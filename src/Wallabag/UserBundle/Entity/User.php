<?php

namespace Wallabag\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedComputerInterface;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Wallabag\ApiBundle\Entity\Client;
use Wallabag\CoreBundle\Entity\Config;
use Wallabag\CoreBundle\Entity\Entry;
use Wallabag\GroupBundle\Entity\Group;
use Wallabag\GroupBundle\Entity\UserGroup;

/**
 * User.
 *
 * @ORM\Entity(repositoryClass="Wallabag\UserBundle\Repository\UserRepository")
 * @ORM\Table(name="`user`")
 * @ORM\HasLifecycleCallbacks()
 * @ExclusionPolicy("all")
 *
 * @UniqueEntity("email")
 * @UniqueEntity("username")
 */
class User extends BaseUser implements TwoFactorInterface, TrustedComputerInterface
{
    /**
     * @var int
     *
     * @Expose
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    protected $name;

    /**
     * @var date
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var date
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="Wallabag\CoreBundle\Entity\Entry", mappedBy="user", cascade={"remove"})
     */
    protected $entries;

    /**
     * @ORM\OneToOne(targetEntity="Wallabag\CoreBundle\Entity\Config", mappedBy="user", cascade={"remove"})
     */
    protected $config;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $authCode;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Wallabag\GroupBundle\Entity\UserGroup", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $userGroups;

    /**
     * @var bool Enabled yes/no
     * @ORM\Column(type="boolean")
     */
    private $twoFactorAuthentication = false;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $trusted;

    /**
     * @ORM\OneToMany(targetEntity="Wallabag\ApiBundle\Entity\Client", mappedBy="user", cascade={"remove"})
     */
    protected $clients;

    public function __construct()
    {
        parent::__construct();
        $this->entries = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function timestamps()
    {
        if (is_null($this->createdAt)) {
            $this->createdAt = new \DateTime();
        }

        $this->updatedAt = new \DateTime();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param Entry $entry
     *
     * @return User
     */
    public function addEntry(Entry $entry)
    {
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * @return ArrayCollection<Entry>
     */
    public function getEntries()
    {
        return $this->entries;
    }

    public function isEqualTo(UserInterface $user)
    {
        return $this->username === $user->getUsername();
    }

    /**
     * Set config.
     *
     * @param Config $config
     *
     * @return User
     */
    public function setConfig(Config $config = null)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function isTwoFactorAuthentication()
    {
        return $this->twoFactorAuthentication;
    }

    /**
     * @param bool $twoFactorAuthentication
     */
    public function setTwoFactorAuthentication($twoFactorAuthentication)
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;
    }

    public function isEmailAuthEnabled()
    {
        return $this->twoFactorAuthentication;
    }

    public function getEmailAuthCode()
    {
        return $this->authCode;
    }

    public function setEmailAuthCode($authCode)
    {
        $this->authCode = $authCode;
    }

    public function addTrustedComputer($token, \DateTime $validUntil)
    {
        $this->trusted[$token] = $validUntil->format('r');
    }

    public function isTrustedComputer($token)
    {
        if (isset($this->trusted[$token])) {
            $now = new \DateTime();
            $validUntil = new \DateTime($this->trusted[$token]);

            return $now < $validUntil;
        }

        return false;
    }

    /**
     * @param Client $client
     *
     * @return User
     */
    public function addClient(Client $client)
    {
        $this->clients[] = $client;

        return $this;
    }

    /**
     * @return ArrayCollection<Entry>
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @param Group $group
     *
     * @return UserGroup
     */
    public function getUserGroupFromGroup(Group $group)
    {
        foreach ($this->userGroups as $userGroup) {
            if ($userGroup->getGroup() == $group) {
                return $userGroup;
            }
        }

        return null;
    }

    /**
     * @param Group $group
     * @param $role
     */
    public function setGroupRole(Group $group, $role)
    {
        if ($userGroup = $this->getUserGroupFromGroup($group)) {
            $userGroup->setRole($role);
        }
    }

    /**
     * @param Group $group
     *
     * @return int
     */
    public function getGroupRoleForUser(Group $group)
    {
        if ($userGroup = $this->getUserGroupFromGroup($group)) {
            return $userGroup->getRole();
        }

        return 0;
    }

    /**
     * @param Group $group
     *
     * @return bool
     */
    public function inGroup(Group $group)
    {
        if ($group::ACCESS_REQUEST === $group->getAcceptSystem()) {
            $userGroup = $this->getUserGroupFromGroup($group);

            return $userGroup->isAccepted();
        }

        return null !== $this->getUserGroupFromGroup($group);
    }

    /**
     * @return ArrayCollection<Group>
     */
    public function getGroups()
    {
        $groups = new ArrayCollection();
        foreach ($this->userGroups as $userGroup) {
            $groups->add($userGroup->getGroup());
        }

        return $groups;
    }
}
