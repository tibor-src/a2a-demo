<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\A2A\DemoAi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

use function Laravel\Ai\agent;

final class TranslateText implements Tool
{
    public function description(): string
    {
        return 'Translate text into a target language. Requires the target_language parameter.';
    }

    public function handle(Request $request): string
    {
        $text = $request['text'] ?? '';
        $text = is_string($text) ? $text : '';
        $language = $request['target_language'] ?? 'Spanish';
        $language = is_string($language) && $language !== '' ? $language : 'Spanish';

        return agent(
            instructions: "Translate the user text into {$language}. Write only {$language}. Return only the translation, with no preamble, labels, or quotation marks.",
        )->prompt($text, model: DemoAi::model())->text;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required()->description('The text to translate.'),
            'target_language' => $schema->string()->required()->description('The language to translate into, for example Spanish or French.'),
        ];
    }
}
