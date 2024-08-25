<?php

namespace App\Form\Type;

use App\CommandHandler\Contact\Create\ContactCreateInputDto;
use App\CommandHandler\Contact\Edit\ContactEditInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ContactCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $type = $options['type'];
        $customer = $options['customer'];

        $builder
            ->add('contactTypeEnum', TextareaType::class, [
                'label' => 'contact_type_enum',
                'attr' => [
                    'readonly' => true
                ],
                'data' => $type,
            ])
        ;
        if ($type === 'phone') {
            $builder
                ->add('countryCode', TextareaType::class, [
                    'label' => 'countryCode',
                    'required' => false,
                    'attr' => [
//                        'readonly' => true
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
                'label' => 'is_verified',
                'required' => false,
                'mapped' => false, // Temporarily unmapped for transformation
                'attr' => [
//                    'readonly' => true,
                ],
                'data' => $options['isVerified'] ? 'Yes' : 'No',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'create_contact',
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactCreateInputDto::class,
            'type' => null,
            'customer' => null,
            'isVerified' => false,
        ]);
    }
}
