<?php

namespace App\Service;

class SocialAppLinkNormalizer
{
    public function normalize(string $link): string
    {
        $link = trim($link);

        if (str_starts_with($link, '@')) {
            return $link;
        }

        if (preg_match('/^\+/', $link)) {
            return preg_replace('/[^+\d]/', '', $link);
        }

        if (str_starts_with($link, 'https://t.me/')) {
            $parsedLink = parse_url($link, PHP_URL_PATH);
            $username = trim($parsedLink, '/');
            return '@' . $username;
        }

        if (str_starts_with($link, 't.me/')) {
            $username = substr($link, strlen('t.me/'));
            $username = trim($username, '/');
            return '@' . $username;
        }

        // Assume any remaining input is a username and prepend '@'
        return '@' . ltrim($link, '@');
    }
}
