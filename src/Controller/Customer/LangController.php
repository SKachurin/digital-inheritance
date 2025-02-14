<?php

namespace App\Controller\Customer;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

class LangController extends AbstractController
{
    private const LANGUAGE_COOKIE = 'preferred_language';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function changeLanguage(string $lang): Response
    {
        $availableLanguages = ['en', 'ru', 'es'];
        if (!in_array($lang, $availableLanguages)) {
            $lang = 'en';
        }

        $response = $this->redirect(
            $this->requestStack->getCurrentRequest()->headers->get('referer')
            ?? $this->generateUrl('home')
        );

        $cookie = Cookie::create(self::LANGUAGE_COOKIE)
            ->withValue($lang)
            ->withExpires(strtotime('now + 1 year'))
            ->withPath('/')
            ->withSecure(false)
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $response->headers->setCookie($cookie);

        $customer = $this->getUser();
        if ($customer instanceof Customer) {
            $customer->setLang($lang);
            $this->entityManager->persist($customer);
            $this->entityManager->flush();
        }

        return $response;
    }
}
