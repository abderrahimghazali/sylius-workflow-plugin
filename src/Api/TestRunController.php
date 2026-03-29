<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Api;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Graph\DryRunWorkflowExecutor;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class TestRunController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DryRunWorkflowExecutor $dryRunExecutor,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            return new JsonResponse(['error' => 'Campaign not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        if ($payload === null) {
            return new JsonResponse(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $subjectType = $payload['subjectType'] ?? '';
        $subjectId = (int) ($payload['subjectId'] ?? 0);

        if ($subjectType === '' || $subjectId === 0) {
            return new JsonResponse(['error' => 'subjectType and subjectId are required.'], Response::HTTP_BAD_REQUEST);
        }

        $subject = $this->resolveSubject($subjectType, $subjectId);
        if ($subject === null) {
            return new JsonResponse(['error' => sprintf('%s #%d not found.', $subjectType, $subjectId)], Response::HTTP_NOT_FOUND);
        }

        // Determine the trigger event from graph
        $triggerEvent = '';
        foreach ($campaign->getNodes() as $node) {
            if (($node['type'] ?? '') === 'trigger') {
                $triggerEvent = $node['config']['event'] ?? '';
                break;
            }
        }

        $context = new WorkflowContext(
            event: $triggerEvent,
            subject: $subject,
            channel: 'default',
        );

        $result = $this->dryRunExecutor->execute($campaign, $context);

        return new JsonResponse($result);
    }

    private function resolveSubject(string $type, int $id): ?object
    {
        $classMap = [
            'order' => 'Sylius\Component\Core\Model\Order',
            'customer' => 'Sylius\Component\Core\Model\Customer',
        ];

        $class = $classMap[$type] ?? null;
        if ($class === null || !class_exists($class)) {
            return null;
        }

        return $this->entityManager->find($class, $id);
    }
}
