<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\ContactRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

class CustomerContactStatusListener
{
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;
    private ContactRepository $contactRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Environment $twig,
        ContactRepository $contactRepository
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->contactRepository = $contactRepository;
    }

    // TODO - FOR EACH CONTACT PERSONAL ?
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $targetRoutes = [
            'user_home',
            'user_home_1',
            'user_home_email',
            'user_home_email_',
            'user_home_phone',
            'user_home_phone_',
            'user_home_social',
        ];

        if (in_array($route, $targetRoutes, true)) {

            /** @var null | TokenInterface $token */
            $token = $this->tokenStorage->getToken();
            if (null === $token) {
                return;
            }

            /** @var Customer $customer */
            $customer = $token->getUser();
            if (!$customer instanceof Customer) {
                return;
            }

            // email and phone statuses for the customer
            $contacts = $this->contactRepository->findBy(['customer' => $customer]);

            $customerFirstEmail = null;
            $customerFirstEmailVerified = false;

            $customerSecondEmail = null;
            $customerSecondEmailVerified = false;

            $customerFirstPhone = null;
            $customerPhoneVerified = false;

            $customerSecondPhone = null;
            $customerSecondPhoneVerified = false;

            $customerSocial = null;
            $customerSocialVerified = false;

            $phonesCounter = 0;
            $emailsCounter = 0;

            foreach ($contacts as $contact) {
                switch ($contact->getContactTypeEnum()) {
                    case 'email':
                        $emailsCounter++;
                        if ($emailsCounter === 1) {
                            $customerFirstEmail = $contact->getId();
                            $customerFirstEmailVerified = $contact->getIsVerified();
                        } elseif ($emailsCounter === 2) {
                            $customerSecondEmail = $contact->getId();
                            $customerSecondEmailVerified = $contact->getIsVerified();
                        }
                        break;
                    case 'phone':
                        $phonesCounter++;
                        if ($phonesCounter === 1) {
                            $customerFirstPhone = $contact->getId();
                            $customerPhoneVerified = $contact->getIsVerified();
                        } elseif ($phonesCounter === 2) {
                            $customerSecondPhone = $contact->getId();
                            $customerSecondPhoneVerified = $contact->getIsVerified();
                        }
                        break;
                    case 'social':
                        $customerSocial = $contact->getId();
                        $customerSocialVerified = $contact->getIsVerified();
                        break;
                }
            }

            $this->twig->addGlobal('customerFirstEmail', $customerFirstEmail);
            $this->twig->addGlobal('customerFirstEmailVerified', $customerFirstEmailVerified);

            $this->twig->addGlobal('customerSecondEmail', $customerSecondEmail);
            $this->twig->addGlobal('customerSecondEmailVerified', $customerSecondEmailVerified);

            $this->twig->addGlobal('customerFirstPhone', $customerFirstPhone);
            $this->twig->addGlobal('customerPhoneVerified', $customerPhoneVerified);

            $this->twig->addGlobal('customerSecondPhone', $customerSecondPhone);
            $this->twig->addGlobal('customerSecondPhoneVerified', $customerSecondPhoneVerified);

            $this->twig->addGlobal('customerSocial', $customerSocial);
            $this->twig->addGlobal('customerSocialVerified', $customerSocialVerified);
        }
    }
}
