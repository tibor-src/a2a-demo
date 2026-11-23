<?php

declare(strict_types=1);

use App\Ai\Agents\CoordinatorAgent;
use App\Ai\Agents\WorkerAgent;

$role = env('A2A_ROLE', 'coordinator');
$workerUrl = env('A2A_WORKER_URL', 'https://worker.a2a-demo.test');
$coordinatorUrl = env('A2A_COORDINATOR_URL', 'https://coordinator.a2a-demo.test');
$agent = env('A2A_AGENT');

return [
    'path' => env('A2A_PATH', ''),
    'base_url' => env('A2A_BASE_URL', $role === 'worker' ? $workerUrl : $coordinatorUrl),
    'name' => env('A2A_NAME', $role === 'worker' ? 'Worker' : 'Coordinator'),
    'description' => env(
        'A2A_DESCRIPTION',
        $role === 'worker'
            ? 'A2A worker that summarizes text and translates text into a target language.'
            : 'A2A coordinator that discovers a worker over HTTPS and composes summarize-then-translate.',
    ),
    'version' => env('A2A_VERSION', '0.1.0'),
    'executor' => env('A2A_EXECUTOR'),
    'agent' => is_string($agent) && $agent !== ''
        ? $agent
        : ($role === 'worker' ? WorkerAgent::class : CoordinatorAgent::class),
    'store' => env('A2A_STORE', 'file'),
    'file_store' => env('A2A_FILE_STORE') ?: storage_path('app/a2a-tasks.json'),
    'middleware' => [],
    'queue' => [
        'enabled' => (bool) env('A2A_QUEUE', false),
        'connection' => env('A2A_QUEUE_CONNECTION'),
        'name' => env('A2A_QUEUE_NAME', 'a2a'),
        'timeout' => (int) env('A2A_QUEUE_TIMEOUT', 30),
    ],
    'auth' => [
        'enabled' => (bool) env('A2A_AUTH', false),
        'guard' => env('A2A_AUTH_GUARD', 'sanctum'),
        'abilities' => [],
    ],
    'capabilities' => [
        'streaming' => true,
        'push_notifications' => true,
        'extended_agent_card' => true,
    ],
    'agents' => [
        'worker' => [
            'card' => $workerUrl,
        ],
    ],
    'coordinator_url' => $coordinatorUrl,
    'card_cache_ttl' => 3600,
    'prune_days' => 14,
    'subscribe_timeout' => (int) env('A2A_SUBSCRIBE_TIMEOUT', 15),
];
