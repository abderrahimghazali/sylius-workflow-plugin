<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Template\WorkflowTemplateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final class TemplateController
{
    /** @var iterable<WorkflowTemplateInterface> */
    private iterable $templates;

    public function __construct(
        iterable $templates,
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
        private readonly RouterInterface $router,
    ) {
        $this->templates = $templates;
    }

    public function index(): Response
    {
        $templateList = [];
        foreach ($this->templates as $template) {
            $templateList[] = $template;
        }

        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/template/index.html.twig', [
            'templates' => $templateList,
        ]);

        return new Response($content);
    }

    public function install(string $templateClass): Response
    {
        $template = null;
        foreach ($this->templates as $t) {
            if ($t::class === $templateClass) {
                $template = $t;
                break;
            }
        }

        if ($template === null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Template not found.');
        }

        $campaign = new WorkflowCampaign();
        $campaign->setName($template->getName());
        $campaign->setDescription($template->getDescription());
        $campaign->setStatus(WorkflowStatus::Draft);
        $campaign->setGraph($template->getGraph());

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return new RedirectResponse(
            $this->router->generate('sylius_workflow_admin_canvas', ['id' => $campaign->getId()])
        );
    }
}
