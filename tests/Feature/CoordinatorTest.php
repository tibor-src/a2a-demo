<?php

declare(strict_types=1);

use App\A2A\CoordinatorOrchestrator;
use App\Ai\Agents\WorkerAgent;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Types\SendMessageRequest;

it('sends a coordinator message that delegates to the https worker URL', function (): void {
    $this->serveCoordinator();
    WorkerAgent::fake();

    $fake = A2A::fake()->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $response = $this->jsonRpc('SendMessage', [
        'message' => [
            'messageId' => 'coord-1',
            'role' => 'ROLE_USER',
            'parts' => [['text' => 'Summarize then translate into French: Laravel is a PHP web framework.']],
        ],
    ])->assertOk()->json();

    expect($response['result']['task']['status']['state'])->toBe('TASK_STATE_COMPLETED')
        ->and($response['result']['task']['status']['message']['parts'][0]['text'])->toContain('Summary:')
        ->and($response['result']['task']['status']['message']['parts'][0]['text'])->toContain('Translation (French):');

    $fake->assertSent('https://worker.a2a-demo.test');
    $fake->assertSentCount(2);
});

it('gets a coordinator task after send', function (): void {
    $this->serveCoordinator();
    A2A::fake()->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $created = $this->jsonRpc('SendMessage', [
        'message' => [
            'messageId' => 'coord-get-1',
            'role' => 'ROLE_USER',
            'parts' => [['text' => 'Hello from the coordinator.']],
        ],
    ])->assertOk()->json();

    $taskId = $created['result']['task']['id'];

    $this->jsonRpc('GetTask', ['id' => $taskId])
        ->assertOk()
        ->assertJsonPath('result.id', $taskId)
        ->assertJsonPath('result.status.state', 'TASK_STATE_COMPLETED');
});

it('asserts outbound coordinator A2A uses the configured https worker base URL', function (): void {
    $this->serveCoordinator();
    $fake = A2A::fake()->withCard('https://worker.a2a-demo.test', $this->workerCard());

    app(CoordinatorOrchestrator::class)->compose('Summarize then translate into Spanish: The rain in Spain.');

    $fake->assertSent('https://worker.a2a-demo.test', function (SendMessageRequest $request): bool {
        return str_contains((string) $request->message->parts[0]->text, 'Summarize');
    });
    $fake->assertSent('https://worker.a2a-demo.test', function (SendMessageRequest $request): bool {
        return str_contains((string) $request->message->parts[0]->text, 'Translate');
    });
});
