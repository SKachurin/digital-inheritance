<?php

namespace App\Form\Type;

use App\CommandHandler\Beneficiary\Edit\BeneficiaryEditInputDto;
use App\Service\Phone\CountryCallingCodeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BeneficiaryEditType extends AbstractType
{
    public function __construct(
        private readonly CountryCallingCodeProvider $countryCallingCodes,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('beneficiaryName', TextType::class, [
                'label' => 'form.label.beneficiary_name',
                'help' => 'form.help.beneficiary_name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.constraints.not_blank',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 64,
                        'minMessage' => 'The name should be at least {{ limit }} characters long.',
                        'maxMessage' => 'The name should not be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('beneficiaryFullName', TextType::class, [
                'label' => 'form.label.beneficiary_full_name',
                'help' => 'form.help.beneficiary_full_name',
                'required' => false,
            ])
            ->add('beneficiaryLang', ChoiceType::class, [
                'choices' => [
                    'English' => 'en',
                    'Русский' => 'ru',
                    'Español' => 'es',
                ],
                'label' => 'form.label.beneficiary_lang',
            ])
            ->add('customerFullName', TextType::class, [
                'label' => 'form.label.customer_full_name',
                'help' => 'form.help.customer_full_name',
                'required' => false,
            ])
            ->add('beneficiaryEmail', EmailType::class, [
                'label' => 'form.label.beneficiary_email',
                'help' => 'form.help.beneficiary_email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.constraints.not_blank',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])
            ->add('beneficiarySecondEmail', EmailType::class, [
                'label' => 'form.label.beneficiary_second_email',
                'help' => 'form.help.beneficiary_second_email',
                'required' => false,
                'constraints' => [
                    new Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])
            ->add('beneficiaryCountryCode', ChoiceType::class, [
                'label' => 'form.label.beneficiary_country_code',
                'help' => 'form.help.beneficiary_country_code',
                'required' => true,
                'choices' => $this->countryCallingCodes->getChoices(),
                'placeholder' => 'Select country code',
                'choice_translation_domain' => false,
            ])
            ->add('beneficiaryFirstPhone', TextType::class, [
                'label' => 'form.label.beneficiary_first_phone',
                'help' => 'form.help.beneficiary_first_phone',
                'required' => false,
            ])
            ->add('beneficiarySecondPhone', TextType::class, [
                'label' => 'form.label.beneficiary_second_phone',
                'help' => 'form.help.beneficiary_second_phone',
                'required' => false,
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'form.label.edit',
                'attr' => ['class' => 'btn btn-outline-dark'],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BeneficiaryEditInputDto::class,
        ]);
    }
}
