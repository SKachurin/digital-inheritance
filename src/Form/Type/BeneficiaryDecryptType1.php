<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Decrypt\BeneficiaryNoteDecryptOutputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BeneficiaryDecryptType1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerText', TextareaType::class, [
                'label' => 'form.label.your_text',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'form.attr.placeholder.fail_decrypt',
                ],
            ])
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
            ->add('submit', SubmitType::class, [
                'label' => 'form.label.edit_envelope',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BeneficiaryNoteDecryptOutputDto::class,
        ]);
    }
}
