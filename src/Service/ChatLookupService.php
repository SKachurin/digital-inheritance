<?php

declare(strict_types=1);

namespace App\Service;

final class ChatLookupService
{
    public function __construct(
        private readonly string $lookupKey,
    ) {
    }

    public function make(string $channel, string $sender): string
    {
        $normalized = $this->normalize($channel, $sender);

        return hash_hmac('sha256', $channel . ':' . $normalized, $this->lookupKey);
    }

    private function normalize(string $channel, string $sender): string
    {
        $sender = trim($sender);

        return match ($channel) {
            'email' => mb_strtolower($sender),
            'whatsapp' => preg_replace('/\D+/', '', $sender) ?? '',
            'telegram' => ltrim(mb_strtolower($sender), '@'),
            default => mb_strtolower($sender),
        };
    }
}