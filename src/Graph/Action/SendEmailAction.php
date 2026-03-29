<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class SendEmailAction implements ActionInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $actionType): bool
    {
        return $actionType === 'send_email';
    }

    public function execute(array $config, WorkflowContext $context): array
    {
        $template = $config['template'] ?? '';
        $subject = $config['subject'] ?? 'Notification';
        $recipientEmail = $config['recipient'] ?? $this->resolveRecipientEmail($context);

        if ($recipientEmail === null) {
            return ['success' => false, 'message' => 'No recipient email found.'];
        }

        try {
            $templateVars = $this->buildTemplateVariables($context);
            $body = $this->twig->render($template, $templateVars);

            $email = (new Email())
                ->to($recipientEmail)
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);

            return ['success' => true, 'message' => sprintf('Email sent to %s.', $recipientEmail)];
        } catch (\Throwable $e) {
            $this->logger->error('Workflow email action failed: ' . $e->getMessage(), [
                'template' => $template,
                'recipient' => $recipientEmail,
            ]);

            return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
        }
    }

    private function resolveRecipientEmail(WorkflowContext $context): ?string
    {
        $subject = $context->getSubject();

        if (method_exists($subject, 'getEmail')) {
            return $subject->getEmail();
        }

        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            return $subject->getCustomer()->getEmail();
        }

        return null;
    }

    private function buildTemplateVariables(WorkflowContext $context): array
    {
        $vars = ['context' => $context];
        $subject = $context->getSubject();

        if (method_exists($subject, 'getCustomer') && $subject->getCustomer() !== null) {
            $vars['customer'] = $subject->getCustomer();
        } elseif (method_exists($subject, 'getEmail')) {
            $vars['customer'] = $subject;
        }

        if (method_exists($subject, 'getNumber')) {
            $vars['order'] = $subject;
        }

        $vars['workflow'] = ['name' => $context->get('workflow_name', '')];

        return array_merge($vars, $context->getExtra());
    }
}
