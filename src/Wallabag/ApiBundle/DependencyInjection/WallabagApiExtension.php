<?php

namespace Wallabag\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class WallabagApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('wallabag_api.applications_icon_path', $config['applications_icon_path']);
    }

    public function getAlias()
    {
        return 'wallabag_api';
    }
}
