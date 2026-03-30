<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Sylius\Resource\Model\ResourceInterface;

interface WorkflowTriggerLogInterface extends ResourceInterface
{
    public function getCampaign(): WorkflowCampaignInterface;

    public function setCampaign(WorkflowCampaignInterface $campaign): void;

    public function getEventName(): string;

    public function setEventName(string $eventName): void;

    public function getSubjectId(): int;

    public function setSubjectId(int $subjectId): void;

    public function getTriggeredAt(): \DateTimeImmutable;

    public function getDedupKey(): string;

    public function setDedupKey(string $dedupKey): void;
}
