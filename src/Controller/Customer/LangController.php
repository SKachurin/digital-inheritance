<?php

namespace App\Controller\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

class LangController extends AbstractController
{
    private const LANGUAGE_COOKIE = 'preferred_language';
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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

        return $response;
    }
}
