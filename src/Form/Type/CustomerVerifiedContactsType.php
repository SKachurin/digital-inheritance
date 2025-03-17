<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\CommandHandler\Customer\Delete\CustomerDeleteInputDto;
use App\Entity\Contact;
use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerVerifiedContactsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Customer $customer */
        $customer = $options['customer'];

        $builder
            ->add('contactType', ChoiceType::class, [
                'choices' => array_combine(
                    $customer->getContacts()
                        ->filter(fn(Contact $c) => $c->getIsVerified())
                        ->map(fn(Contact $c) => $c->getContactTypeEnum())->toArray(),  // Values
                    $customer->getContacts()
                        ->filter(fn(Contact $c) => $c->getIsVerified())
                        ->map(fn(Contact $c) => strtoupper($c->getContactTypeEnum()))->toArray()
                ),
                'label' => 'form.label.choose_type',
                'expanded' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.label.choose_type_button',
                'attr' => ['class' => 'btn btn-outline-dark'],
            ]);;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerDeleteInputDto::class,
            'customer' => null
        ]);
        $resolver->setAllowedTypes('customer', [Customer::class, 'null']);
    }
}
