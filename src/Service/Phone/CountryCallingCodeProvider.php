<?php

declare(strict_types=1);

namespace App\Service\Phone;

final class CountryCallingCodeProvider
{
    public function getChoices(): array
    {
        /** @var array<string,array{name:string,dial:string}> $map */
        $map = require \dirname(__DIR__, 3).'/config/phone_country_codes.php';

        $choices = [];
        foreach ($map as $iso2 => $row) {
            $flag  = $this->flagEmoji($iso2);
            $label = sprintf('%s %s (+%s)', $flag, $row['name'], $row['dial']);
            $choices[$label] = $row['dial'];
        }

        return $choices;
    }

    private function flagEmoji(string $iso2): string
    {
        $iso2 = strtoupper(trim($iso2));
        if (!preg_match('/^[A-Z]{2}$/', $iso2)) {
            return '🏳️';
        }

        $base = 0x1F1E6;
        $a = $base + (ord($iso2[0]) - ord('A'));
        $b = $base + (ord($iso2[1]) - ord('A'));

        return mb_chr($a, 'UTF-8') . mb_chr($b, 'UTF-8');
    }

    public function normalize(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $code) ?? '';
        return $digits !== '' ? $digits : null;
    }

}
