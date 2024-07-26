<?php

namespace App\Form\Type;

use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\DataTransformer\EnumToStringTransformer;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'form.label.customer_name',
                'help' => 'form.help.customer_name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter something to address you.',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 64,
                        'minMessage' => 'Your name should be at least {{ limit }} characters long.',
                        'maxMessage' => 'Your name should not be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'form.label.customer_email',
                'help' => 'form.help.customer_email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email address.',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])
            ->add('customerSecondEmail', EmailType::class, [
                'label' => 'form.label.customer_second_email',
                'help' => 'form.help.customer_second_email',
                'required' => false,
                'constraints' => [
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])
            ->add('customerFullName', TextType::class, [
                'label' => 'form.label.customer_full_name',
                'help' => 'form.help.customer_full_name',
                'required' => false,
            ])
            ->add('customerCountryCode', TextType::class, [
                'label' => 'form.label.customer_country_code',
                'help' => 'form.help.customer_country_code',
            ])
            ->add('customerFirstPhone', TextType::class, [
                'label' => 'form.label.customer_first_phone',
                'help' => 'form.help.customer_first_phone',
                'required' => false,
            ])
            ->add('customerSecondPhone', TextType::class, [
                'label' => 'form.label.customer_second_phone',
                'help' => 'form.help.customer_second_phone',
                'required' => false,
            ])
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
                'first_options'  => ['label' => 'form.label.customer_first_question_answer'],
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
                'first_options'  => ['label' => 'form.label.customer_second_question_answer'],
                'second_options' => ['label' => 'form.label.customer_second_question_answer_repeat'],
            ])
            ->add('customerSocialApp',  ChoiceType::class, [
                'label' => 'form.label.customer_social_app',
                'choices' => [
                    'form.choice.customer_social_app.none' => 'none',
                    'form.choice.customer_social_app.facebook' => 'facebook',
                    'form.choice.customer_social_app.instagram' => 'instagram',
                    'form.choice.customer_social_app.vkcom' => 'vk.com',
                    'form.choice.customer_social_app.telegram' => 'telegram',
                ],
                'required' => true,
            ])
            ->add('customerOkayPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'help' => '',
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => ['label' => 'form.label.customer_okay_password'],
                'second_options' => ['label' => 'form.label.customer_okay_password_repeat'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => ['label' => 'form.label.password'],
                'second_options' => ['label' => 'form.label.password_repeat'],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
            ]);
        $builder->get('customerSocialApp')->addModelTransformer(new EnumToStringTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerCreateInputDto::class
        ]);
    }
}

