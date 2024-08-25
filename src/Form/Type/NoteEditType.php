<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Decrypt\NoteDecryptInputDto;
use App\CommandHandler\Note\Edit\NoteEditInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NoteEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $beneficiary = $options['beneficiary'];

        $builder
            ->add('customerTextAnswerOne', TextareaType::class, [
                'label' => 'Customer Text Answer One',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Customer Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                    'readonly' => true
                ]
            ])
            ->add('customerTextAnswerTwo', TextareaType::class, [
                'label' => 'Customer Text Answer Two',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Customer Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                    'readonly' => true
                ]
            ]);
        if ($beneficiary) {
            $builder
                ->add('beneficiaryTextAnswerOne', TextareaType::class, [
                    'label' => 'beneficiary Text Answer One',
                    'required' => false,
                    'constraints' => [
                        new Length([
                            'max' => 10000,
                            'maxMessage' => 'beneficiary Text Answer One cannot be longer than {{ limit }} characters.',
                        ]),
                    ],
                    'attr' => [
                        'placeholder' => 'This field is intended for encrypted data.',
                        'rows' => 10,
                        'style' => 'height: 15em',
                        'readonly' => true
                    ]
                ])
                ->add('beneficiaryTextAnswerTwo', TextareaType::class, [
                    'label' => 'beneficiary Text Answer Two',
                    'required' => false,
                    'constraints' => [
                        new Length([
                            'max' => 10000,
                            'maxMessage' => 'beneficiary Text Answer One cannot be longer than {{ limit }} characters.',
                        ]),
                    ],
                    'attr' => [
                        'placeholder' => 'This field is intended for encrypted data.',
                        'rows' => 10,
                        'style' => 'height: 15em',
                        'readonly' => true
                    ]
                ]);
        }
        $builder
            ->add('customerFirstQuestion', TextType::class, [
                'label' => 'Customer First Question',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Placeholder for Question',
                    'readonly' => true
                ]

            ])
            ->add('customerFirstQuestionAnswer', TextType::class, [
                'label' => 'Customer First Question Answer',
                'required' => false,
            ])
            ->add('customerSecondQuestion', TextType::class, [
                'label' => 'Customer Second Question',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Placeholder for Question',
                    'readonly' => true
                ]
            ])
            ->add('customerSecondQuestionAnswer', TextType::class, [
                'label' => 'Customer Second Question Answer',
                'required' => false,
            ]);

        if ($beneficiary) {
            $builder
                ->add('beneficiaryFirstQuestion', TextType::class, [
                    'label' => 'Beneficiary First Question',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Placeholder for Question',
                        'readonly' => true
                    ]

                ])
                ->add('beneficiaryFirstQuestionAnswer', TextType::class, [
                    'label' => 'Beneficiary First Question Answer',
                    'required' => false,
                ])
                ->add('beneficiarySecondQuestion', TextType::class, [
                    'label' => 'Beneficiary Second Question',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Placeholder for Question',
                        'readonly' => true
                    ]
                ])
                ->add('beneficiarySecondQuestionAnswer', TextType::class, [
                    'label' => 'Beneficiary Second Question Answer',
                    'required' => false,
                ]);
        }

        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'Try to decrypt this with my answer',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NoteEditInputDto::class,
            'beneficiary' => null,
        ]);
    }
}
