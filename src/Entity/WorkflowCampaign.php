<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\ResourceInterface;

class WorkflowCampaign implements ResourceInterface
{
    private ?int $id = null;

    private string $name = '';

    private ?string $description = null;

    private bool $enabled = false;

    private WorkflowStatus $status = WorkflowStatus::Draft;

    private array $graph = ['nodes' => [], 'edges' => []];

    private int $runCount = 0;

    private ?\DateTimeImmutable $lastRunAt = null;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, WorkflowRun> */
    private Collection $runs;

    /** @var Collection<int, WorkflowTriggerLog> */
    private Collection $triggerLogs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->runs = new ArrayCollection();
        $this->triggerLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getStatus(): WorkflowStatus
    {
        return $this->status;
    }

    public function setStatus(WorkflowStatus $status): void
    {
        $this->status = $status;
    }

    public function getGraph(): array
    {
        return $this->graph;
    }

    public function setGraph(array $graph): void
    {
        $this->graph = $graph;
    }

    public function getRunCount(): int
    {
        return $this->runCount;
    }

    public function incrementRunCount(): void
    {
        $this->runCount++;
    }

    public function getLastRunAt(): ?\DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(?\DateTimeImmutable $lastRunAt): void
    {
        $this->lastRunAt = $lastRunAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /** @return Collection<int, WorkflowRun> */
    public function getRuns(): Collection
    {
        return $this->runs;
    }

    /** @return Collection<int, WorkflowTriggerLog> */
    public function getTriggerLogs(): Collection
    {
        return $this->triggerLogs;
    }

    public function getNodes(): array
    {
        return $this->graph['nodes'] ?? [];
    }

    public function getEdges(): array
    {
        return $this->graph['edges'] ?? [];
    }
}
