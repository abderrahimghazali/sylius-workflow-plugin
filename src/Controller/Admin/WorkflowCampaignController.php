<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Form\Type\WorkflowCampaignType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[AsController]
final class WorkflowCampaignController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
    ) {
    }

    public function create(Request $request): Response
    {
        $campaign = new WorkflowCampaign();
        $form = $this->formFactory->create(WorkflowCampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaign->setStatus(WorkflowStatus::Draft);
            $campaign->setGraph([
                'nodes' => [
                    [
                        'id' => 'node-1',
                        'type' => 'trigger',
                        'position' => ['x' => 300, 'y' => 40],
                        'config' => ['event' => 'order.completed'],
                    ],
                ],
                'edges' => [],
            ]);

            $this->entityManager->persist($campaign);
            $this->entityManager->flush();

            return new RedirectResponse(
                $this->router->generate('sylius_workflow_admin_canvas', ['id' => $campaign->getId()])
            );
        }

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/workflow_campaign/create.html.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }

    public function editCanvas(int $id): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/workflow_campaign/canvas.html.twig', [
            'campaign' => $campaign,
        ]);

        return new Response($content);
    }

    public function delete(int $id, Request $request): Response
    {
        $campaign = $this->entityManager->find(WorkflowCampaign::class, $id);
        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        $this->entityManager->remove($campaign);
        $this->entityManager->flush();

        return new RedirectResponse($this->router->generate('workflow_admin_campaign_index'));
    }

    public function toggleEnabled(int $id): JsonResponse
    {
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
