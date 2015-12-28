<?php

namespace FARSymfony2UploadBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class FARSymfony2UploadExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('far_symfony2_upload.prefix', $config['prefix']);
        $container->setParameter('far_symfony2_upload.temp_path', $config['temp_path']);
        $container->setParameter(
            'far_symfony2_upload.thumbnail_directory_prefix',
            $config['thumbnail_directory_prefix']
        );
        $container->setParameter('far_symfony2_upload.thumbnail_driver', $config['thumbnail_driver']);
        $container->setParameter('far_symfony2_upload.thumbnail_size', $config['thumbnail_size']);
        $container->setParameter('far_symfony2_upload.max_file_size', $config['max_file_size']);
        $container->setParameter('far_symfony2_upload.max_files_upload', $config['max_files_upload']);
        $container->setParameter('far_symfony2_upload.file_extensions_allowed', $config['file_extensions_allowed']);
        $container->setParameter('far_symfony2_upload.local_filesystem', $config['local_filesystem']);
        $container->setParameter('far_symfony2_upload.remote_filesystem', $config['remote_filesystem']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
