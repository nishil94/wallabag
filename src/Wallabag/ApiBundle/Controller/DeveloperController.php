<?php

namespace Wallabag\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Wallabag\ApiBundle\Entity\Client;
use Wallabag\ApiBundle\Form\Type\ClientType;
use Wallabag\ApiBundle\Form\Type\GlobalClientType;

class DeveloperController extends Controller
{
    /**
     * List all clients and link to create a new one.
     *
     * @Route("/developer", name="developer")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $clients = $this->getDoctrine()->getRepository('WallabagApiBundle:Client')->findByUser($this->getUser()->getId());
        $global_clients = $this->getDoctrine()->getRepository('WallabagApiBundle:Client')->findByUser(null);

        return $this->render('@WallabagCore/themes/common/Developer/index.html.twig', [
            'clients' => $clients,
            'global_clients' => $global_clients,
        ]);
    }

    /**
     * Create a global client (an app) for all users
     *
     * @param Request $request
     *
     * @Route("/developer/client/global/create", name="developer_create_global_client")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createGlobalClientAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $client = new Client();
        $clientForm = $this->createForm(GlobalClientType::class, $client);
        $clientForm->handleRequest($request);

        if ($clientForm->isSubmitted() && $clientForm->isValid()) {

            /** Handling the application icon */
            $file = $client->getImage();
            $fileName = md5(uniqid('', true)).'.'.$file->guessExtension();

            $file->move(
                $this->getParameter('wallabag_api.applications_icon_path'),
                $fileName
            );

            $client->setImage($fileName);


            $client->setAllowedGrantTypes(['token', 'authorization_code', 'refresh_token']);
            $em->persist($client);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice',
                $this->get('translator')->trans('flashes.developer.notice.client_created', ['%name%' => $client->getName()])
            );

            return $this->render('@WallabagCore/themes/common/Developer/client_parameters.html.twig', [
                'client_id' => $client->getPublicId(),
                'client_secret' => $client->getSecret(),
                'client_name' => $client->getName(),
            ]);
        }

        return $this->render('@WallabagCore/themes/common/Developer/client.html.twig', [
            'form' => $clientForm->createView(),
        ]);
    }

    /**
     * Create a client (an app).
     *
     * @param Request $request
     *
     * @Route("/developer/client/create", name="developer_create_client")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createClientAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $client = new Client($this->getUser());
        $clientForm = $this->createForm(ClientType::class, $client);
        $clientForm->handleRequest($request);

        if ($clientForm->isSubmitted() && $clientForm->isValid()) {
            $client->setAllowedGrantTypes(['token', 'refresh_token', 'client_credentials']);
            $em->persist($client);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice',
                $this->get('translator')->trans('flashes.developer.notice.client_created', ['%name%' => $client->getName()])
            );

            return $this->render('@WallabagCore/themes/common/Developer/client_parameters.html.twig', [
                'client_id' => $client->getPublicId(),
                'client_secret' => $client->getSecret(),
                'client_name' => $client->getName(),
            ]);
        }

        return $this->render('@WallabagCore/themes/common/Developer/client.html.twig', [
            'form' => $clientForm->createView(),
        ]);
    }

    /**
     * Remove a client.
     *
     * @param Client $client
     *
     * @Route("/developer/client/delete/{id}", requirements={"id" = "\d+"}, name="developer_delete_client")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteClientAction(Client $client)
    {
        if (($client->getUser() !== null) && (null === $this->getUser() || $client->getUser()->getId() != $this->getUser()->getId())) {
            throw $this->createAccessDeniedException('You can not access this client.');
        }

        if ($img = $client->getImage()) {
            if ($file = $this->getParameter('wallabag_api.applications_icon_path') . '/' . $img) {
                unlink($file);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($client);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            $this->get('translator')->trans('flashes.developer.notice.client_deleted', ['%name%' => $client->getName()])
        );

        return $this->redirect($this->generateUrl('developer'));
    }

    /**
     * Display developer how to use an existing app.
     *
     * @Route("/developer/howto/first-app", name="developer_howto_firstapp")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function howtoFirstAppAction()
    {
        return $this->render('@WallabagCore/themes/common/Developer/howto_app.html.twig');
    }
}
