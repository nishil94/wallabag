<?php

namespace Wallabag\CoreBundle\GuzzleSiteAuthenticator;

use BD\GuzzleSiteAuthenticator\SiteConfig\SiteConfig;
use BD\GuzzleSiteAuthenticator\SiteConfig\SiteConfigBuilder;
use Graby\SiteConfig\ConfigBuilder;
use Wallabag\CoreBundle\Repository\SiteCredentialRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use OutOfRangeException;

class GrabySiteConfigBuilder implements SiteConfigBuilder
{
    /**
     * @var ConfigBuilder
     */
    private $grabyConfigBuilder;

    /**
     * @var SiteCredentialRepository
     */
    private $credentialRepository;

    /**
     * @var Wallabag\UserBundle\Entity\User|null
     */
    private $currentUser;

    /**
     * GrabySiteConfigBuilder constructor.
     *
     * @param ConfigBuilder            $grabyConfigBuilder
     * @param User                     $currentUser
     * @param SiteCredentialRepository $credentialRepository
     */
    public function __construct(ConfigBuilder $grabyConfigBuilder, TokenStorage $token, SiteCredentialRepository $credentialRepository)
    {
        $this->grabyConfigBuilder = $grabyConfigBuilder;
        $this->credentialRepository = $credentialRepository;

        if ($token->getToken()) {
            $this->currentUser = $token->getToken()->getUser();
        }
    }

    /**
     * Builds the SiteConfig for a host.
     *
     * @param string $host The "www." prefix is ignored
     *
     * @return SiteConfig
     *
     * @throws OutOfRangeException If there is no config for $host
     */
    public function buildForHost($host)
    {
        // required by credentials below
        $host = strtolower($host);
        if (substr($host, 0, 4) == 'www.') {
            $host = substr($host, 4);
        }

        $config = $this->grabyConfigBuilder->buildForHost($host);
        $parameters = [
            'host' => $host,
            'requiresLogin' => $config->requires_login ?: false,
            'loginUri' => $config->login_uri ?: null,
            'usernameField' => $config->login_username_field ?: null,
            'passwordField' => $config->login_password_field ?: null,
            'extraFields' => is_array($config->login_extra_fields) ? $config->login_extra_fields : [],
            'notLoggedInXpath' => $config->not_logged_in_xpath ?: null,
        ];

        if ($this->currentUser) {
            $credentials = $this->credentialRepository->findOneByHostAndUser($host, $this->currentUser->getId());

            if (null !== $credentials) {
                $parameters['username'] = $credentials['username'];
                $parameters['password'] = $credentials['password'];
            }
        }

        return new SiteConfig($parameters);
    }
}
