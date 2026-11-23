<?php

declare(strict_types=1);

use App\A2A\A2AText;
use App\A2A\LanguageHints;
use App\A2A\WorkerMarker;
use TiborSrc\A2A\Id;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Types\GetExtendedAgentCardRequest;
use TiborSrc\A2A\Types\GetTaskRequest;
use TiborSrc\A2A\Types\Message;
use TiborSrc\A2A\Types\Part;
use TiborSrc\A2A\Types\Role;
use TiborSrc\A2A\Types\SendMessageRequest;
use TiborSrc\A2A\Types\StreamResponse;
use TiborSrc\A2A\Types\Task;
use TiborSrc\A2A\Types\TaskState;
use TiborSrc\A2A\Types\TaskStatusUpdateEvent;

it('runs the full user > coordinator > worker chain over real HTTPS', function (): void {
    $coordinatorUrl = rtrim((string) env('A2A_COORDINATOR_URL', 'https://coordinator.a2a-demo.test'), '/');
    $workerUrl = rtrim((string) env('A2A_WORKER_URL', 'https://worker.a2a-demo.test'), '/');

    expect($coordinatorUrl)->toStartWith('https://')
        ->and($workerUrl)->toStartWith('https://');

    $workerCard = A2A::registry()->card('worker');
    expect($workerCard->supportedInterfaces[0]->url)->toStartWith('https://');

    $client = A2A::client($coordinatorUrl);
    $card = $client->getExtendedAgentCard(new GetExtendedAgentCardRequest);
    expect($card->name)->not->toBeEmpty();

    $response = $client->sendMessage(new SendMessageRequest(new Message(
        messageId: Id::uuid(),
        role: Role::User,
        parts: [Part::text('Summarize then translate into Spanish: Laravel is a PHP framework for web artisans.')],
    )));

    $text = A2AText::fromResponse($response);
    $spanish = A2AText::translationSection($text, 'Spanish');

    expect($text)->toContain('Summary:')
        ->and($text)->toContain(WorkerMarker::line())
        ->and(LanguageHints::matches($spanish, LanguageHints::spanish()) ? 'matched' : 'unmatched: '.$spanish)->toBe('matched');

    expect($response->task)->toBeInstanceOf(Task::class);
    $task = $client->getTask(new GetTaskRequest($response->task->id));
    expect($task->id)->toBe($response->task->id)
        ->and($task->status->state)->toBe(TaskState::Completed);

    $events = $client->sendStreamingMessage(new SendMessageRequest(new Message(
        messageId: Id::uuid(),
        role: Role::User,
        parts: [Part::text('Summarize then translate into French: Pest runs the live HTTPS group.')],
    )));

    expect($events)->not->toBeEmpty();

    $hasText = false;
    $terminal = null;
    $streamed = '';

    foreach ($events as $event) {
        expect($event)->toBeInstanceOf(StreamResponse::class);

        $update = $event->statusUpdate;

        if (! $update instanceof TaskStatusUpdateEvent) {
            continue;
        }

        if ($update->status->message instanceof Message) {
            $chunk = A2AText::fromMessage($update->status->message);

            if ($chunk !== '') {
                $hasText = true;
                $streamed = $chunk;
            }
        }

        if ($update->final || $update->status->state->isTerminal()) {
            $terminal = $update;
        }
    }

    expect($hasText)->toBeTrue()
        ->and($terminal)->toBeInstanceOf(TaskStatusUpdateEvent::class)
        ->and($terminal->status->state)->toBe(TaskState::Completed)
        ->and($streamed)->toContain(WorkerMarker::line());

    $french = A2AText::translationSection($streamed, 'French');
    expect(LanguageHints::matches($french, LanguageHints::french()) ? 'matched' : 'unmatched: '.$french)->toBe('matched');
})->skip(fn (): bool => a2aDemoMissingLiveProviderKey(), a2aDemoLiveSkipReason())->group('live');

it('serves both agent cards over https on the live hosts', function (): void {
    $coordinatorUrl = rtrim((string) env('A2A_COORDINATOR_URL', 'https://coordinator.a2a-demo.test'), '/');
    $workerUrl = rtrim((string) env('A2A_WORKER_URL', 'https://worker.a2a-demo.test'), '/');

    $coordinator = A2A::client($coordinatorUrl)->getExtendedAgentCard(new GetExtendedAgentCardRequest);
    $worker = A2A::client($workerUrl)->getExtendedAgentCard(new GetExtendedAgentCardRequest);

    expect($coordinator->supportedInterfaces[0]->url)->toStartWith('https://')
        ->and($worker->supportedInterfaces[0]->url)->toStartWith('https://');
})->skip(fn (): bool => a2aDemoMissingLiveProviderKey(), a2aDemoLiveSkipReason())->group('live');
