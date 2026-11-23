<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\SummarizeText;
use App\Ai\Tools\TranslateText;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;

final class WorkerAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are an A2A worker. Your only skills are summarizing text and translating text into a target language. Use the SummarizeText tool to summarize and the TranslateText tool (with target_language) to translate. Do not add other skills. Return only the result.';
    }

    /**
     * @return list<Message>
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [
            new SummarizeText,
            new TranslateText,
        ];
    }
}
