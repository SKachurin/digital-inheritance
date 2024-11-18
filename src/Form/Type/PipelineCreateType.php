<?php

namespace App\Form\Type;

use App\CommandHandler\Pipeline\Create\PipelineCreateInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PipelineCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $customerActions = $options['customerActions'];

        $builder
            ->add('actions', CollectionType::class, [
                'entry_type' => ActionType::class,
                'entry_options' => [
                    'customerActions' => $customerActions,
                ],
                'allow_add' => true, // Allow adding new actions via the form
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true, //for JS additions
            ])
            ->add('submit_add', SubmitType::class, [
                'label' => 'form.label.action_add',
                'attr' => ['class' => 'btn btn-secondary'],
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'form.label.pipeline_save',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PipelineCreateInputDto::class,
            'customerActions' => [],
        ]);

        $resolver->setAllowedTypes('customerActions', ['array']);
    }
}


