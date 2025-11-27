<?php
namespace App\Controller\Api;

use App\Service\CryptoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class KMSWrapController extends AbstractController
{
    public function __invoke(Request $req, CryptoService $crypto): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Customer) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        $in       = json_decode($req->getContent(), true);
        $kms      = (int)($in['kms'] ?? 0);
        $dekB64   = (string)($in['dek_b64'] ?? '');
        $hB64     = (string)($in['h_b64'] ?? '');
        $answerFp = (string)($in['answer_fp'] ?? '');

        if (!in_array($kms, [1,2,3], true) || !$dekB64 || !$hB64 || !$answerFp) {
            return $this->json(['error' => 'bad_request'], 400);
        }

        $wRaw = $crypto->kmsWrap(
            (int) $user->getId(),
            $kms,
            base64_decode($dekB64),
            base64_decode($hB64),
            $answerFp
        );

        if ($wRaw === false) {
            // No KMS protection for this region
            return $this->json(['error' => 'kms_unavailable'], 503);
        }

        return $this->json([
            'w_b64' => base64_encode($wRaw),
        ]);
    }
}
