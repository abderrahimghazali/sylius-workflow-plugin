<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Messenger;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DelayedWorkflowMessageHandler
{
    private const SUBJECT_CLASS_MAP = [
        'order' => 'Sylius\Component\Core\Model\Order',
        'customer' => 'Sylius\Component\Core\Model\Customer',
    ];

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

        if ($run->getStatus() !== WorkflowRun::STATUS_RUNNING) {
            $this->logger->info('Delayed workflow: run is no longer running.', [
                'runId' => $run->getId(),
                'status' => $run->getStatus(),
            ]);
            return;
        }

        // Reconstruct subject from scalar IDs
        $subject = $this->resolveSubject($message->getSubjectType(), $message->getSubjectId());
        if ($subject === null) {
            $this->logger->warning('Delayed workflow: subject not found.', [
                'subjectType' => $message->getSubjectType(),
                'subjectId' => $message->getSubjectId(),
            ]);
            $run->markFailed('Subject not found when resuming.');
            $this->entityManager->flush();
            return;
        }

        $context = new WorkflowContext(
            event: $message->getEvent(),
            subject: $subject,
            channel: $message->getChannel(),
        );

        try {
            $this->executor->execute(
                campaign: $campaign,
                context: $context,
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

    private function resolveSubject(string $type, int $id): ?object
    {
        $class = self::SUBJECT_CLASS_MAP[$type] ?? null;
        if ($class === null || !class_exists($class)) {
            return null;
        }

        return $this->entityManager->find($class, $id);
    }
}
