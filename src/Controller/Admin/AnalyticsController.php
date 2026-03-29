<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowCampaignRepository;
use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowRunRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;

#[AsController]
final class AnalyticsController
{
    public function __construct(
        private readonly WorkflowRunRepository $runRepository,
        private readonly WorkflowCampaignRepository $campaignRepository,
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
