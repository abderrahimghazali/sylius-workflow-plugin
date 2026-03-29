<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

interface WorkflowTemplateInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function getCategory(): string;

    public function getThumbnailIcon(): string;

    public function getGraph(): array;
}
