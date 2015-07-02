<?php

namespace Guru\PhoneNumberFormatterBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GuruPhoneNumberFormatterExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $locator = new FileLocator(__DIR__.'/../Resources/config');

        $resolver = new LoaderResolver(array(
            new YamlFileLoader($container, $locator)
        ));

        $loader = new DelegatingLoader($resolver);
        $loader->load('parameters.yml');
        $loader->load('services.yml');
    }

    public function getAlias()
    {
        return 'guru_phone_number_formatter';
    }
}
