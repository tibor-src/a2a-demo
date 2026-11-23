<?php

declare(strict_types=1);

use App\A2A\A2AText;
use TiborSrc\A2A\Id;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Protocol;
use TiborSrc\A2A\Types\GetExtendedAgentCardRequest;
use TiborSrc\A2A\Types\GetTaskRequest;
use TiborSrc\A2A\Types\Message;
use TiborSrc\A2A\Types\Part;
use TiborSrc\A2A\Types\Role;
use TiborSrc\A2A\Types\SendMessageRequest;
use TiborSrc\A2A\Types\Task;

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
    expect($text)->toContain('Summary:');

    if ($response->task instanceof Task) {
        $task = $client->getTask(new GetTaskRequest($response->task->id));
        expect($task->id)->toBe($response->task->id);
    }

    $events = $client->sendStreamingMessage(new SendMessageRequest(new Message(
        messageId: Id::uuid(),
        role: Role::User,
        parts: [Part::text('Summarize then translate into French: Pest runs the live HTTPS group.')],
    )));

    expect($events)->not->toBeEmpty();
})->skip(fn (): bool => blank((string) env('OPENAI_API_KEY')), 'Set OPENAI_API_KEY to run the live HTTPS group.')->group('live');

it('serves both agent cards over https on the live hosts', function (): void {
    $coordinatorUrl = rtrim((string) env('A2A_COORDINATOR_URL', 'https://coordinator.a2a-demo.test'), '/');
    $workerUrl = rtrim((string) env('A2A_WORKER_URL', 'https://worker.a2a-demo.test'), '/');

    $coordinator = file_get_contents($coordinatorUrl.Protocol::AGENT_CARD_PATH);
    $worker = file_get_contents($workerUrl.Protocol::AGENT_CARD_PATH);

    expect($coordinator)->not->toBeFalse()
        ->and($worker)->not->toBeFalse();

    $coordinatorCard = json_decode((string) $coordinator, true);
    $workerCard = json_decode((string) $worker, true);

    expect($coordinatorCard['supportedInterfaces'][0]['url'])->toStartWith('https://')
        ->and($workerCard['supportedInterfaces'][0]['url'])->toStartWith('https://');
})->skip(fn (): bool => blank((string) env('OPENAI_API_KEY')), 'Set OPENAI_API_KEY to run the live HTTPS group.')->group('live');
