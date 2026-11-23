<?php

declare(strict_types=1);

namespace App\A2A;

final class LanguageHints
{
    /**
     * Common Spanish function words. Used for a tolerant language check, not exact wording.
     *
     * @return list<string>
     */
    public static function spanish(): array
    {
        return ['el', 'los', 'las', 'una', 'un', 'para', 'del', 'está', 'por', 'con', 'es', 'y'];
    }

    /**
     * Common French function words. Used for a tolerant language check, not exact wording.
     *
     * @return list<string>
     */
    public static function french(): array
    {
        return ['le', 'la', 'les', 'des', 'une', 'un', 'est', 'dans', 'pour', 'aux', 'du', 'sur', 'avec', 'et', 'en'];
    }

    /**
     * @param  list<string>  $words
     */
    public static function matches(string $text, array $words, int $minimum = 2): bool
    {
        $haystack = mb_strtolower($text);
        $hits = 0;

        foreach ($words as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/u', $haystack) === 1) {
                $hits++;
            }
        }

        return $hits >= $minimum;
    }
}
