<?php

namespace App\Form\Type;

use App\CommandHandler\Contact\Edit\ContactEditInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ContactEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countryCode = $options['countryCode'];
        $isVerified = $options['isVerified'];

        $builder
            ->add('contactTypeEnum', TextareaType::class, [
                'label' => 'contactTypeEnum',
                'attr' => [
                    'readonly' => true
                ]
            ])
        ;
        if ($countryCode) {
            $builder
                ->add('countryCode', TextareaType::class, [
                    'label' => 'countryCode',
                    'required' => false,
                    'attr' => [
                        'readonly' => true
                    ]
                ])
            ;
        }
        $builder
            ->add('value', TextareaType::class, [
                'label' => 'value',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'cannot be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
        ;

        $builder
            ->add('isVerified', TextType::class, [
                'label' => 'isVerified',
                'required' => false,
                'mapped' => false, // Temporarily unmapped for transformation
                'attr' => [
                    'readonly' => true,
                ],
                'data' => $options['isVerified'] ? 'Yes' : 'No',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'updateContact',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->add('resend_verification', SubmitType::class, [
                'label' => 'resendVerificationCode',
                'attr' => ['class' => 'btn btn-secondary'],
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactEditInputDto::class,
            'countryCode' => null,
            'isVerified' => null
        ]);
    }
}
