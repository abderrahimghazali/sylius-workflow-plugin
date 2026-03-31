<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TrackEventAction implements ActionInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'track_event';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $eventName = $config['event_name'] ?? '';
        if ($eventName === '') {
            return ['success' => false, 'message' => 'No event name specified.'];
        }

        $properties = $config['properties'] ?? '';
        $parsedProperties = [];
        if ($properties !== '') {
            $decoded = json_decode($properties, true);
            if (\is_array($decoded)) {
                $parsedProperties = $decoded;
            }
        }

        $subject = $context->getSubject();
        $payload = [
            'event_name' => $eventName,
            'properties' => $parsedProperties,
            'workflow_event' => $context->getEvent(),
            'channel' => $context->getChannel(),
            'subject_id' => method_exists($subject, 'getId') ? $subject->getId() : null,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ];

        // Add customer email if available
        $customer = null;
        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            $customer = $subject->getCustomer();
        } elseif (method_exists($subject, 'getEmail')) {
            $customer = $subject;
        }

        if ($customer !== null && method_exists($customer, 'getEmail')) {
            $payload['customer_email'] = $customer->getEmail();
        }

        try {
            $event = new GenericEvent($context->getSubject(), $payload);
            $this->eventDispatcher->dispatch($event, 'workflow.analytics.track');

            return ['success' => true, 'message' => sprintf('Event "%s" tracked.', $eventName)];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow track event action failed.', ['exception' => $e]);

            return ['success' => false, 'message' => 'Event tracking failed. See server logs for details.'];
        }
    }
}
