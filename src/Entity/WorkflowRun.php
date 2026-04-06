<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Abderrahim\SyliusWorkflowPlugin\Enum\RunStatus;

class WorkflowRun implements WorkflowRunInterface
{
    protected ?int $id = null;

    protected ?WorkflowCampaignInterface $campaign = null;

    protected string $subjectType = '';

    protected int $subjectId = 0;

    protected RunStatus $status = RunStatus::Running;

    protected string $currentNodeId = '';

    protected array $executionLog = [];

    protected \DateTimeImmutable $startedAt;

    protected ?\DateTimeImmutable $completedAt = null;

    protected ?string $errorMessage = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
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

    public function getStatus(): RunStatus
    {
        return $this->status;
    }

    public function setStatus(RunStatus $status): void
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

    public function addLogEntry(string $nodeId, string $status, string $message, ?string $actionType = null): void
    {
        if (\count($this->executionLog) >= 500) {
            return;
        }

        $entry = [
            'nodeId' => $nodeId,
            'status' => $status,
            'message' => $message,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ];

        if ($actionType !== null) {
            $entry['actionType'] = $actionType;
        }

        $this->executionLog[] = $entry;
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
        $this->status = RunStatus::Completed;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markFailed(string $errorMessage): void
    {
        $this->status = RunStatus::Failed;
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markSkipped(): void
    {
        $this->status = RunStatus::Skipped;
        $this->completedAt = new \DateTimeImmutable();
    }
}
