<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final class WorkflowCampaignController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
    ) {
    }

    #[Route(
        path: '/admin/workflows/{id}/edit',
        name: 'sylius_workflow_admin_canvas',
        methods: ['GET'],
    )]
    public function editCanvas(int $id): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Campaign not found.');
        }

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/workflow_campaign/canvas.html.twig', [
            'campaign' => $campaign,
        ]);

        return new Response($content);
    }
}
