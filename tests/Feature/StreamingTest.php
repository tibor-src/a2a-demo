<?php

declare(strict_types=1);

use App\Ai\Agents\WorkerAgent;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Protocol;

it('streams a coordinator message over json-rpc', function (): void {
    $this->serveCoordinator();
    A2A::fake()->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $response = $this->jsonRpc('SendStreamingMessage', [
        'message' => [
            'messageId' => 'coord-stream-1',
            'role' => 'ROLE_USER',
            'parts' => [['text' => 'Summarize then translate into Spanish: Streaming proves HTTPS A2A.']],
        ],
    ]);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain(Protocol::SSE_CONTENT_TYPE)
        ->and((string) $response->getContent())->toContain('TASK_STATE_COMPLETED')
        ->and((string) $response->getContent())->toContain('Summary:');
});

it('streams a worker message over json-rpc', function (): void {
    $this->serveWorker();
    WorkerAgent::fake(['streamed summary']);

    $response = $this->jsonRpc('SendStreamingMessage', [
        'message' => [
            'messageId' => 'worker-stream-1',
            'role' => 'ROLE_USER',
            'parts' => [['text' => 'Summarize this.']],
        ],
    ]);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain(Protocol::SSE_CONTENT_TYPE)
        ->and((string) $response->getContent())->toContain('streamed summary');
});
