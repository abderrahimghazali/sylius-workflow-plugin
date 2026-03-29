<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

class WorkflowTriggerLog
{
    private ?int $id = null;

    private WorkflowCampaign $campaign;

    private string $eventName = '';

    private int $subjectId = 0;

    private \DateTimeImmutable $triggeredAt;

    private string $dedupKey = '';

    public function __construct()
    {
        $this->triggeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): WorkflowCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(WorkflowCampaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): void
    {
        $this->eventName = $eventName;
    }

    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    public function setSubjectId(int $subjectId): void
    {
        $this->subjectId = $subjectId;
    }

    public function getTriggeredAt(): \DateTimeImmutable
    {
        return $this->triggeredAt;
    }

    public function getDedupKey(): string
    {
        return $this->dedupKey;
    }

    public function setDedupKey(string $dedupKey): void
    {
        $this->dedupKey = $dedupKey;
    }

    public static function generateDedupKey(int $campaignId, string $eventName, int $subjectId): string
    {
        return hash('sha256', sprintf('%d:%s:%d:%s', $campaignId, $eventName, $subjectId, date('Y-m-d')));
    }
}
