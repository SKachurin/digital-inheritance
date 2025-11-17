<?php

namespace App\Controller\Api;

use App\Service\CryptoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class NoteTokenController extends AbstractController
{
    public function __invoke(CryptoService $crypto): JsonResponse | RedirectResponse
    {
        $customer = $this->getUser();
        if (!$customer instanceof \App\Entity\Customer) {
            return $this->redirectToRoute('user_login');
        }

        return $this->json([
            'tokens' => $crypto->getNoteTokens($customer->getId())
        ]);
    }
}
