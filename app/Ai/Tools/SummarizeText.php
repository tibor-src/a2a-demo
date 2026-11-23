<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\A2A\DemoAi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

use function Laravel\Ai\agent;

final class SummarizeText implements Tool
{
    public function description(): string
    {
        return 'Summarize the given text. Return only the summary.';
    }

    public function handle(Request $request): string
    {
        $text = $request['text'] ?? '';
        $text = is_string($text) ? $text : '';

        return agent(
            instructions: 'Summarize the user text concisely. Return only the summary, with no preamble.',
        )->prompt($text, model: DemoAi::model())->text;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required()->description('The text to summarize.'),
        ];
    }
}
