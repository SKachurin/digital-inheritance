<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Service\Api\KmsWrapInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class KMSWrapController extends AbstractController
{
    public function __construct(
        private readonly KmsWrapInterface $kmsWrap,
    ) {}

    public function __invoke(Request $req): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Customer) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        $in = json_decode($req->getContent(), true);
        if (!is_array($in)) {
            return $this->json(['error' => 'bad_request'], 400);
        }

        $kmsId = (string)($in['kms_id'] ?? '');
        if (!preg_match('/^kms[1-3]$/', $kmsId)) {
            return $this->json(['error' => 'bad_request'], 400);
        }
        $kmsNumber = (int)substr($kmsId, 3);

        // contract: accept inner_b64, fallback to dek_b64
        $payloadB64 = (string)($in['inner_b64'] ?? '');
        if ($payloadB64 === '') {
            $payloadB64 = (string)($in['dek_b64'] ?? '');
        }

        $hB64     = (string)($in['h_b64'] ?? '');
        $answerFp = (string)($in['answer_fp'] ?? '');

        if ($payloadB64 === '' || $hB64 === '' || $answerFp === '') {
            return $this->json(['error' => 'bad_request'], 400);
        }

        // validate b64 + H length
        $payloadRaw = base64_decode($payloadB64, true);
        if ($payloadRaw === false || $payloadRaw === '') {
            return $this->json(['error' => 'bad_inner'], 400);
        }

        $hRaw = base64_decode($hB64, true);
        if ($hRaw === false || strlen($hRaw) !== 32) {
            return $this->json(['error' => 'bad_h'], 400);
        }

        $wB64 = $this->kmsWrap->wrapInner(
            (int)$user->getId(),
            $kmsNumber,
            $payloadB64,
            $hB64,
            $answerFp
        );

        if ($wB64 === null) {
            return $this->json(['error' => 'kms_unavailable'], 503);
        }

        return $this->json(['w_b64' => $wB64], 200);
    }
}