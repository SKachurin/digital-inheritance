<?php

namespace App\Form\Type;

use App\CommandHandler\Contact\Create\ContactCreateInputDto;
use App\CommandHandler\Contact\Edit\ContactEditInputDto;
use App\Enum\CustomerSocialAppEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

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
        if ($type === 'social') {
            $builder
                ->add('customerSocialApp', TextareaType::class, [
                    'label' => 'form.label.customer_social_app',
                    'attr' => [
                        'readonly' => true
                    ],
                    'data' => CustomerSocialAppEnum::TELEGRAM->value,
//                'choices' => [
//                    'form.choice.customer_social_app.none' => 'none',
//                    'form.choice.customer_social_app.facebook' => 'facebook',
//                    'form.choice.customer_social_app.instagram' => 'instagram',
//                    'form.choice.customer_social_app.vkcom' => 'vk.com',
//                    'form.choice.customer_social_app.telegram' => 'telegram',
//                ],
//                    'required' => true,
                ])
                ->add('value', TextType::class, [
                    'label' => 'form.label.customer_social_app_link',
                    'help' => 'form.help.customer_social_app_link',
                    'required' => true,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter something to address you.', //TODO error to messages
                        ]),
                        new Length([
                            'min' => 2,
                            'max' => 64,
                            'minMessage' => 'Your name should be at least {{ limit }} characters long.',
                            'maxMessage' => 'Your name should not be longer than {{ limit }} characters.',
                        ]),
                        new Regex([
                            'pattern' => '/^(https:\/\/t\.me\/[a-zA-Z0-9_]+|@[a-zA-Z0-9_]+|\+\d{1,5}\s?\d{6,12})$/',
                            'message' => 'You can enter a valid Telegram link (https://t.me/login), username (@login), or phone number (+995 555544433).'
                        ]),
                    ],
                ])
            ;
        } else {
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
        }


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
