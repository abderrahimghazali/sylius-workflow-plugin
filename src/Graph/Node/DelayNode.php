<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Node;

use Abderrahim\SyliusWorkflowPlugin\Enum\NodeType;

final class DelayNode
{
    public function __construct(
        private readonly string $id,
        private readonly int $amount,
        private readonly string $unit,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            amount: (int) ($data['config']['amount'] ?? 0),
            unit: $data['config']['unit'] ?? 'minutes',
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getDelayInSeconds(): int
    {
        return match ($this->unit) {
            'minutes' => $this->amount * 60,
            'hours' => $this->amount * 3600,
            'days' => $this->amount * 86400,
            'weeks' => $this->amount * 604800,
            default => $this->amount * 60,
        };
    }

    public function getType(): NodeType
    {
        return NodeType::Delay;
    }
}
