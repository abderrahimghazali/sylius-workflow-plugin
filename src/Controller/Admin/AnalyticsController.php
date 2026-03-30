<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowRunRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final class AnalyticsController
{
    public function __construct(
        private readonly WorkflowRunRepository $runRepository,
        private readonly Environment $twig,
    ) {
    }

    public function index(): Response
    {
        $since = new \DateTimeImmutable('-30 days');

        $stats = $this->runRepository->getAnalyticsStats($since);
        $dailyRuns = $this->runRepository->getDailyRunCounts($since);
        $perCampaign = $this->runRepository->getPerCampaignStats($since);

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/analytics/index.html.twig', [
            'stats' => $stats,
            'dailyRuns' => $dailyRuns,
            'perCampaign' => $perCampaign,
        ]);

        return new Response($content);
    }
}
