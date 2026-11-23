<?php

declare(strict_types=1);

namespace Tests;

use App\Ai\Agents\CoordinatorAgent;
use App\Ai\Agents\WorkerAgent;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use TiborSrc\A2A\Laravel\A2AManager;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Protocol;
use TiborSrc\A2A\Types\AgentCapabilities;
use TiborSrc\A2A\Types\AgentCard;
use TiborSrc\A2A\Types\AgentInterface;
use TiborSrc\A2A\Types\AgentSkill;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<string, mixed>  $params
     */
    protected function jsonRpc(string $method, array $params = [], mixed $id = 1): TestResponse
    {
        return $this->call('POST', '/', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_'.Protocol::VERSION_HEADER => Protocol::VERSION,
        ], json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ], JSON_THROW_ON_ERROR));
    }

    protected function serveCoordinator(): void
    {
        config([
            'a2a.agent' => CoordinatorAgent::class,
            'a2a.executor' => null,
            'a2a.base_url' => 'https://coordinator.a2a-demo.test',
            'a2a.coordinator_url' => 'https://coordinator.a2a-demo.test',
            'a2a.agents.worker.card' => 'https://worker.a2a-demo.test',
            'a2a.store' => 'memory',
        ]);

        $this->app->forgetInstance(A2AManager::class);
        A2A::clearResolvedInstances();
    }

    protected function serveWorker(): void
    {
        config([
            'a2a.agent' => WorkerAgent::class,
            'a2a.executor' => null,
            'a2a.base_url' => 'https://worker.a2a-demo.test',
            'a2a.store' => 'memory',
        ]);

        $this->app->forgetInstance(A2AManager::class);
        A2A::clearResolvedInstances();
    }

    protected function workerCard(string $url = 'https://worker.a2a-demo.test'): AgentCard
    {
        return new AgentCard(
            name: 'WorkerAgent',
            description: 'A2A worker that summarizes text and translates text into a target language.',
            supportedInterfaces: [
                new AgentInterface($url, Protocol::JSON_RPC_BINDING, Protocol::VERSION),
                new AgentInterface($url, Protocol::HTTP_JSON_BINDING, Protocol::VERSION),
            ],
            version: '0.1.0',
            capabilities: new AgentCapabilities(streaming: true, pushNotifications: true, extendedAgentCard: true),
            defaultInputModes: ['text/plain'],
            defaultOutputModes: ['text/plain'],
            skills: [
                new AgentSkill('SummarizeText', 'SummarizeText', 'Summarize text.', ['tool', 'laravel-ai']),
                new AgentSkill('TranslateText', 'TranslateText', 'Translate text into a target language.', ['tool', 'laravel-ai']),
            ],
        );
    }

    protected function coordinatorCard(string $url = 'https://coordinator.a2a-demo.test'): AgentCard
    {
        return new AgentCard(
            name: 'CoordinatorAgent',
            description: 'Coordinator A2A agent.',
            supportedInterfaces: [
                new AgentInterface($url, Protocol::JSON_RPC_BINDING, Protocol::VERSION),
            ],
            version: '0.1.0',
            capabilities: new AgentCapabilities(streaming: true, pushNotifications: true, extendedAgentCard: true),
            defaultInputModes: ['text/plain'],
            defaultOutputModes: ['text/plain'],
            skills: [
                new AgentSkill('summarize-then-translate', 'summarize-then-translate', 'Compose summarize then translate.', ['skill']),
            ],
        );
    }
}
