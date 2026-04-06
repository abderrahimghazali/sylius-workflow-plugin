<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;

interface WorkflowCampaignInterface extends ResourceInterface, TimestampableInterface
{
    public function getName(): string;

    public function setName(string $name): void;

    public function getDescription(): ?string;

    public function setDescription(?string $description): void;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function getStatus(): WorkflowStatus;

    public function setStatus(WorkflowStatus $status): void;

    public function getGraph(): array;

    public function setGraph(array $graph): void;

    public function getTriggerEvent(): ?string;

    public function setTriggerEvent(?string $triggerEvent): void;

    public function getRunCount(): int;

    public function incrementRunCount(): void;

    public function getLastRunAt(): ?\DateTimeImmutable;

    public function setLastRunAt(?\DateTimeImmutable $lastRunAt): void;

    /** @return Collection<int, WorkflowRunInterface> */
    public function getRuns(): Collection;

    /** @return Collection<int, WorkflowTriggerLogInterface> */
    public function getTriggerLogs(): Collection;

    public function getNodes(): array;

    public function getEdges(): array;
}
