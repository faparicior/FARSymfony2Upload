<?php

namespace faparicior\FARSymfony2UploadBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('far_symfony2_upload');

        $rootNode
            ->children()
                ->scalarNode('prefix')
                    ->isRequired()
                ->end()
                ->scalarNode('temp_path')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('thumbnail_directory_prefix')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('thumbnail_driver')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('thumbnail_size')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('max_file_size')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('max_files_upload')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('file_extensions_allowed')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->requiresAtLeastOneElement()
                        ->prototype('scalar')
                    ->end()
                ->end()
                ->scalarNode('local_filesystem')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('remote_filesystem')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
