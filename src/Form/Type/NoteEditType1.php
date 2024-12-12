<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Decrypt\NoteDecryptOutputDto;
use App\CommandHandler\Note\Edit\NoteEditOutputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteEditType1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $beneficiary = $options['beneficiary'];

        $builder
            ->add('customerText', TextareaType::class, [
                'label' => 'form.label.your_text',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'form.attr.placeholder.fail_decrypt',
                ],
            ])
            ->add('customerFirstQuestion', TextType::class, [
                'label' => 'form.label.customer_first_question',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.attr.placeholder.for_question',
//                    'readonly' => true
                ]

            ])
            ->add('customerFirstQuestionAnswer', TextType::class, [
                'label' => 'form.label.customer_first_question_answer',
                'required' => false,
            ])
            ->add('customerSecondQuestion', TextType::class, [
                'label' => 'form.label.customer_second_question',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.attr.placeholder.for_question',
//                    'readonly' => true
                ]
            ])
            ->add('customerSecondQuestionAnswer', TextType::class, [
                'label' => 'form.label.customer_second_question_answer',
                'required' => false,
            ])
        ;

        if ($beneficiary) {
            $builder
                ->add('beneficiaryFirstQuestion', TextType::class, [
                    'label' => 'form.label.beneficiary_first_question',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'form.attr.placeholder.for_question',
//                        'readonly' => true
                    ]

                ])
                ->add('beneficiaryFirstQuestionAnswer', TextType::class, [
                    'label' => 'form.label.beneficiary_first_question_answer',
                    'required' => false,
                ])
                ->add('beneficiarySecondQuestion', TextType::class, [
                    'label' => 'form.label.beneficiary_second_question',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'form.attr.placeholder.for_question',
//                        'readonly' => true
                    ]
                ])
                ->add('beneficiarySecondQuestionAnswer', TextType::class, [
                    'label' => 'form.label.beneficiary_second_question_answer',
                    'required' => false,
                ])
            ;
        }
        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'form.label.edit_envelope',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NoteEditOutputDto::class,
            'beneficiary' => null,
        ]);
    }
}
