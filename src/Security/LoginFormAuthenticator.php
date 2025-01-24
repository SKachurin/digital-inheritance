<?php

namespace App\Security;

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

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private RouterInterface $router;
    private ReCaptcha $recaptcha;
    private AuthenticationUtils $authenticationUtils;
    private ReCaptcha $recaptchaV3;
    private ReCaptcha $recaptchaV2;

    public function __construct(RouterInterface $router, ReCaptcha $recaptchaV3, ReCaptcha $recaptchaV2)
    {
        $this->router = $router;
        $this->recaptchaV3 = $recaptchaV3;
        $this->recaptchaV2 = $recaptchaV2;
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
        $request->getSession()->set('authentication_error', $exception->getMessage());

        return new RedirectResponse($this->router->generate('user_login'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('user_login');
    }
}

