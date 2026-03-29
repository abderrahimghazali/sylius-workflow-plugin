<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addWorkflowMenu(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $marketingMenu = $menu->getChild('marketing');
        if ($marketingMenu !== null) {
            $marketingMenu
                ->addChild('automation_workflows', [
                    'route' => 'workflow_admin_campaign_index',
                    'extras' => ['routes' => [
                        ['route' => 'workflow_admin_campaign_create'],
                        ['route' => 'sylius_workflow_admin_canvas'],
                        ['route' => 'workflow_admin_run_index'],
                        ['route' => 'workflow_admin_analytics'],
                    ]],
                ])
                ->setLabel('workflow.ui.automation_workflows')
                ->setLabelAttribute('icon', 'git-branch')
            ;
        }
    }
}
