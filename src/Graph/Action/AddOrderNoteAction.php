<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class AddOrderNoteAction implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'add_order_note';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $note = $config['note'] ?? '';
        if ($note === '') {
            return ['success' => false, 'message' => 'No note content specified.'];
        }

        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return ['success' => false, 'message' => 'Subject is not an order.'];
        }

        if (method_exists($subject, 'setNotes')) {
            $subject->setNotes(
                ($subject->getNotes() ?? '') . "\n[Workflow] " . $note
            );
        } else {
            return ['success' => false, 'message' => 'Order entity does not support notes.'];
        }

        $this->entityManager->flush();

        return ['success' => true, 'message' => 'Note added to order.'];
    }
}
