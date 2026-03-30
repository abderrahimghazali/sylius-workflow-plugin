<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final class WorkflowRunController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowRunRepository $runRepository,
        private readonly Environment $twig,
    ) {
    }

    public function index(int $id, Request $request): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $total = $this->runRepository->count(['campaign' => $campaign]);
        $runs = $this->runRepository->findBy(
            ['campaign' => $campaign],
            ['startedAt' => 'DESC'],
            $limit,
            $offset,
        );

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/workflow_run/index.html.twig', [
            'campaign' => $campaign,
            'runs' => $runs,
            'page' => $page,
            'totalPages' => (int) ceil($total / $limit),
            'total' => $total,
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
        if ($run === null || $run->getCampaign()->getId() !== $campaign->getId()) {
            throw new NotFoundHttpException('Run not found.');
        }

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/workflow_run/show.html.twig', [
            'campaign' => $campaign,
            'run' => $run,
        ]);

        return new Response($content);
    }
}
