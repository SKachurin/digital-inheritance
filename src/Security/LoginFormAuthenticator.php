<?php

namespace App\Security;

use App\Repository\ContactRepository;
use App\Repository\CustomerRepository;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;


    public function __construct(
        private RouterInterface     $router,
        private ReCaptcha           $recaptcha,
        private ReCaptcha           $recaptchaV3,
        private ReCaptcha           $recaptchaV2,
        private CustomerRepository  $customerRepository,
        private ContactRepository   $contactRepository,
        private TranslatorInterface $translator,
    )
    {
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username');
        $password = $request->request->get('_password');
        $formData = $request->request->all();
        $recaptchaResponse = $formData['login']['g-recaptcha-response'] ?? null;

        // if it's v3 or v2
        if (!$recaptchaResponse) {
            $recaptchaResponse = $formData['g-recaptcha-response'] ?? null;
            if (!$recaptchaResponse) {
                throw new CustomUserMessageAuthenticationException('Missing reCAPTCHA token.');
            }
            $this->validateRecaptchaV2($recaptchaResponse);
        } else {
            $this->validateRecaptchaV3($recaptchaResponse);
        }

        $this->isEmailVerified($username, $request);

        // Create and return the Passport
        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $formData['login']['_csrf_token'] ?? null),
                new RememberMeBadge(),
            ]
        );

    }

    /**
     * Validate reCAPTCHA v3.
     *
     * @param string $recaptchaResponse
     * @throws CustomUserMessageAuthenticationException
     */
    private function validateRecaptchaV3(string $recaptchaResponse): void
    {
        $recaptchaValidation = $this->recaptchaV3->verify($recaptchaResponse);

        if (!$recaptchaValidation->isSuccess()) {
            throw new CustomUserMessageAuthenticationException('reCAPTCHA v3 validation failed.');
        }

//        $score = 0.2; <-- for test V2
        $score = $recaptchaValidation->getScore();
        if ($score < 0.5) {
            throw new CustomUserMessageAuthenticationException('Suspicious activity detected.');
        }
    }

    /**
     * Validate reCAPTCHA v2.
     *
     * @param string $recaptchaResponse
     * @throws CustomUserMessageAuthenticationException
     */
    private function validateRecaptchaV2(string $recaptchaResponse): void
    {
        $recaptchaValidation = $this->recaptchaV2->verify($recaptchaResponse);

        if (!$recaptchaValidation->isSuccess()) {
            throw new CustomUserMessageAuthenticationException('reCAPTCHA v2 validation failed.');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        $url = $this->router->generate('user_home');

        return new RedirectResponse($url);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $username = $request->request->get('_username');

        // wait email verification page
        if ($exception instanceof CustomUserMessageAuthenticationException &&
            $exception->getMessage() === $this->translator->trans('errors.reg.email_verification_required')) {

            $request->getSession()->set('unverified_email', $username);

            return new RedirectResponse($this->router->generate('wait'));
        }

        $request->getSession()->set('authentication_error', $exception->getMessage());

        return new RedirectResponse($this->router->generate('user_login'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('user_login');
    }

    private function isEmailVerified(string $username, Request $request): void
    {
        $customer = $this->customerRepository->findOneBy(['customerEmail' => $username]);

        if (!$customer) {
            return;
        }

        $email = $this->contactRepository->findOneBy(['customer' => $customer]);

        if (!$email || !$email->getIsVerified()) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('errors.reg.email_verification_required'));
        }

    }
}

