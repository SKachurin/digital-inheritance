<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * https://gemini.google.com/app/80f8d1ee517c2d5e
         */
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Your Email Is Your Login',
                'constraints' => [
                    new Email([
                        'message' => 'Please enter your login.',
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'password',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password.',
                    ]),
                ],
            ])
            ->add('remember_me', CheckboxType::class, [
                'mapped'=> false,
                'label'    => 'remember me',
                'label_attr' => [
                    'class' => 'checkbox-inline checkbox-switch',
                ],
                'required' => false,
            ])
            ->add('Submit', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ));
    }
}
/**1. Create a Symfony FormType:

Create LoginType.php with structure similar to ContactFormType.
Define fields for username, password, and rememberMe.
Apply appropriate validation constraints (e.g., NotBlank, Length).
2. Implement Authentication Logic in a Controller:

Create a controller action (e.g., login).
Inject the form service (LoginType) and entity manager (Doctrine).
Handle form submission:
If valid, retrieve the user entity using the username.
If the user is found and the password is valid, authenticate the user using Symfony's security component (LoginManager).
Set a persistent session if rememberMe is checked.
3. Integrate Security Component:

Configure Symfony's security component in security.yaml.
Define authentication and firewall rules.
Consider using a user provider for loading user data from your database.
4. Adapt validatePassword Logic:

Implement the validatePassword method within your User entity or a dedicated service.
Handle password hashing and comparison securely (using bcrypt or similar).
5. Additional Considerations:

Refactor custom logic (e.g., getUser) into repository methods or services as needed.
Adapt error handling and messaging to Symfony conventions.
Key Points:

Symfony's security component handles authentication and authorization.
Form validation is integrated with security checks.
Password handling should be secure and follow best practices.
I'm ready to provide more specific guidance as you progress with each step! */