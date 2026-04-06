<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\DependencyInjection;

use Abderrahim\SyliusWorkflowPlugin\Graph\Action\ActionInterface;
use Abderrahim\SyliusWorkflowPlugin\Graph\Action\SendWebhookAction;
use Abderrahim\SyliusWorkflowPlugin\Graph\Rule\RuleInterface;
use Abderrahim\SyliusWorkflowPlugin\Template\WorkflowTemplateInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusWorkflowExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $container->registerForAutoconfiguration(RuleInterface::class)
            ->addTag('sylius_workflow.rule');

        $container->registerForAutoconfiguration(ActionInterface::class)
            ->addTag('sylius_workflow.action');

        $container->registerForAutoconfiguration(WorkflowTemplateInterface::class)
            ->addTag('sylius_workflow.template');

        // Set parameters from configuration
        $container->setParameter('sylius_workflow.webhook.timeout', $config['webhook']['timeout']);
        $container->setParameter('sylius_workflow.webhook.allow_http', $config['webhook']['allow_http']);
        $container->setParameter('sylius_workflow.execution.max_steps', $config['execution']['max_steps']);
        $container->setParameter('sylius_workflow.execution.max_log_entries', $config['execution']['max_log_entries']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));

        $loader->load('resources/workflow_campaign.yaml');
        $loader->load('resources/workflow_run.yaml');
        $loader->load('grids/admin/workflow_campaign.yaml');
    }
}
