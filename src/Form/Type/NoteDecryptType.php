<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Decrypt\NoteDecryptInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NoteDecryptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerTextAnswerOne', TextareaType::class, [
                'label' => 'Customer Text Answer One',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Customer Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                ]
            ])
            ->add('customerTextAnswerTwo', TextareaType::class, [
                'label' => 'Customer Text Answer Two',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Customer Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                ]
            ])
            ->add('customerFirstQuestion', TextType::class, [
                'label' => 'Customer First Question',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Placeholder for Question',
                ]

            ])
            ->add('customerFirstQuestionAnswer', TextType::class, [
                'label' => 'Customer First Question Answer',
                'required' => false,
            ])
            ->add('customerSecondQuestion', TextType::class, [
                'label' => 'Customer Second Question',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Placeholder for Question',
                ]
            ])
            ->add('customerSecondQuestionAnswer', TextType::class, [
                'label' => 'Customer Second Question Answer',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Try to decrypt this with my answer',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NoteDecryptInputDto::class,
        ]);
    }
}
