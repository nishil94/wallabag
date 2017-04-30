<?php

namespace Wallabag\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Wallabag\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("oauth2_clients")
 * @ORM\Entity
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="RefreshToken", mappedBy="client", cascade={"remove"})
     */
    protected $refreshTokens;

    /**
     * @ORM\OneToMany(targetEntity="AccessToken", mappedBy="client", cascade={"remove"})
     */
    protected $accessTokens;

    /**
     * @ORM\ManyToOne(targetEntity="Wallabag\UserBundle\Entity\User", inversedBy="clients")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\File(
     *     maxSize = "5M",
     *     mimeTypes = {"image/jpeg", "image/gif", "image/png", "image/tiff"},
     *     maxSizeMessage = "The maximum allowed file size is 5MB.",
     *     mimeTypesMessage = "Only the filetypes image are allowed."
     * )
     */
    private $image;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $description;

    /**
     * Client constructor.
     * @param User|null $user
     */
    public function __construct(User $user = null)
    {
        parent::__construct();
        $this->user = $user;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Client
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
