<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Edit\NoteEditOutputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

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
            ->add('customerTextKms2', TextareaType::class, [
                'label' => 'form.label.your_text_kms2',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'form.attr.placeholder.fail_decrypt',
                ],
            ])
            ->add('customerTextKms3', TextareaType::class, [
                'label' => 'form.label.your_text_kms3',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'form.attr.placeholder.fail_decrypt',
                ],
            ])
//            ->add('customerFirstQuestion', TextType::class, [
//                'label' => 'form.label.customer_first_question',
//                'required' => false,
//                'attr' => ['placeholder' => 'form.attr.placeholder.for_question'],
//            ])
//            ->add('customerFirstQuestionAnswer', RepeatedType::class, [
//                'type' => PasswordType::class,
//                'invalid_message' => 'The password fields must match.',
//                'required' => true,
//                'first_options'  => ['label' => 'form.label.customer_first_question_answer'],
//                'second_options' => ['label' => 'form.label.customer_first_question_answer_repeat'],
//            ])
//            ->add('customerSecondQuestion', TextType::class, [
//                'label' => 'form.label.customer_second_question',
//                'required' => false,
//                'attr' => ['placeholder' => 'form.attr.placeholder.for_question'],
//            ])
//            ->add('customerSecondQuestionAnswer', RepeatedType::class, [
//                'type' => PasswordType::class,
//                'invalid_message' => 'The password fields must match.',
//                'required' => false,
//                'first_options'  => ['label' => 'form.label.customer_second_question_answer'],
//                'second_options' => ['label' => 'form.label.customer_second_question_answer_repeat'],
//            ])
//        ;
//
//        if ($beneficiary) {
//            $builder
//                ->add('beneficiaryFirstQuestion', TextType::class, [
//                    'label' => 'form.label.beneficiary_first_question',
//                    'required' => false,
//                    'attr' => ['placeholder' => 'form.attr.placeholder.for_question'],
//                ])
//                ->add('beneficiaryFirstQuestionAnswer', RepeatedType::class, [
//                    'type' => PasswordType::class,
//                    'invalid_message' => 'The password fields must match.',
//                    'required' => true,
//                    'first_options'  => ['label' => 'form.label.beneficiary_first_question_answer'],
//                    'second_options' => ['label' => 'form.label.beneficiary_first_question_answer_repeat'],
//                ])
//                ->add('beneficiarySecondQuestion', TextType::class, [
//                    'label' => 'form.label.beneficiary_second_question',
//                    'required' => false,
//                    'attr' => ['placeholder' => 'form.attr.placeholder.for_question'],
//                ])
//                ->add('beneficiarySecondQuestionAnswer', RepeatedType::class, [
//                    'type' => PasswordType::class,
//                    'invalid_message' => 'The password fields must match.',
//                    'required' => false,
//                    'first_options'  => ['label' => 'form.label.beneficiary_second_question_answer'],
//                    'second_options' => ['label' => 'form.label.beneficiary_second_question_answer_repeat'],
//                ])
//            ;
//        }
//
//        // hidden fields - EXACTLY like create flow
//        $hidden = fn() => [
//            'mapped' => true,
//            'required' => false,
//            'constraints' => [new Length(['max' => 20000])],
//        ];
//
//        $builder
//            ->add('frontendEncrypted', HiddenType::class, [
//                'mapped' => true,
//                'required' => false,
//                'empty_data' => '0',
//            ])
//
//            ->add('customerTextAnswerOne', HiddenType::class, $hidden())
//            ->add('customerTextAnswerOneKms2', HiddenType::class, $hidden())
//            ->add('customerTextAnswerOneKms3', HiddenType::class, $hidden())
//
//            ->add('customerTextAnswerTwo', HiddenType::class, $hidden())
//            ->add('customerTextAnswerTwoKms2', HiddenType::class, $hidden())
//            ->add('customerTextAnswerTwoKms3', HiddenType::class, $hidden())
//        ;
//
//        if ($beneficiary) {
//            $builder
//                ->add('beneficiaryTextAnswerOne', HiddenType::class, $hidden())
//                ->add('beneficiaryTextAnswerOneKms2', HiddenType::class, $hidden())
//                ->add('beneficiaryTextAnswerOneKms3', HiddenType::class, $hidden())
//
//                ->add('beneficiaryTextAnswerTwo', HiddenType::class, $hidden())
//                ->add('beneficiaryTextAnswerTwoKms2', HiddenType::class, $hidden())
//                ->add('beneficiaryTextAnswerTwoKms3', HiddenType::class, $hidden())
//            ;
//        }

//        $builder->add('submit', SubmitType::class, [
//            'label' => 'form.label.edit_envelope',
//            'attr' => ['class' => 'btn btn-outline-dark'],
//        ])
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