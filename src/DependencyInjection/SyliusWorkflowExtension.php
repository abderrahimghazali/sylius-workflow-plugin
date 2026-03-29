<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\DependencyInjection;

use Abderrahim\SyliusWorkflowPlugin\Graph\Action\ActionInterface;
use Abderrahim\SyliusWorkflowPlugin\Graph\Rule\RuleInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusWorkflowExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $container->registerForAutoconfiguration(RuleInterface::class)
            ->addTag('sylius_workflow.rule');

        $container->registerForAutoconfiguration(ActionInterface::class)
            ->addTag('sylius_workflow.action');
    }
}
