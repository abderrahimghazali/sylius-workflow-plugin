<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Form\Type\WorkflowCampaignType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[AsController]
#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final class WorkflowCampaignController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function create(Request $request): Response
    {
        $campaign = new WorkflowCampaign();
        $form = $this->createForm(WorkflowCampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaign->setStatus(WorkflowStatus::Draft);
            $campaign->setGraph([
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'type' => 'trigger',
                        'position' => ['x' => 40, 'y' => 200],
                        'config' => ['event' => 'order.completed'],
                    ],
                ],
                'edges' => [],
            ]);

            $this->entityManager->persist($campaign);
            $this->entityManager->flush();

            return $this->redirectToRoute('sylius_workflow_admin_canvas', ['id' => $campaign->getId()]);
        }

        return $this->render('@SyliusWorkflowPlugin/admin/workflow_campaign/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function edit(int $id, Request $request): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $form = $this->createForm(WorkflowCampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'workflow.flash.updated');

            return $this->redirectToRoute('workflow_admin_campaign_edit', ['id' => $campaign->getId()]);
        }

        return $this->render('@SyliusWorkflowPlugin/admin/workflow_campaign/edit.html.twig', [
            'campaign' => $campaign,
            'form' => $form->createView(),
        ]);
    }

    public function editCanvas(int $id): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        return $this->render('@SyliusWorkflowPlugin/admin/workflow_campaign/canvas.html.twig', [
            'campaign' => $campaign,
        ]);
    }

    #[IsCsrfTokenValid('workflow_delete', tokenKey: '_csrf_token')]
    public function delete(int $id, Request $request): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $this->entityManager->remove($campaign);
        $this->entityManager->flush();

        $this->addFlash('success', 'workflow.flash.deleted');

        return $this->redirectToRoute('workflow_admin_campaign_index');
    }

    public function toggleEnabled(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token') ?? $request->query->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('workflow_toggle', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            return new JsonResponse(['error' => 'Campaign not found.'], Response::HTTP_NOT_FOUND);
        }

        $campaign->setEnabled(!$campaign->isEnabled());
        if ($campaign->isEnabled()) {
            $campaign->setStatus(WorkflowStatus::Active);
        } else {
            $campaign->setStatus(WorkflowStatus::Paused);
        }

        $campaign->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $campaign->getId(),
            'enabled' => $campaign->isEnabled(),
            'status' => $campaign->getStatus()->value,
        ]);
    }
}
