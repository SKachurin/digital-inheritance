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

class RegistrationController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private ReCaptcha $recaptchaV3;
    private ReCaptcha $recaptchaV2;

    public function __construct(
        MessageBusInterface $commandBus,
        ReCaptcha           $recaptchaV3,
        ReCaptcha           $recaptchaV2
    )
    {
        $this->commandBus = $commandBus;
        $this->recaptchaV3 = $recaptchaV3;
        $this->recaptchaV2 = $recaptchaV2;
    }

    /**
     * @throws \Exception
     */
    public function new(Request $request): Response
    {
        $customer = $this->getUser();

        if ($customer instanceof \App\Entity\Customer) {
            return $this->redirectToRoute('user_home');
        }

        $customer = new CustomerCreateInputDto('', '', '');
        $form = $this->createForm(RegistrationType::class, $customer);

        $form->handleRequest($request);

        /** @var CustomerCreateInputDto $customerData */
        $customerData = $form->getData();

        $errorMessage = '';

        if ($form->isSubmitted() && $form->isValid()) {
//            dd($customerData);
            $requestData = $request->request->all();
//            dd($requestData);
            $form = $this->createForm(RegistrationType::class, $customerData);

            $recaptchaResponse = $requestData['registration']['g-recaptcha-response'] ?? null;
            // if it's v3 or v2
            if (!$recaptchaResponse) {
                // it's V2
                $recaptchaResponse = $requestData['g-recaptcha-response'] ?? null;

                if (!$recaptchaResponse) {
                    return $this->render('user/registration.html.twig', [
                        'form' => $form,
                        'error' => 'Missing reCAPTCHA token.',
                    ]);
                }

                $this->validateRecaptchaV2($recaptchaResponse, $form->getData());

            } else {
//                dd($recaptchaResponse);
                // it's V3
                $this->validateRecaptchaV3($recaptchaResponse, $request);

                $session = $request->getSession();
                $errorMessage = $session->get('authentication_error');

//                dd($errorMessage);
                if ($errorMessage === 'Suspicious activity detected.') {

                    //v3 score is low, fallback to v2
                    $session->remove('authentication_error'); //clear it
                    return $this->render('user/registration.html.twig', [
                        'form' => $form,
                        'error' => $errorMessage
                    ]);
                }
            }

            // Proceed with the command dispatch
            $envelope = $this->commandBus->dispatch($form->getData());
            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new \RuntimeException('CommandBus failed to process the registration.');
            }

            $this->addFlash('success', 'Your registration is being processed. You will receive a confirmation email once it is complete.');

            return $this->redirectToRoute('user_login');
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
        if ($score < 0.5) {
            $request->getSession()->set('authentication_error', 'Suspicious activity detected.');
        }
    }

    private function validateRecaptchaV2(string $recaptchaResponse, $form): void
    {
        $recaptchaValidation = $this->recaptchaV2->verify($recaptchaResponse);

        if ($recaptchaValidation) {
            $this->ifValidatedV2($form);
        }

        if (!$recaptchaValidation->isSuccess()) {
            throw new CustomUserMessageAuthenticationException('reCAPTCHA v2 validation failed.');
        }
    }

    private function ifValidatedV2($form): void
    {
        // Proceed with the command dispatch
        $envelope = $this->commandBus->dispatch($form);
        $handledStamp = $envelope->last(HandledStamp::class);

        if (!$handledStamp) {
            throw new \RuntimeException('CommandBus failed to process the registration.');
        }

        $this->addFlash('success', 'Your registration is being processed. You will receive a confirmation email once it is complete.');

        $this->redirectToRoute('user_login');
    }
}