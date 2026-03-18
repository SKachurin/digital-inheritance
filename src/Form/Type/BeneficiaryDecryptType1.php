<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Decrypt\BeneficiaryNoteDecryptOutputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BeneficiaryDecryptType1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerText', TextareaType::class, [
                'label' => 'form.label.your_text',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'form.attr.placeholder.fail_decrypt',
                    'readonly' => true,
                ],
            ])
            ->add('customerTextKMS2', TextareaType::class, [
                'label' => 'form.label.your_text_kms2',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'form.attr.placeholder.fail_decrypt',
                    'readonly' => true,
                ],
            ])
            ->add('customerTextKMS3', TextareaType::class, [
                'label' => 'form.label.your_text_kms3',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'form.attr.placeholder.fail_decrypt',
                    'readonly' => true,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BeneficiaryNoteDecryptOutputDto::class,
        ]);
    }
}