<?php

declare(strict_types=1);

use App\A2A\CoordinatorOrchestrator;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Protocol;
use TiborSrc\A2A\Types\AgentCapabilities;
use TiborSrc\A2A\Types\AgentCard;
use TiborSrc\A2A\Types\AgentInterface;
use TiborSrc\A2A\Types\AgentSkill;

it('returns a json-rpc error for a missing task', function (): void {
    $this->serveCoordinator();
    A2A::fake()->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $this->jsonRpc('GetTask', ['id' => 'missing-task'])
        ->assertOk()
        ->assertJsonPath('error.code', -32001);
});

it('rejects a worker card that is missing the summarize skill', function (): void {
    $this->serveCoordinator();

    A2A::fake()->withCard('https://worker.a2a-demo.test', new AgentCard(
        name: 'BrokenWorker',
        description: 'No skills.',
        supportedInterfaces: [
            new AgentInterface('https://worker.a2a-demo.test', Protocol::JSON_RPC_BINDING, Protocol::VERSION),
        ],
        version: '0.1.0',
        capabilities: new AgentCapabilities,
        defaultInputModes: ['text/plain'],
        defaultOutputModes: ['text/plain'],
        skills: [
            new AgentSkill('other', 'other', 'Not summarize.', ['x']),
        ],
    ));

    expect(fn () => app(CoordinatorOrchestrator::class)->compose('Please summarize this.'))
        ->toThrow(RuntimeException::class, 'summarize');
});

it('rejects a non-https worker URL', function (): void {
    $this->serveCoordinator();
    config(['a2a.agents.worker.card' => 'http://worker.a2a-demo.test']);

    expect(fn () => app(CoordinatorOrchestrator::class)->compose('hello'))
        ->toThrow(RuntimeException::class, 'https');
});
