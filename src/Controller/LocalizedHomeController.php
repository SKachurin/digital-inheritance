<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class LocalizedHomeController extends AbstractController
{
    private const DEFAULT_LOCALE = 'en';
    private const ALLOWED_LOCALES = ['en', 'es', 'ru'];
    private const LANGUAGE_COOKIE = 'preferred_language';

    public function redirectToPreferredLocale(Request $request): RedirectResponse
    {
        $locale = $request->cookies->get(self::LANGUAGE_COOKIE);

        if (!in_array($locale, self::ALLOWED_LOCALES, true)) {
            $locale = $request->getPreferredLanguage(self::ALLOWED_LOCALES);
        }

        if (!in_array($locale, self::ALLOWED_LOCALES, true)) {
            $locale = self::DEFAULT_LOCALE;
        }

        return $this->redirectToRoute('localized_home', [
            '_locale' => $locale,
        ], 302);
    }
}