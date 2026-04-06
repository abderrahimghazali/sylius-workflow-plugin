<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Abderrahim\SyliusWorkflowPlugin\Enum\RunStatus;
use Sylius\Resource\Model\ResourceInterface;

interface WorkflowRunInterface extends ResourceInterface
{
    public function getCampaign(): WorkflowCampaignInterface;

    public function setCampaign(WorkflowCampaignInterface $campaign): void;

    public function getSubjectType(): string;

    public function setSubjectType(string $subjectType): void;

    public function getSubjectId(): int;

    public function setSubjectId(int $subjectId): void;

    public function getStatus(): RunStatus;

    public function setStatus(RunStatus $status): void;

    public function getCurrentNodeId(): string;

    public function setCurrentNodeId(string $currentNodeId): void;

    public function getExecutionLog(): array;

    public function addLogEntry(string $nodeId, string $status, string $message, ?string $actionType = null): void;

    public function getStartedAt(): \DateTimeImmutable;

    public function getCompletedAt(): ?\DateTimeImmutable;

    public function getErrorMessage(): ?string;

    public function markCompleted(): void;

    public function markFailed(string $errorMessage): void;

    public function markSkipped(): void;
}
