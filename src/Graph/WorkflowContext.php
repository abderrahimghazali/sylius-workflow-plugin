<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph;

final class WorkflowContext
{
    public function __construct(
        private readonly string $event,
        private readonly object $subject,
        private readonly string $channel,
        private array $extra = [],
    ) {
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getSubject(): object
    {
        return $this->subject;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->extra[$key] = $value;
    }
}
