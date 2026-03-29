<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Messenger;

final class DelayedWorkflowMessage
{
    public function __construct(
        private readonly int $campaignId,
        private readonly int $runId,
        private readonly string $resumeFromNodeId,
        private readonly string $event,
        private readonly string $subjectType,
        private readonly int $subjectId,
        private readonly string $channel,
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

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
