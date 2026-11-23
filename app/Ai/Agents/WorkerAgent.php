<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\A2A\DemoAi;
use App\A2A\WorkerMarker;
use App\A2A\WorkerSkills;
use App\Ai\Tools\SummarizeText;
use App\Ai\Tools\TranslateText;
use Generator;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\AgentInput;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;

final class WorkerAgent implements Agent, Conversational, HasTools
{
    use Promptable {
        prompt as runPrompt;
        stream as runStream;
    }

    public function instructions(): string
    {
        return 'You are an A2A worker. Your only skills are summarizing text and translating text into a target language. Use the SummarizeText tool to summarize and the TranslateText tool (with target_language) to translate. Do not add other skills. Return only the result.';
    }

    public function provider(): string
    {
        return DemoAi::provider();
    }

    public function model(): string
    {
        return DemoAi::model();
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

    /**
     * @param  array<int, mixed>  $attachments
     * @param  Lab|array<int|string, mixed>|string|null  $provider
     */
    public function prompt(
        AgentInput|UserMessage|Decisions|string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null,
        ?int $timeout = null,
    ): AgentResponse {
        if (self::isFaked()) {
            $response = $this->runPrompt($prompt, $attachments, $provider, $model, $timeout);
            $response->text = WorkerMarker::append($response->text);

            return $response;
        }

        unset($attachments, $provider, $model, $timeout);

        return new AgentResponse(
            'worker',
            WorkerMarker::append(app(WorkerSkills::class)->handle($this->promptText($prompt))),
            new TextUsage,
            new Meta,
        );
    }

    /**
     * @param  array<int, mixed>  $attachments
     * @param  Lab|array<int|string, mixed>|string|null  $provider
     */
    public function stream(
        AgentInput|UserMessage|Decisions|string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null,
        ?int $timeout = null,
    ): StreamableAgentResponse {
        if (self::isFaked()) {
            $inner = $this->runStream($prompt, $attachments, $provider, $model, $timeout);

            return new StreamableAgentResponse($inner->invocationId, function () use ($inner): Generator {
                foreach ($inner as $event) {
                    yield $event;
                }

                yield new TextDelta('a2a-worker-marker', 'a2a-worker-marker', "\n".WorkerMarker::line(), time());
            }, new Meta);
        }

        unset($attachments, $provider, $model, $timeout);

        $text = WorkerMarker::append(app(WorkerSkills::class)->handle($this->promptText($prompt)));

        return new StreamableAgentResponse('worker', function () use ($text): Generator {
            yield new TextDelta('d1', 'm1', $text, time());
            yield new StreamEnd('e1', 'stop', new TextUsage, time());
        }, new Meta);
    }

    private function promptText(AgentInput|UserMessage|Decisions|string $prompt): string
    {
        if (is_string($prompt)) {
            return $prompt;
        }

        if ($prompt instanceof UserMessage) {
            return (string) $prompt->content;
        }

        if ($prompt instanceof AgentInput) {
            $message = $prompt->message();

            return $message instanceof UserMessage ? (string) $message->content : '';
        }

        return '';
    }
}
