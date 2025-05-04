<?php

declare(strict_types=1);

namespace App\Controller\Checkout;

use App\Service\PlanPriceResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CheckoutTwigController extends AbstractController
{
    public function __construct(
        private PlanPriceResolver $resolver
    )
    {
    }

    public function __invoke(Request $request): Response
    {
        return $this->render('checkout.html.twig', [
            'planPrices'          => $this->resolver->getAllPrices()
        ]);
    }

}