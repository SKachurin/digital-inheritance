<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class PasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'New Password'],
                'second_options' => ['label' => 'Repeat New Password'],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a new password.',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters.',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Reset Password',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }
}
