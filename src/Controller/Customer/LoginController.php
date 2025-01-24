<?php
namespace App\Controller\Customer;

use App\Form\Type\LoginType;
use ReCaptcha\ReCaptcha;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    private ReCaptcha $recaptchaV2;
    public function __construct(ReCaptcha $recaptchaV2)
    {
        $this->recaptchaV2 = $recaptchaV2;
    }

    public function index(
        AuthenticationUtils $authenticationUtils,
        Request $request
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('user_home');
        }

        $form = $this->createForm(LoginType::class, [
            'email' => $authenticationUtils->getLastUsername(),
        ]);

        $session = $request->getSession();
        $errorMessage = $session->get('authentication_error');
        $session->remove('authentication_error'); //clear it

        $error = $errorMessage ?? $authenticationUtils->getLastAuthenticationError();
        if ($request->isMethod('POST')) {
            // reCAPTCHA v2 response
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            if ($error === 'Suspicious activity detected.') {
                if (!$recaptchaResponse) {
                    $this->addFlash('error', 'Please complete the reCAPTCHA.'); //TODO translate all errors
                    return $this->redirectToRoute('user_login');
                }

                // Verify the reCAPTCHA v2
                $recaptcha = $this->recaptchaV2;
                $recaptchaValidation = $recaptcha->verify($recaptchaResponse, $request->getClientIp());
                if (!$recaptchaValidation->isSuccess()) {
                    $this->addFlash('error', 'reCAPTCHA validation failed. Please try again.');
                    return $this->redirectToRoute('user_login');
                }
            }
        }

        // If no session error exists, use the standard AuthenticationUtils error
        $error = $errorMessage ?? $authenticationUtils->getLastAuthenticationError();

        if ($error instanceof AuthenticationException) {
            $error = new \Exception('Invalid email or password. Please try again.');
        }

        return $this->render('user/login.html.twig', [
             'form'          => $form,
             'last_username' =>  $authenticationUtils->getLastUsername(),
             'error'         => $error
        ]);
    }
}