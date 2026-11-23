<?php

declare(strict_types=1);

namespace App\A2A;

use App\Ai\Tools\SummarizeText;
use App\Ai\Tools\TranslateText;
use Laravel\Ai\Tools\Request;

final class WorkerSkills
{
    public function handle(string $prompt): string
    {
        if ($this->isTranslate($prompt)) {
            return (new TranslateText)->handle(new Request([
                'text' => $this->payload($prompt),
                'target_language' => $this->language($prompt),
            ]));
        }

        return (new SummarizeText)->handle(new Request([
            'text' => $this->payload($prompt),
        ]));
    }

    public function isTranslate(string $prompt): bool
    {
        return (bool) preg_match('/translatetext|translate/i', $prompt);
    }

    public function language(string $prompt): string
    {
        if (preg_match('/into\s+([A-Za-z][A-Za-z\- ]{1,40})(?:\.|:|\n|$)/i', $prompt, $matches) === 1) {
            $language = trim($matches[1], " \t\n\r\0\x0B.,");

            if ($language !== '') {
                return $language;
            }
        }

        return 'Spanish';
    }

    public function payload(string $prompt): string
    {
        if (preg_match('/\n\n(.+)\z/s', $prompt, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/(?:summarize(?: the following text)?|translate the following summary(?: into [A-Za-z\- ]+)?)\s*:\s*(.+)\z/is', $prompt, $matches) === 1) {
            return trim($matches[1]);
        }

        return trim($prompt);
    }
}
