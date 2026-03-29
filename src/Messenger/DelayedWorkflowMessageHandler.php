<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Messenger;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DelayedWorkflowMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowExecutor $executor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DelayedWorkflowMessage $message): void
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $message->getCampaignId());
        if ($campaign === null) {
            $this->logger->warning('Delayed workflow: campaign not found.', [
                'campaignId' => $message->getCampaignId(),
            ]);
            return;
        }

        // Check campaign is still active
        if ($campaign->getStatus() !== WorkflowStatus::Active || !$campaign->isEnabled()) {
            $this->logger->info('Delayed workflow: campaign no longer active.', [
                'campaignId' => $campaign->getId(),
                'status' => $campaign->getStatus()->value,
            ]);
            return;
        }

        $run = $this->entityManager->find(WorkflowRun::class, $message->getRunId());
        if ($run === null) {
            $this->logger->warning('Delayed workflow: run not found.', [
                'runId' => $message->getRunId(),
            ]);
            return;
        }

        // Check run hasn't been cancelled or already completed
        if ($run->getStatus() !== WorkflowRun::STATUS_RUNNING) {
            $this->logger->info('Delayed workflow: run is no longer running.', [
                'runId' => $run->getId(),
                'status' => $run->getStatus(),
            ]);
            return;
        }

        try {
            $this->executor->execute(
                campaign: $campaign,
                context: $message->getContext(),
                existingRun: $run,
                resumeFromNodeId: $message->getResumeFromNodeId(),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Delayed workflow execution failed.', [
                'campaignId' => $campaign->getId(),
                'runId' => $run->getId(),
                'error' => $e->getMessage(),
            ]);

            $run->markFailed($e->getMessage());
            $this->entityManager->flush();
        }
    }
}
