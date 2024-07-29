<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Decrypt\NoteDecryptOutputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteDecryptType1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerCongrats', TextType::class, [
                'label' => 'Congratulations',
                'required' => true,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('customerText', TextareaType::class, [
                'label' => 'Your Text',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'if decrypted your text will appear here.',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NoteDecryptOutputDto::class,
        ]);
    }
}
