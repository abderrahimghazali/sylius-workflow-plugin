<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Messenger;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;

final class DelayedWorkflowMessage
{
    public function __construct(
        private readonly int $campaignId,
        private readonly int $runId,
        private readonly string $resumeFromNodeId,
        private readonly WorkflowContext $context,
    ) {
    }

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getRunId(): int
    {
        return $this->runId;
    }

    public function getResumeFromNodeId(): string
    {
        return $this->resumeFromNodeId;
    }

    public function getContext(): WorkflowContext
    {
        return $this->context;
    }
}
