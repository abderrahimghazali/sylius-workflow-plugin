<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sylius_workflow');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('webhook')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('timeout')
                            ->defaultValue(10)
                            ->info('HTTP timeout in seconds for webhook requests.')
                        ->end()
                        ->booleanNode('allow_http')
                            ->defaultFalse()
                            ->info('Allow HTTP (non-HTTPS) webhook URLs. Not recommended for production.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('execution')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_steps')
                            ->defaultValue(200)
                            ->info('Maximum number of graph steps per workflow execution.')
                        ->end()
                        ->integerNode('max_log_entries')
                            ->defaultValue(500)
                            ->info('Maximum number of log entries per workflow run.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
