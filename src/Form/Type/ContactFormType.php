<?php

    namespace App\Form\Type;

//    use App\Entity\ContactForm; // Assuming you've mapped it as an entity
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\Component\Validator\Constraints\Email;
    use Symfony\Component\Validator\Constraints\Length;
    use Symfony\Component\Validator\Constraints\NotBlank;

    class ContactFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder
                ->add('name', null, [
                    'label' => 'Your Name',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter your name.',
                        ]),
                        new Length([
                            'min' => 2,
                            'max' => 50,
                            'minMessage' => 'Your name should be at least {{ limit }} characters long.',
                            'maxMessage' => 'Your name should not be longer than {{ limit }} characters.',
                        ]),
                    ],
                ])
                ->add('email', null, [
                    'label' => 'Your Email',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter your email address.',
                        ]),
                        new Email([
                            'message' => 'Please enter a valid email address.',
                        ]),
                    ],
                ])
                ->add('subject', null, [
                    'label' => 'Subject',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter a subject.',
                        ]),
                    ],
                ])
                ->add('body', null, [
                    'label' => 'Message',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter your message.',
                        ]),
                    ],
                ])
                ->add('verifyCode', null, [
                    'label' => 'Verification Code', // Adapt for reCAPTCHA or other validation
                ])
            ;
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults([
                'data_class' => ContactForm::class,
            ]);
        }
    }