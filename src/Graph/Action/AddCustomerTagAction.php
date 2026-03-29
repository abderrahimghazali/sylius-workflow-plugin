<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class AddCustomerTagAction implements ActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'add_customer_tag';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $tag = $config['tag'] ?? '';
        if ($tag === '') {
            return ['success' => false, 'message' => 'No tag specified.'];
        }

        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return ['success' => false, 'message' => 'No customer found in context.'];
        }

        if (!method_exists($customer, 'addTag')) {
            // Store tag in workflow context for downstream actions
            $tags = $context->get('customer_tags', []);
            $tags[] = $tag;
            $context->set('customer_tags', $tags);

            return ['success' => true, 'message' => sprintf('Tag "%s" recorded (customer entity does not have native tag support).', $tag)];
        }

        $customer->addTag($tag);
        $this->entityManager->flush();

        return ['success' => true, 'message' => sprintf('Tag "%s" added to customer.', $tag)];
    }

    private function resolveCustomer(WorkflowContext $context): ?object
    {
        $subject = $context->getSubject();

        if (method_exists($subject, 'getEmail')) {
            return $subject;
        }

        if ($subject instanceof OrderInterface && $subject->getCustomer() !== null) {
            return $subject->getCustomer();
        }

        return null;
    }
}
