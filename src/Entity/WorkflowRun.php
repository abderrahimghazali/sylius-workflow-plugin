<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Sylius\Resource\Model\ResourceInterface;

class WorkflowRun implements ResourceInterface
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    private ?int $id = null;

    private WorkflowCampaign $campaign;

    private string $subjectType = '';

    private int $subjectId = 0;

    private string $status = self::STATUS_RUNNING;

    private string $currentNodeId = '';

    private array $executionLog = [];

    private \DateTimeImmutable $startedAt;

    private ?\DateTimeImmutable $completedAt = null;

    private ?string $errorMessage = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
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

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function setSubjectType(string $subjectType): void
    {
        $this->subjectType = $subjectType;
    }

    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    public function setSubjectId(int $subjectId): void
    {
        $this->subjectId = $subjectId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCurrentNodeId(): string
    {
        return $this->currentNodeId;
    }

    public function setCurrentNodeId(string $currentNodeId): void
    {
        $this->currentNodeId = $currentNodeId;
    }

    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    public function addLogEntry(string $nodeId, string $status, string $message): void
    {
        $this->executionLog[] = [
            'nodeId' => $nodeId,
            'status' => $status,
            'message' => $message,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ];
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markFailed(string $errorMessage): void
    {
        $this->status = self::STATUS_FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markSkipped(): void
    {
        $this->status = self::STATUS_SKIPPED;
        $this->completedAt = new \DateTimeImmutable();
    }
}
