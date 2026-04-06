<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Entity;

use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\TimestampableTrait;

class WorkflowCampaign implements WorkflowCampaignInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected string $name = '';

    protected ?string $description = null;

    protected bool $enabled = false;

    protected WorkflowStatus $status = WorkflowStatus::Draft;

    protected array $graph = ['nodes' => [], 'edges' => []];

    protected ?string $triggerEvent = null;

    protected int $runCount = 0;

    protected ?\DateTimeImmutable $lastRunAt = null;

    /** @var Collection<int, WorkflowRunInterface> */
    protected Collection $runs;

    /** @var Collection<int, WorkflowTriggerLogInterface> */
    protected Collection $triggerLogs;

    public function __construct()
    {
        $this->runs = new ArrayCollection();
        $this->triggerLogs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
        $this->triggerEvent = $this->extractTriggerEvent($graph);
    }

    public function getTriggerEvent(): ?string
    {
        return $this->triggerEvent;
    }

    public function setTriggerEvent(?string $triggerEvent): void
    {
        $this->triggerEvent = $triggerEvent;
    }

    private function extractTriggerEvent(array $graph): ?string
    {
        foreach ($graph['nodes'] ?? [] as $node) {
            if (($node['type'] ?? '') === 'trigger') {
                return $node['config']['event'] ?? null;
            }
        }

        return null;
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

    /** @return Collection<int, WorkflowRunInterface> */
    public function getRuns(): Collection
    {
        return $this->runs;
    }

    /** @return Collection<int, WorkflowTriggerLogInterface> */
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
