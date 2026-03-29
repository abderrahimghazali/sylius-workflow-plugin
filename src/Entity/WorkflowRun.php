<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowRunRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkflowRunRepository::class)]
#[ORM\Table(name: 'abderrahim_workflow_run')]
class WorkflowRun
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkflowCampaign::class, inversedBy: 'runs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkflowCampaign $campaign;

    #[ORM\Column(length: 50)]
    private string $subjectType;

    #[ORM\Column]
    private int $subjectId;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_RUNNING;

    #[ORM\Column(length: 255)]
    private string $currentNodeId = '';

    #[ORM\Column(type: 'json')]
    private array $executionLog = [];

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
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
