<?php

namespace App\Form\Type;

use App\CommandHandler\Beneficiary\Edit\BeneficiaryEditOutputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BeneficiaryEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('beneficiaryName', TextType::class, [
                'label' => 'form.label.beneficiary_name',
                'help' => 'form.help.beneficiary_name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.constraints.not_blank',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 64,
                        'minMessage' => 'The name should be at least {{ limit }} characters long.',
                        'maxMessage' => 'The name should not be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('beneficiaryFullName', TextType::class, [
                'label' => 'form.label.beneficiary_full_name',
                'help' => 'form.help.beneficiary_full_name',
                'required' => false,
            ])

            ->add('beneficiaryEmail', EmailType::class, [
                'label' => 'form.label.beneficiary_email',
                'help' => 'form.help.beneficiary_email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.constraints.not_blank',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])
            ->add('beneficiarySecondEmail', EmailType::class, [
                'label' => 'form.label.beneficiary_second_email',
                'help' => 'form.help.beneficiary_second_email',
                'required' => false,
                'constraints' => [
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])

            ->add('beneficiaryCountryCode', TextType::class, [
                'label' => 'form.label.beneficiary_country_code',
                'help' => 'form.help.beneficiary_country_code',
            ])
            ->add('beneficiaryFirstPhone', TextType::class, [
                'label' => 'form.label.beneficiary_first_phone',
                'help' => 'form.help.beneficiary_first_phone',
                'required' => false,
            ])
            ->add('beneficiarySecondPhone', TextType::class, [
                'label' => 'form.label.beneficiary_second_phone',
                'help' => 'form.help.beneficiary_second_phone',
                'required' => false,
            ])

//            ->add('beneficiaryFirstQuestion', TextType::class, [
//                'label' => 'form.label.beneficiary_first_question',
//                'help' => 'form.help.beneficiary_first_question',
//                'required' => true,
//            ])
//            ->add('beneficiaryFirstQuestionAnswer', RepeatedType::class, [
//                'type' => PasswordType::class,
//                'help' => '',
//                'invalid_message' => 'The password fields must match.',
//                'options' => ['attr' => ['class' => 'password-field']],
//                'required' => true,
//                'first_options'  => ['label' => 'form.label.beneficiary_first_question_answer'],
//                'second_options' => ['label' => 'form.label.beneficiary_first_question_answer_repeat'],
//            ])
//            ->add('beneficiarySecondQuestion', TextType::class, [
//                'label' => 'form.label.beneficiary_second_question',
//                'help' => 'form.help.beneficiary_second_question',
//                'required' => false,
//            ])
//            ->add('beneficiarySecondQuestionAnswer', RepeatedType::class, [
//                'type' => PasswordType::class,
//                'help' => 'form.help.beneficiary_second_question_answer',
//                'invalid_message' => 'The password fields must match.',
//                'options' => ['attr' => ['class' => 'password-field']],
//                'required' => false,
//                'first_options'  => ['label' => 'form.label.beneficiary_second_question_answer'],
//                'second_options' => ['label' => 'form.label.beneficiary_second_question_answer_repeat'],
//            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.label.edit',
                'attr' => ['class' => 'btn btn-primary'],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BeneficiaryEditOutputDto::class
        ]);
    }
}
