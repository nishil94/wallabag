<?php

namespace Wallabag\ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wallabag\CoreBundle\Entity\Entry;
use Wallabag\CoreBundle\Entity\Tag;

class TagRestController extends WallabagRestController
{
    /**
     * Retrieve all tags.
     *
     * @ApiDoc()
     * @Security("has_role('ROLE_READ')")
     * @return JsonResponse
     */
    public function getTagsAction()
    {
        $this->validateAuthentication();

        $tags = $this->getDoctrine()
            ->getRepository('WallabagCoreBundle:Tag')
            ->findAllTags($this->getUser()->getId());

        $json = $this->get('serializer')->serialize($tags, 'json');

        return (new JsonResponse())->setJson($json);
    }

    /**
     * Permanently remove one tag from **every** entry by passing the Tag label.
     *
     * @ApiDoc(
     *      requirements={
     *          {"name"="tag", "dataType"="string", "required"=true, "requirement"="\w+", "description"="Tag as a string"}
     *      }
     * )
     * @Security("has_role('ROLE_WRITE')")
     * @return JsonResponse
     */
    public function deleteTagLabelAction(Request $request)
    {
        $this->validateAuthentication();
        $label = $request->request->get('tag', '');

        $tag = $this->getDoctrine()->getRepository('WallabagCoreBundle:Tag')->findOneByLabel($label);

        if (empty($tag)) {
            throw $this->createNotFoundException('Tag not found');
        }

        $this->getDoctrine()
            ->getRepository('WallabagCoreBundle:Entry')
            ->removeTag($this->getUser()->getId(), $tag);

        $this->cleanOrphanTag($tag);

        $json = $this->get('serializer')->serialize($tag, 'json');

        return (new JsonResponse())->setJson($json);
    }

    /**
     * Permanently remove some tags from **every** entry.
     *
     * @ApiDoc(
     *      requirements={
     *          {"name"="tags", "dataType"="string", "required"=true, "format"="tag1,tag2", "description"="Tags as strings (comma splitted)"}
     *      }
     * )
     * @Security("has_role('ROLE_WRITE')")
     * @return JsonResponse
     */
    public function deleteTagsLabelAction(Request $request)
    {
        $this->validateAuthentication();

        $tagsLabels = $request->request->get('tags', '');

        $tags = [];

        foreach (explode(',', $tagsLabels) as $tagLabel) {
            $tagEntity = $this->getDoctrine()->getRepository('WallabagCoreBundle:Tag')->findOneByLabel($tagLabel);

            if (!empty($tagEntity)) {
                $tags[] = $tagEntity;
            }
        }

        if (empty($tags)) {
            throw $this->createNotFoundException('Tags not found');
        }

        $this->getDoctrine()
            ->getRepository('WallabagCoreBundle:Entry')
            ->removeTags($this->getUser()->getId(), $tags);

        $this->cleanOrphanTag($tags);

        $json = $this->get('serializer')->serialize($tags, 'json');

        return (new JsonResponse())->setJson($json);
    }

    /**
     * Permanently remove one tag from **every** entry by passing the Tag ID.
     *
     * @ApiDoc(
     *      requirements={
     *          {"name"="tag", "dataType"="integer", "requirement"="\w+", "description"="The tag"}
     *      }
     * )
     * @Security("has_role('ROLE_WRITE')")
     * @return JsonResponse
     */
    public function deleteTagAction(Tag $tag)
    {
        $this->validateAuthentication();

        $this->getDoctrine()
            ->getRepository('WallabagCoreBundle:Entry')
            ->removeTag($this->getUser()->getId(), $tag);

        $this->cleanOrphanTag($tag);

        $json = $this->get('serializer')->serialize($tag, 'json');

        return (new JsonResponse())->setJson($json);
    }

    /**
     * Remove orphan tag in case no entries are associated to it.
     * @Security("has_role('ROLE_WRITE')")
     * @param Tag|array $tags
     */
    private function cleanOrphanTag($tags)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        $em = $this->getDoctrine()->getManager();

        foreach ($tags as $tag) {
            if (count($tag->getEntries()) === 0) {
                $em->remove($tag);
            }
        }

        $em->flush();
    }
}
