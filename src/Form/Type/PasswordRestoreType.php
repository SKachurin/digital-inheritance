<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class PasswordRestoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * https://gemini.google.com/app/80f8d1ee517c2d5e
         */
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.label.your_login',
                'constraints' => [
                    new Email([
                        'message' => 'form.constraints.your_login',
                    ]),
                    new NotBlank([
                        'message' => 'form.constraints.not_blank',
                    ]),
                ],
            ])

            ->add('Submit', SubmitType::class, [
                'label'    => 'form.label.send',
                'attr' => ['class' => 'btn btn-outline-dark'],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ));
    }
}