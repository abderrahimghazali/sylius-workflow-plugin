<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Api;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowGraphValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class WorkflowGraphController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowGraphValidator $graphValidator,
    ) {
    }

    #[Route(
        path: '/api/v2/admin/workflows/{id}/graph',
        name: 'sylius_workflow_api_save_graph',
        methods: ['POST'],
    )]
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

        $graph = $payload['graph'] ?? null;
        if ($graph === null) {
            return new JsonResponse(['error' => 'Missing "graph" field.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate graph
        $validationErrors = $this->graphValidator->validate($graph);
        if (!empty($validationErrors)) {
            return new JsonResponse(['errors' => $validationErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update campaign
        $campaign->setGraph($graph);
        $campaign->setUpdatedAt(new \DateTimeImmutable());

        if (isset($payload['name']) && \is_string($payload['name']) && $payload['name'] !== '') {
            $campaign->setName($payload['name']);
        }

        if (isset($payload['status'])) {
            $newStatus = WorkflowStatus::tryFrom($payload['status']);
            if ($newStatus !== null) {
                $campaign->setStatus($newStatus);
                $campaign->setEnabled($newStatus === WorkflowStatus::Active);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'id' => $campaign->getId(),
            'status' => $campaign->getStatus()->value,
        ]);
    }
}
