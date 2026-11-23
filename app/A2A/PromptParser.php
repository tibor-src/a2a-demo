<?php

declare(strict_types=1);

namespace App\A2A;

final readonly class PromptParser
{
    public function __construct(
        public string $text,
        public string $language,
    ) {}

    public static function parse(string $prompt, string $defaultLanguage = 'Spanish'): self
    {
        $prompt = trim($prompt);
        $language = $defaultLanguage;
        $text = $prompt;

        if (preg_match('/(?:^|\s)(?:into|in|to)\s+([A-Za-z][A-Za-z\- ]{1,40})(?:\.|:|\n|$)/i', $prompt, $matches) === 1) {
            $language = trim($matches[1], " \t\n\r\0\x0B.,");
        }

        if (preg_match('/(?:target\s+language|language)\s*[:=]\s*([A-Za-z][A-Za-z\- ]{1,40})/i', $prompt, $matches) === 1) {
            $language = trim($matches[1], " \t\n\r\0\x0B.,");
        }

        $text = preg_replace('/^\s*summarize(?:\s+then\s+translate)?(?:\s+(?:into|in|to)\s+[A-Za-z][A-Za-z\- ]{1,40})?\s*:?\s*/i', '', $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            $text = $prompt;
        }

        return new self($text, $language === '' ? $defaultLanguage : $language);
    }
}
