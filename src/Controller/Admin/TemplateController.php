<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Controller\Admin;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Template\WorkflowTemplateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final class TemplateController
{
    /** @var array<string, WorkflowTemplateInterface> */
    private array $templatesBySlug = [];

    public function __construct(
        iterable $templates,
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
        private readonly RouterInterface $router,
    ) {
        foreach ($templates as $template) {
            $this->templatesBySlug[self::slugify($template)] = $template;
        }
    }

    public function index(): Response
    {
        $content = $this->twig->render('@SyliusWorkflowPlugin/admin/template/index.html.twig', [
            'templates' => $this->templatesBySlug,
        ]);

        return new Response($content);
    }

    #[IsCsrfTokenValid('workflow_template_install', tokenKey: '_csrf_token')]
    public function install(string $templateSlug, Request $request): Response
    {
        $template = $this->templatesBySlug[$templateSlug] ?? null;

        if ($template === null) {
            throw new NotFoundHttpException('Template not found.');
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

    public static function slugify(WorkflowTemplateInterface $template): string
    {
        return $template->getCategory() . '-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($template->getName()));
    }
}
