<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SendSmsAction implements ActionInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'send_sms';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $phone = $config['phone'] ?? '';
        $message = $config['message'] ?? '';

        if ($message === '') {
            return ['success' => false, 'message' => 'No SMS message specified.'];
        }

        // Resolve phone from customer if not provided
        if ($phone === '') {
            $phone = $this->resolvePhone($context);
        }

        if ($phone === '') {
            return ['success' => false, 'message' => 'No phone number found.'];
        }

        try {
            $event = new GenericEvent($context->getSubject(), [
                'phone' => $phone,
                'message' => $message,
                'context' => $context,
            ]);
            $this->eventDispatcher->dispatch($event, 'workflow.sms.send');

            return ['success' => true, 'message' => sprintf('SMS event dispatched to %s.', $phone)];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow SMS action failed.', ['exception' => $e]);

            return ['success' => false, 'message' => 'SMS dispatch failed. See server logs for details.'];
        }
    }

    private function resolvePhone(WorkflowContext $context): string
    {
        $subject = $context->getSubject();

        // Try customer phone
        $customer = null;
        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            $customer = $subject->getCustomer();
        } elseif (method_exists($subject, 'getPhoneNumber')) {
            $customer = $subject;
        }

        if ($customer !== null && method_exists($customer, 'getPhoneNumber')) {
            $phone = $customer->getPhoneNumber();
            if ($phone !== null && $phone !== '') {
                return $phone;
            }
        }

        // Try shipping address phone
        if ($subject instanceof OrderInterface) {
            $address = $subject->getShippingAddress();
            if ($address !== null && method_exists($address, 'getPhoneNumber')) {
                $phone = $address->getPhoneNumber();
                if ($phone !== null && $phone !== '') {
                    return $phone;
                }
            }
        }

        return '';
    }
}
