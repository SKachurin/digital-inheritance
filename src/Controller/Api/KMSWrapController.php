<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Customer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class KMSWrapController extends AbstractController
{
    public function __construct(
        private readonly string $kmsMode,
        private readonly string $testKmsKey1B64,
        private readonly string $testKmsKey2B64,
        private readonly string $testKmsKey3B64,
        private readonly \App\Service\Kms\KmsGatewayService $gateway,
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

        $kms      = (int)($in['kms'] ?? 0);
        $dekB64   = (string)($in['dek_b64'] ?? '');
        $hB64     = (string)($in['h_b64'] ?? '');
        $answerFp = (string)($in['answer_fp'] ?? '');


        if (!in_array($kms, [1,2,3], true) || $dekB64 === '' || $hB64 === '' || $answerFp === '') {
            return $this->json(['error' => 'bad_request'], 400);
        }

        // strict validation
        $dek = base64_decode($dekB64, true);
        $h   = base64_decode($hB64, true);
        if ($dek === false || strlen($dek) !== 32) return $this->json(['error' => 'bad_dek'], 400);
        if ($h === false || strlen($h) !== 32)     return $this->json(['error' => 'bad_h'], 400);


        if (strtolower(trim($this->kmsMode)) === 'mock') {

            $wB64 = $this->wrapDekMock(
                (int) $user->getId(),
                $kms,
                $dek,
                $h,
                $answerFp
            );

            if ($wB64 === false) {
                return $this->json(['error' => 'kms_unavailable'], 503);
            }

            return $this->json(['w_b64' => $wB64], 200);
        }

        // GATEWAY: mTLS wrap
        $w = $this->gateway->wrapDek((int)$user->getId(), $kms, $dekB64, $hB64, $answerFp);
        if ($w === null) {
            return $this->json(['error' => 'kms_unavailable'], 503);
        }

        return $this->json(['w_b64' => $w], 200);
    }

    private function getTestKeyForKms(int $kms): string
    {
        return match ($kms) {
            1 => trim($this->testKmsKey1B64),
            2 => trim($this->testKmsKey2B64),
            3 => trim($this->testKmsKey3B64),
            default => '',
        };
    }

    private function hkdfSha256(string $ikm, string $salt, string $info, int $len): string
    {
        return hash_hkdf('sha256', $ikm, $len, $info, $salt);
    }

    private function wrapDekMock(
        int $userId,
        int $kms,
        string $dek32,
        string $h32,
        string $answerFp
    ): string|false {
        $testKeyB64 = $this->getTestKeyForKms($kms);
        $testKey = base64_decode($testKeyB64, true);
        if ($testKey === false || $testKey === '') return false;
        if ($testKey === '') {
            return false; // KMS down
        }

        $kek = hash('sha256', $testKey, true);               // 32B
        $kekPrime = $this->hkdfSha256($kek, $h32, 'wrap-v2', 32);

        $iv  = random_bytes(12);
        $aad = $userId . '|' . $answerFp;

        $ct = openssl_encrypt(
            $dek32,
            'aes-256-gcm',
            $kekPrime,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($ct === false) {
            return false;
        }

        return base64_encode($iv . $ct . $tag);
    }
}