<?php

namespace App\Controller\Customer;

use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use App\Form\Type\RegistrationType;
use ReCaptcha\ReCaptcha;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    private const RECAPTCHA_V3_THRESHOLD = 0.5;
    private const COOKIE_NAME = 'ref';

    public function __construct(
        private MessageBusInterface $commandBus,
        private ReCaptcha $recaptchaV3,
        private ReCaptcha $recaptchaV2,
        private TranslatorInterface $translator
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function new(Request $request): Response
    {
        // Redirect if already authenticated
        if ($this->getUser() instanceof \App\Entity\Customer) {
            return $this->redirectToRoute('user_home');
        }

        $customerDto = new CustomerCreateInputDto('', '', '', null, null);
        $form = $this->createForm(RegistrationType::class, $customerDto);

        $form->handleRequest($request);

        $errorMessage = '';

        if ($form->isSubmitted() && $form->isValid()) {
            $requestData = $request->request->all();

            $recaptchaV2Token = $requestData['g-recaptcha-response'] ?? null;

            if ($recaptchaV2Token) {
                $this->validateRecaptchaV2($recaptchaV2Token);
            } else {
                $recaptchaV3Token = $requestData['registration']['g-recaptcha-response'] ?? null;

                if (!$recaptchaV3Token) {
                    return $this->render('user/registration.html.twig', [
                        'form' => $form,
                        'error' => 'Missing reCAPTCHA token.',
                    ]);
                }

                $this->validateRecaptchaV3($recaptchaV3Token, $request);

                $errorMessage = $request->getSession()->get('authentication_error');

                if ($errorMessage === 'Suspicious activity detected.') {
                    $request->getSession()->remove('authentication_error');

                    return $this->render('user/registration.html.twig', [
                        'form' => $form,
                        'error' => $errorMessage
                    ]);
                }
            }

            $refCookie = $request->cookies->get(self::COOKIE_NAME);

            if ($refCookie && Uuid::isValid($refCookie)) {
                $customerDto->setInvitedByUuid($refCookie);
            }


            // Proceed with the command dispatch
            $envelope = $this->commandBus->dispatch($customerDto);
            $handledStamp = $envelope->last(HandledStamp::class);
            if (!$handledStamp) {
                throw new \RuntimeException('CommandBus failed to process the registration.');
            }

            $this->addFlash('success', $this->translator->trans('errors.flash.registration_is_processed'));

            $request->getSession()->set('unverified_email', $customerDto->getCustomerEmail());

            return $this->redirectToRoute('wait');
        }

        return $this->render('user/registration.html.twig', [
            'form' => $form,
            'error' => $errorMessage
        ]);
    }

    private function validateRecaptchaV3(string $recaptchaResponse, Request $request): void
    {
        $recaptchaValidation = $this->recaptchaV3->verify($recaptchaResponse, $request->getClientIp());

        if (!$recaptchaValidation->isSuccess()) {
            throw new CustomUserMessageAuthenticationException('reCAPTCHA v3 validation failed.');
        }

//        $score = 0.2; // <-- for test V2
        $score = $recaptchaValidation->getScore();
        if ($score < self::RECAPTCHA_V3_THRESHOLD) {
            $request->getSession()->set('authentication_error', 'Suspicious activity detected.');
        }
    }

    private function validateRecaptchaV2(string $recaptchaResponse): void
    {
        $recaptchaValidation = $this->recaptchaV2->verify($recaptchaResponse);

        if (!$recaptchaValidation || !$recaptchaValidation->isSuccess()) {
            throw new CustomUserMessageAuthenticationException('reCAPTCHA v2 validation failed.');
        }
    }
}