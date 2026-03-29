<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Form\Type;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WorkflowCampaignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'workflow.ui.name',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'workflow.ui.description',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkflowCampaign::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'workflow_campaign';
    }
}
