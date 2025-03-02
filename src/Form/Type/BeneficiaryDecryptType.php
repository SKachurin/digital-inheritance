<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Decrypt\BeneficiaryNoteDecryptInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class BeneficiaryDecryptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('beneficiaryTextAnswerOne', TextareaType::class, [
                'label' => 'form.label.text_encoded_beneficiary_first',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'beneficiary Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'form.attr.placeholder.for_encrypted_text',
                    'rows' => 10,
                    'style' => 'height: 15em',
                    'readonly' => true
                ]
            ])
            ->add('beneficiaryTextAnswerTwo', TextareaType::class, [
                'label' => 'form.label.text_encoded_beneficiary_second',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'form.constraints.too_long_1000',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'form.attr.placeholder.for_encrypted_text',
                    'rows' => 10,
                    'style' => 'height: 15em',
                    'readonly' => true
                ]
            ])
            ->add('beneficiaryFirstQuestion', TextType::class, [
                'label' => 'form.label.beneficiary_first_question',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.attr.placeholder.for_question',
                    'readonly' => true
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
                    'readonly' => true
                ]
            ])
            ->add('beneficiarySecondQuestionAnswer', TextType::class, [
                'label' => 'form.label.beneficiary_second_question_answer',
                'required' => false,
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'form.label.try_decrypt',
                'attr' => ['class' => 'btn btn-outline-dark'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BeneficiaryNoteDecryptInputDto::class,
        ]);
    }
}
