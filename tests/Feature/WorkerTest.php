<?php

declare(strict_types=1);

use App\Ai\Agents\WorkerAgent;

it('sends a message to the worker and completes a faked summarize', function (): void {
    $this->serveWorker();
    WorkerAgent::fake(['A short summary of Laravel.']);

    $response = $this->jsonRpc('SendMessage', [
        'message' => [
            'messageId' => 'worker-1',
            'role' => 'ROLE_USER',
            'parts' => [['text' => 'Use the SummarizeText skill. Summarize: Laravel is a PHP framework.']],
        ],
    ])->assertOk()->json();

    expect($response['result']['task']['status']['state'])->toBe('TASK_STATE_COMPLETED')
        ->and($response['result']['task']['status']['message']['parts'][0]['text'])->toBe('A short summary of Laravel.');
});

it('gets a worker task after send', function (): void {
    $this->serveWorker();
    WorkerAgent::fake(['translated text']);

    $created = $this->jsonRpc('SendMessage', [
        'message' => [
            'messageId' => 'worker-get-1',
            'role' => 'ROLE_USER',
            'parts' => [['text' => 'Use the TranslateText skill. Translate into French: hello']],
        ],
    ])->json();

    $taskId = $created['result']['task']['id'];

    $this->jsonRpc('GetTask', ['id' => $taskId])
        ->assertOk()
        ->assertJsonPath('result.id', $taskId);
});
