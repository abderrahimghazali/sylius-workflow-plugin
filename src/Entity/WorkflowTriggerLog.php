<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

class WorkflowTriggerLog implements WorkflowTriggerLogInterface
{
    protected ?int $id = null;

    protected ?WorkflowCampaignInterface $campaign = null;

    protected string $eventName = '';

    protected int $subjectId = 0;

    protected \DateTimeImmutable $triggeredAt;

    protected string $dedupKey = '';

    public function __construct()
    {
        $this->triggeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): WorkflowCampaignInterface
    {
        return $this->campaign;
    }

    public function setCampaign(WorkflowCampaignInterface $campaign): void
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
        $date = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');

        return hash('sha256', sprintf('%d:%s:%d:%s', $campaignId, $eventName, $subjectId, $date));
    }
}
