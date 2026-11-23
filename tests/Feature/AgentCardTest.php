<?php

declare(strict_types=1);

use App\A2A\CoordinatorOrchestrator;
use App\Ai\Agents\CoordinatorAgent;
use App\Ai\Agents\WorkerAgent;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Protocol;

it('serves the coordinator agent card over an https URL', function (): void {
    $this->serveCoordinator();

    $card = A2A::card();

    expect($card->name)->toBe('CoordinatorAgent')
        ->and($card->skills)->not->toBeEmpty()
        ->and(array_map(fn ($skill) => $skill->id, $card->skills))->toContain('summarize-then-translate');

    foreach ($card->supportedInterfaces as $interface) {
        expect($interface->url)->toStartWith('https://');
    }

    $this->get('/.well-known/agent-card.json')
        ->assertOk()
        ->assertHeader('Content-Type', Protocol::HTTP_JSON_CONTENT_TYPE)
        ->assertJsonPath('name', 'CoordinatorAgent')
        ->assertJsonPath('supportedInterfaces.0.url', 'https://coordinator.a2a-demo.test');
});

it('serves the worker agent card with summarize and translate skills on https', function (): void {
    $this->serveWorker();

    $card = A2A::card();
    $ids = array_map(fn ($skill) => $skill->id, $card->skills);

    expect($card->name)->toBe('WorkerAgent')
        ->and($ids)->toContain('SummarizeText', 'TranslateText')
        ->and($ids)->not->toContain('echo')
        ->and($ids)->not->toContain('EchoTool');

    foreach ($card->supportedInterfaces as $interface) {
        expect($interface->url)->toStartWith('https://worker.a2a-demo.test');
    }

    $this->get('/.well-known/agent-card.json')
        ->assertOk()
        ->assertJsonPath('name', 'WorkerAgent')
        ->assertJsonPath('supportedInterfaces.0.url', 'https://worker.a2a-demo.test');
});

it('configures the coordinator client to use the https worker URL', function (): void {
    $this->serveCoordinator();

    expect(config('a2a.agents.worker.card'))->toBe('https://worker.a2a-demo.test')
        ->and(app(CoordinatorOrchestrator::class)->workerUrl())->toBe('https://worker.a2a-demo.test')
        ->and(config('a2a.agent'))->toBe(CoordinatorAgent::class)
        ->and(config('a2a.base_url'))->toStartWith('https://');

    $fake = A2A::fake()->withCard('https://worker.a2a-demo.test', $this->workerCard());
    $client = A2A::client((string) config('a2a.agents.worker.card'));

    expect($client)->not->toBeNull()
        ->and(config('a2a.agents.worker.card'))->toStartWith('https://');

    $fake->assertNothingSent();
});

it('does not default the worker agent class onto the coordinator host', function (): void {
    $this->serveCoordinator();

    expect(config('a2a.agent'))->toBe(CoordinatorAgent::class)
        ->and(config('a2a.agent'))->not->toBe(WorkerAgent::class);
});
