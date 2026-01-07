<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Create\NoteCreateInputDto;
use App\Entity\Beneficiary;
use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class NoteCreationType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!isset($options['customerId']) || !$options['customerId']) {
            throw new \InvalidArgumentException('The customerId option is required.');
        }

        $customerId = $options['customerId'];
        $decodedNote = $options['decodedNote'];


        $builder

            ->add('customerText', TextareaType::class, [
                'label' => 'form.label.note',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Customer Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'rows' => 10,
                    'style' => 'height: 15em',
                    'maxlength' => 10000,
                ],
            ]);

        if (!$decodedNote) {
            $builder
                ->add('customerFirstQuestion', TextType::class, [
                    'label' => 'form.label.customer_first_question',
                    'help' => 'form.help.customer_first_question',
                    'required' => true,
                ])
                ->add('customerFirstQuestionAnswer', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'help' => '',
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => true,
                    'first_options' => ['label' => 'form.label.customer_first_question_answer'],
                    'second_options' => ['label' => 'form.label.customer_first_question_answer_repeat'],
                ])
                ->add('customerSecondQuestion', TextType::class, [
                    'label' => 'form.label.customer_second_question',
                    'help' => 'form.help.customer_second_question',
                    'required' => false,
                ])
                ->add('customerSecondQuestionAnswer', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'help' => 'form.help.customer_second_question_answer',
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => false,
                    'first_options' => ['label' => 'form.label.customer_second_question_answer'],
                    'second_options' => ['label' => 'form.label.customer_second_question_answer_repeat'],
                ])

                ->add('beneficiaryFirstQuestion', TextType::class, [
                    'label' => 'form.label.beneficiary_first_question',
                    'help' => 'form.help.beneficiary_first_question',
                    'required' => true,
                ])
                ->add('beneficiaryFirstQuestionAnswer', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'help' => '',
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => true,
                    'first_options'  => ['label' => 'form.label.beneficiary_first_question_answer'],
                    'second_options' => ['label' => 'form.label.beneficiary_first_question_answer_repeat'],
                ])
                ->add('beneficiarySecondQuestion', TextType::class, [
                    'label' => 'form.label.beneficiary_second_question',
                    'help' => 'form.help.beneficiary_second_question',
                    'required' => false,
                ])
                ->add('beneficiarySecondQuestionAnswer', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'help' => 'form.help.beneficiary_second_question_answer',
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => false,
                    'first_options'  => ['label' => 'form.label.beneficiary_second_question_answer'],
                    'second_options' => ['label' => 'form.label.beneficiary_second_question_answer_repeat'],
                ])
            ;
        }

        if ($decodedNote) {
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
                        'placeholder' => $decodedNote//'This field is intended for encrypted data.'
                    ]
                ])
                ->add('customerTextAnswerTwo', TextareaType::class, [
                    'label' => 'Customer Text Answer Two',
                    'required' => false,
                    'constraints' => [
                        new Length([
                            'max' => 10000,
                            'maxMessage' => 'Customer Text Answer Two cannot be longer than {{ limit }} characters.',
                        ]),
                    ],
                    'attr' => [
                        'placeholder' => 'This field is intended for encrypted data.'
                    ]
                ])
                ->add('beneficiaryTextAnswerOne', TextareaType::class, [
                    'label' => 'Beneficiary Text Answer One',
                    'required' => false,
                    'constraints' => [
                        new Length([
                            'max' => 10000,
                            'maxMessage' => 'Beneficiary Text Answer One cannot be longer than {{ limit }} characters.',
                        ]),
                    ],
                    'attr' => [
                        'placeholder' => 'This field is intended for encrypted data.'
                    ]
                ])
                ->add('beneficiaryTextAnswerTwo', TextareaType::class, [
                    'label' => 'Beneficiary Text Answer Two',
                    'required' => false,
                    'constraints' => [
                        new Length([
                            'max' => 10000,
                            'maxMessage' => 'Beneficiary Text Answer Two cannot be longer than {{ limit }} characters.',
                        ]),
                    ],
                    'attr' => [
                        'placeholder' => 'This field is intended for encrypted data.'
                    ]
                ]);
        }

        /**
         * Hidden, mapped fields to receive browser-encrypted replicas + flag.
         * Names MUST match NoteCreateInputDto properties.
         */
        $hidden = fn() => [
            'mapped' => true,
            'required' => false,
            'constraints' => [new Length(['max' => 20000])],
        ];

        $builder
            ->add('frontendEncrypted', HiddenType::class, [
                'mapped' => true,
                'required' => false,
                'empty_data' => '0',
            ])

            // a1 (customer first answer) – 3 replicas
            ->add('customerTextAnswerOne', HiddenType::class, $hidden())
            ->add('customerTextAnswerOneKms2', HiddenType::class, $hidden())
            ->add('customerTextAnswerOneKms3', HiddenType::class, $hidden())

            // a2 (customer second answer) – 3 replicas
            ->add('customerTextAnswerTwo', HiddenType::class, $hidden())
            ->add('customerTextAnswerTwoKms2', HiddenType::class, $hidden())
            ->add('customerTextAnswerTwoKms3', HiddenType::class, $hidden())

            // b1 (beneficiary first answer) – 3 replicas
            ->add('beneficiaryTextAnswerOne', HiddenType::class, $hidden())
            ->add('beneficiaryTextAnswerOneKms2', HiddenType::class, $hidden())
            ->add('beneficiaryTextAnswerOneKms3', HiddenType::class, $hidden())

            // b2 (beneficiary second answer) – 3 replicas
            ->add('beneficiaryTextAnswerTwo', HiddenType::class, $hidden())
            ->add('beneficiaryTextAnswerTwoKms2', HiddenType::class, $hidden())
            ->add('beneficiaryTextAnswerTwoKms3', HiddenType::class, $hidden())
        ;

        $builder->add('submit', SubmitType::class, [
            'label' => 'form.label.note_create',
            'attr' => ['class' => 'btn btn-outline-dark'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NoteCreateInputDto::class,
            'customerId' => null,
            'decodedNote' => null
        ]);
    }
}
