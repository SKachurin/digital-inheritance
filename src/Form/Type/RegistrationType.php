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
use Symfony\Component\Validator\Constraints\Regex;

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
                        'message' => 'form.constraints.not_blank',
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
                        'message' => 'form.constraints.not_blank',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])

//            ->add('customerFullName', TextType::class, [
//                'label' => 'form.label.customer_full_name',
//                'help' => 'form.help.customer_full_name',
//                'required' => false,
//            ])
//
//            ->add('customerOkayPassword', RepeatedType::class, [
//                'type' => PasswordType::class,
//                'help' => '',
//                'invalid_message' => 'The password fields must match.',
//                'options' => ['attr' => ['class' => 'password-field']],
//                'required' => true,
//                'first_options'  => ['label' => 'form.label.customer_okay_password'],
//                'second_options' => ['label' => 'form.label.customer_okay_password_repeat'],
//            ])
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
//        $builder->get('customerSocialApp')->addModelTransformer(new EnumToStringTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerCreateInputDto::class
        ]);
    }
}

