<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function __construct(
        private RouterInterface $router,
        private RequestStack $requestStack,
    )
    {
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundHttpException) {
            $response = new RedirectResponse($this->router->generate('404'));
            $event->setResponse($response);
        }

        // Handle Messenger-wrapped exceptions
        if ($exception instanceof HandlerFailedException) {
            $nested = $exception->getPrevious();

            if ($nested !== null) {
                $message = $nested->getMessage();

                if (str_contains($message, 'Email already registered. Try to login')) {
                    $flashBag = $this->requestStack->getSession()->getFlashBag();
                    $flashBag->add('danger', $message);
                    $response = new RedirectResponse($this->router->generate('user_login'));
                    $event->setResponse($response);
                    return;
                }

                // check for other message patterns
//                if (str_contains($message, 'Another specific error')) {
//                    //
//                }
            }
        }
    }
}
