<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

#[AsController]
final class WorkflowRunController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowRunRepository $runRepository,
        private readonly Environment $twig,
    ) {
    }

    public function index(int $id): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $runs = $this->runRepository->findBy(
            ['campaign' => $campaign],
            ['startedAt' => 'DESC'],
            100,
        );

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/workflow_run/index.html.twig', [
            'campaign' => $campaign,
            'runs' => $runs,
        ]);

        return new Response($content);
    }

    public function show(int $campaignId, int $runId): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $campaignId);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $run = $this->entityManager->find(WorkflowRun::class, $runId);
        if ($run === null || $run->getCampaign()->getId() !== $campaignId) {
            throw new NotFoundHttpException('Run not found.');
        }

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/workflow_run/show.html.twig', [
            'campaign' => $campaign,
            'run' => $run,
        ]);

        return new Response($content);
    }
}
