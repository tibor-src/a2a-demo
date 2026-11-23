<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\A2A\CoordinatorOrchestrator;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\AgentInput;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasSkills;
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
use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;

final class CoordinatorAgent implements Agent, Conversational, HasSkills, HasTools
{
    use Promptable;

    public function __construct(
        private readonly CoordinatorOrchestrator $orchestrator,
    ) {}

    public function instructions(): string
    {
        return 'Coordinator A2A agent. Discover a worker over HTTPS from its agent card, pick summarize then translate, delegate via A2A::client, track the worker task, and return a composed result. Do not call the worker in-process.';
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
        return [];
    }

    /**
     * @return list<Skill>
     */
    public function skills(): iterable
    {
        return [
            new Skill(
                'summarize-then-translate',
                'Discover the worker over HTTPS, summarize text, then translate the summary.',
                'Fetch the worker agent card, pick the summarize and translate skills, delegate over HTTPS with A2A::client, track the worker task, and return the composed result.',
            ),
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
        unset($attachments, $provider, $model, $timeout);

        return new AgentResponse(
            'coordinator',
            $this->orchestrator->compose($this->promptText($prompt)),
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
        unset($attachments, $provider, $model, $timeout);

        $text = $this->orchestrator->compose($this->promptText($prompt));

        return new StreamableAgentResponse('coordinator', function () use ($text) {
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
