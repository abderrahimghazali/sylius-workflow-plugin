<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class AddOrderTagAction implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'add_order_tag';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $tag = $config['tag'] ?? '';
        if ($tag === '') {
            return ['success' => false, 'message' => 'No tag specified.'];
        }

        // Store in workflow context
        $tags = $context->get('order_tags', []);
        $tags[] = $tag;
        $context->set('order_tags', $tags);

        // Also append to order notes
        $subject = $context->getSubject();
        if ($subject instanceof OrderInterface && method_exists($subject, 'setNotes')) {
            $notes = $subject->getNotes() ?? '';
            $subject->setNotes($notes . "\n[Workflow Tag] " . $tag);
            $this->entityManager->flush();
        }

        return ['success' => true, 'message' => sprintf('Tag "%s" added to order.', $tag)];
    }
}
