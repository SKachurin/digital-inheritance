<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.label.your_login',
                'constraints' => [
                    new Email([
                        'message' => 'form.constraints.your_login',
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'form.label.login_password',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.constraints.not_blank',
                    ]),
                ],
            ])
            ->add('remember_me', CheckboxType::class, [
                'mapped'=> false,
                'label'    => 'form.label.remember_me',
                'label_attr' => [
                    'class' => 'checkbox-inline checkbox-switch',
                ],
                'required' => false,
            ])
            ->add('g-recaptcha-response', HiddenType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'id' => 'g-recaptcha-response', // ID matches the JS
                    'name' => 'g-recaptcha-response', // Correct name
                    'style' => 'display: none;',
                ],
            ])
            ->add('Submit', SubmitType::class, [
                'attr' => ['class' => 'btn btn-outline-dark'],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ));
    }
}