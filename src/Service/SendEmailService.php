<?php

namespace App\Service;

use App\Entity\Contact;
use Doctrine\DBAL\Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class SendEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private CryptoService $cryptoService
    ) {}

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    public function sendMessageEmail(Contact $contact, string $message): JsonResponse //TODO translate twig for email
    {
        $emailAddress = $this->cryptoService->decryptData($contact->getValue());
//        $name = $this->cryptoService->decryptData($contact->getCustomer()->getCustomerName());

        if (is_string($emailAddress)) {

            $email = (new TemplatedEmail())
                ->from('info@thedigitalheir.com')
                ->to($emailAddress)
                ->subject($message . ' - Message from TheDigitalHeir')
                ->htmlTemplate('emails/alert_pipeline_email.html.twig')
                ->context([
                    'emailAddress' => $emailAddress,
                    'message' => $message,
                    'year' => \Date('Y'),
                ])
                //a text template
                ->text($message);

            try {
                $this->mailer->send($email);

                return new JsonResponse([
                    'success' => true,
                    'chatId' => $emailAddress], 200);

            } catch (TransportExceptionInterface $e) {
                return new JsonResponse([
                    'error' => 'Failed to send email',
                    'details' => $e->getMessage(),
                ], 500);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Invalid email or other error',
                    'details' => $e->getMessage(),
                ], 400);
            }

        } else {
            return new JsonResponse([
                'error' => 'Invalid email',
            ], 400);
        }
    }
}
