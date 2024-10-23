<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;
    private const LANGUAGE_COOKIE = 'preferred_language';

    public function __construct(string $defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        $preferredLanguage = $request->cookies->get(self::LANGUAGE_COOKIE);

        if ($preferredLanguage) {
            $request->setLocale($preferredLanguage);
        } else {
            // Fallback to the 'Accept-Language' header
            $preferredLanguage = $request->getPreferredLanguage(['en', 'ru', 'es']);

            if ($preferredLanguage) {
                $request->setLocale($preferredLanguage);
            } else {
                // Fallback to session locale
                $locale = $request->getSession()->get('_locale', $this->defaultLocale);

                if (is_string($locale)) {
                    $request->setLocale($locale);
                } else {
                    //Use default locale
                    $request->setLocale($this->defaultLocale);
                }
            }
        }

        // Debug the locale
        // dump($request->getLocale());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
