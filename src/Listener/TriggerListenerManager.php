<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Listener;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowTriggerLog;
use Abderrahim\SyliusWorkflowPlugin\Enum\TriggerEvent;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowExecutor;
use Abderrahim\SyliusWorkflowPlugin\Repository\WorkflowCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;

#[AsEventListener(event: 'sylius.order.post_complete', method: 'onOrderCompleted')]
#[AsEventListener(event: 'sylius.order.post_cancel', method: 'onOrderCancelled')]
#[AsEventListener(event: 'sylius.order.post_ship', method: 'onOrderShipped')]
#[AsEventListener(event: 'sylius.customer.post_register', method: 'onCustomerRegistered')]
#[AsEventListener(event: 'sylius.payment.post_failure', method: 'onPaymentFailed')]
final class TriggerListenerManager
{
    public function __construct(
        private readonly WorkflowCampaignRepository $campaignRepository,
        private readonly WorkflowExecutor $executor,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onOrderCompleted(GenericEvent $event): void
    {
        $this->handleEvent(TriggerEvent::OrderCompleted, $event);
    }

    public function onOrderCancelled(GenericEvent $event): void
    {
        $this->handleEvent(TriggerEvent::OrderCancelled, $event);
    }

    public function onOrderShipped(GenericEvent $event): void
    {
        $this->handleEvent(TriggerEvent::OrderShipped, $event);
    }

    public function onCustomerRegistered(GenericEvent $event): void
    {
        $this->handleEvent(TriggerEvent::CustomerRegistered, $event);
    }

    public function onPaymentFailed(GenericEvent $event): void
    {
        $this->handleEvent(TriggerEvent::PaymentFailed, $event);
    }

    private function handleEvent(TriggerEvent $triggerEvent, GenericEvent $event): void
    {
        $subject = $event->getSubject();
        if (!\is_object($subject)) {
            return;
        }

        $subjectId = method_exists($subject, 'getId') ? (int) $subject->getId() : 0;
        $channel = $this->resolveChannel($subject);

        $campaigns = $this->campaignRepository->findActiveByTriggerEvent($triggerEvent->value);

        foreach ($campaigns as $campaign) {
            try {
                // Deduplication check
                $dedupKey = WorkflowTriggerLog::generateDedupKey(
                    $campaign->getId(),
                    $triggerEvent->value,
                    $subjectId,
                );

                $existing = $this->entityManager->getRepository(WorkflowTriggerLog::class)
                    ->findOneBy(['dedupKey' => $dedupKey]);

                if ($existing !== null) {
                    $this->logger->debug('Workflow trigger deduplicated.', [
                        'campaign' => $campaign->getId(),
                        'event' => $triggerEvent->value,
                        'subjectId' => $subjectId,
                    ]);
                    continue;
                }

                // Create trigger log — catch duplicate key race condition
                $triggerLog = new WorkflowTriggerLog();
                $triggerLog->setCampaign($campaign);
                $triggerLog->setEventName($triggerEvent->value);
                $triggerLog->setSubjectId($subjectId);
                $triggerLog->setDedupKey($dedupKey);
                $this->entityManager->persist($triggerLog);

                try {
                    $this->entityManager->flush();
                } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                    $this->logger->debug('Workflow trigger deduplicated (race condition caught).', [
                        'campaign' => $campaign->getId(),
                    ]);
                    continue;
                }

                // Execute workflow
                $context = new WorkflowContext(
                    event: $triggerEvent->value,
                    subject: $subject,
                    channel: $channel,
                );

                $this->executor->execute($campaign, $context);
            } catch (\Throwable $e) {
                $this->logger->error('Workflow execution failed.', [
                    'campaign' => $campaign->getId(),
                    'event' => $triggerEvent->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function resolveChannel(object $subject): string
    {
        if ($subject instanceof OrderInterface && $subject->getChannel() !== null) {
            return $subject->getChannel()->getCode() ?? 'default';
        }

        return 'default';
    }
}
