<?php

namespace App\Form\Type;

use App\CommandHandler\Customer\Compare\CustomerCompareOutputDto1;
use App\CommandHandler\Customer\Compare\CustomerCompareOutputDto2;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\DataTransformer\EnumToStringTransformer;

class DemonstrationType2 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'form.label.customer_name',
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'form.label.customer_email',
            ])
            ->add('customerSecondEmail', EmailType::class, [
                'label' => 'form.label.customer_second_email',
            ])
            ->add('customerFullName', TextType::class, [
                'label' => 'form.label.customer_full_name',
                'required' => false,
            ])
            ->add('customerCountryCode', TextType::class, [
                'label' => 'form.label.customer_country_code',
            ])
            ->add('customerFirstPhone', TextType::class, [
                'label' => 'form.label.customer_first_phone',
                'required' => false,
            ])
            ->add('customerSecondPhone', TextType::class, [
                'label' => 'form.label.customer_second_phone',
                'required' => false,
            ])
            ->add('customerFirstQuestion', TextType::class, [
                'label' => 'form.label.customer_first_question',
                'required' => false,
            ])
            ->add('customerFirstQuestionAnswer', TextType::class, [
                'label' => 'form.label.customer_first_question_answer',
                'required' => false,
            ])
            ->add('customerSecondQuestion', TextType::class, [
                'label' => 'form.label.customer_second_question',
                'required' => false,
            ])
            ->add('customerSecondQuestionAnswer', TextType::class, [
                'label' => 'form.label.customer_second_question_answer',
                'required' => false,
            ])
            ->add('customerSocialApp',  TextType::class, [
                'label' => 'form.label.customer_social_app',
                'required' => false,
            ])
            ->add('customerOkayPassword', TextType::class, [
                'label' => 'form.label.customer_okay_password',
                'required' => false,
            ])
            ->add('password', TextType::class, [
                'label' => 'form.label.password',
                'required' => false,
            ]);
        $builder->get('customerSocialApp')->addModelTransformer(new EnumToStringTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerCompareOutputDto2::class
        ]);
    }
}

