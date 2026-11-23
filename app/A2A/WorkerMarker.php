<?php

declare(strict_types=1);

namespace App\A2A;

final class WorkerMarker
{
    public const string AGENT = 'WorkerAgent';

    public static function line(string $agent = self::AGENT): string
    {
        return '<<<A2A_WORKER:'.$agent.'>>>';
    }

    public static function append(string $text, string $agent = self::AGENT): string
    {
        $line = self::line($agent);
        $trimmed = rtrim($text);

        if (str_contains($trimmed, $line)) {
            return $trimmed;
        }

        return $trimmed === '' ? $line : $trimmed."\n".$line;
    }

    public static function extract(string $text): ?string
    {
        if (preg_match('/<<<A2A_WORKER:([A-Za-z0-9_]+)>>>/', $text, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public static function present(string $text): bool
    {
        return self::extract($text) !== null;
    }

    public static function strip(string $text): string
    {
        $stripped = preg_replace('/\n?<<<A2A_WORKER:[A-Za-z0-9_]+>>>/', '', $text);

        return is_string($stripped) ? rtrim($stripped) : rtrim($text);
    }
}
