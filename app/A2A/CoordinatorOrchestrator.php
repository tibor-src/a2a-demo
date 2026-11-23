<?php

declare(strict_types=1);

namespace App\A2A;

use RuntimeException;
use TiborSrc\A2A\Id;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Types\AgentCard;
use TiborSrc\A2A\Types\AgentSkill;
use TiborSrc\A2A\Types\GetTaskRequest;
use TiborSrc\A2A\Types\Message;
use TiborSrc\A2A\Types\Part;
use TiborSrc\A2A\Types\Role;
use TiborSrc\A2A\Types\SendMessageRequest;
use TiborSrc\A2A\Types\Task;

final class CoordinatorOrchestrator
{
    public function compose(string $prompt): string
    {
        $parsed = PromptParser::parse($prompt);
        $workerUrl = $this->workerUrl();

        $this->assertHttps($workerUrl, 'A2A_WORKER_URL');

        $card = A2A::registry()->card('worker');
        $this->assertHttpsCard($card);

        $summarize = $this->pickSkill($card, 'summarize');
        $translate = $this->pickSkill($card, 'translate');
        $clientUrl = $this->clientUrl($card, $workerUrl);

        $summary = $this->delegate(
            $clientUrl,
            $summarize,
            "Use the {$summarize->id} skill. Summarize the following text:\n\n{$parsed->text}",
        );

        $marker = WorkerMarker::extract($summary);
        $summaryText = WorkerMarker::strip($summary);

        $translation = $this->delegate(
            $clientUrl,
            $translate,
            "Use the {$translate->id} skill. Translate the following summary into {$parsed->language}:\n\n{$summaryText}",
        );

        $marker ??= WorkerMarker::extract($translation);
        $translationText = WorkerMarker::strip($translation);
        $composed = "Summary:\n{$summaryText}\n\nTranslation ({$parsed->language}):\n{$translationText}";

        if ($marker !== null) {
            $composed .= "\n".WorkerMarker::line($marker);
        }

        return $composed;
    }

    public function workerUrl(): string
    {
        return rtrim((string) config('a2a.agents.worker.card', 'https://worker.a2a-demo.test'), '/');
    }

    public function coordinatorUrl(): string
    {
        return rtrim((string) config('a2a.coordinator_url', 'https://coordinator.a2a-demo.test'), '/');
    }

    private function clientUrl(AgentCard $card, string $fallback): string
    {
        $url = $card->supportedInterfaces[0]->url ?? $fallback;

        $this->assertHttps($url, 'worker agent card URL');

        return rtrim($url, '/');
    }

    private function delegate(string $workerUrl, AgentSkill $skill, string $text): string
    {
        $client = A2A::client($workerUrl);
        $response = $client->sendMessage(new SendMessageRequest(
            new Message(
                messageId: Id::uuid(),
                role: Role::User,
                parts: [Part::text($text)],
                metadata: ['skill' => $skill->id],
            ),
        ));

        $composed = A2AText::fromResponse($response);
        $task = $response->task;

        if ($task instanceof Task) {
            try {
                $fresh = $client->getTask(new GetTaskRequest($task->id));
                $tracked = A2AText::fromTask($fresh);

                if ($tracked !== '' && $tracked !== $fresh->status->state->value) {
                    $composed = $tracked;
                }
            } catch (\Throwable) {
                // Fake clients may not round-trip getTask; sendMessage already returned the worker result.
            }
        }

        if ($composed === '') {
            throw new RuntimeException("Worker skill [{$skill->id}] returned an empty result.");
        }

        return $composed;
    }

    private function pickSkill(AgentCard $card, string $needle): AgentSkill
    {
        foreach ($card->skills as $skill) {
            if (str_contains(strtolower($skill->id), $needle) || str_contains(strtolower($skill->name), $needle)) {
                return $skill;
            }
        }

        throw new RuntimeException("Worker agent card is missing a {$needle} skill.");
    }

    private function assertHttpsCard(AgentCard $card): void
    {
        foreach ($card->supportedInterfaces as $interface) {
            $this->assertHttps($interface->url, 'worker agent card URL');
        }
    }

    private function assertHttps(string $url, string $label): void
    {
        if (! str_starts_with($url, 'https://')) {
            throw new RuntimeException("{$label} must be an https URL, got [{$url}].");
        }
    }
}
