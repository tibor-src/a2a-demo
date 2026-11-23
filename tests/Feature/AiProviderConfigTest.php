<?php

declare(strict_types=1);

use App\A2A\DemoAi;
use App\Ai\Agents\WorkerAgent;

it('defaults the laravel ai provider to openrouter', function (): void {
    expect(config('ai.default'))->toBe('openrouter')
        ->and(config('ai.providers.openrouter.driver'))->toBe('openrouter')
        ->and(config('ai.providers.openrouter.url'))->toBe('https://openrouter.ai/api/v1')
        ->and(config('ai.providers.openai.driver'))->toBe('openai')
        ->and(config('ai.providers.openai.key'))->toBeEmpty();
});

it('defaults the demo model to llama 3.1 8b instruct', function (): void {
    expect(config('ai.demo_model'))->toBe('meta-llama/llama-3.1-8b-instruct')
        ->and(config('ai.providers.openrouter.models.text.default'))->toBe('meta-llama/llama-3.1-8b-instruct')
        ->and(DemoAi::model())->toBe('meta-llama/llama-3.1-8b-instruct')
        ->and(DemoAi::provider())->toBe('openrouter');
});

it('lets env switch the default provider to openai without removing that provider', function (): void {
    config(['ai.default' => 'openai']);

    expect(DemoAi::provider())->toBe('openai')
        ->and(DemoAi::liveKeyEnv())->toBe('OPENAI_API_KEY')
        ->and(config('ai.providers.openai.driver'))->toBe('openai');
});

it('uses the openrouter key env for the live group by default', function (): void {
    expect(DemoAi::liveKeyEnv())->toBe('OPENROUTER_API_KEY')
        ->and(a2aDemoLiveSkipReason())->toBe('Set OPENROUTER_API_KEY to run the live HTTPS group.');
});

it('exposes the configured model on the worker agent', function (): void {
    $agent = new WorkerAgent;

    expect($agent->provider())->toBe('openrouter')
        ->and($agent->model())->toBe('meta-llama/llama-3.1-8b-instruct');
});

it('documents openrouter first in the env example without storing a key', function (): void {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)->not->toBeFalse()
        ->and($example)->toContain('AI_DEFAULT_PROVIDER=openrouter')
        ->and($example)->toContain('A2A_DEMO_MODEL=meta-llama/llama-3.1-8b-instruct')
        ->and($example)->toContain('OPENROUTER_API_KEY=')
        ->and($example)->toContain('# OPENROUTER_URL=https://openrouter.ai/api/v1')
        ->and($example)->toContain('OPENAI_API_KEY=')
        ->and($example)->not->toContain('OPENROUTER_API_KEY=sk-');
});

it('documents openrouter first in the real API key readme section', function (): void {
    $readme = file_get_contents(base_path('README.md'));

    expect($readme)->not->toBeFalse()
        ->and($readme)->toContain('## Run locally with a real API key')
        ->and($readme)->toContain('Set `OPENROUTER_API_KEY` in `.env`')
        ->and($readme)->toContain('AI_DEFAULT_PROVIDER=openrouter')
        ->and($readme)->toContain('meta-llama/llama-3.1-8b-instruct')
        ->and($readme)->toContain('google/gemma-4-31b-it:free')
        ->and($readme)->toContain('AI_DEFAULT_PROVIDER=openai')
        ->and($readme)->toContain('herd secure')
        ->and($readme)->toContain('worker.a2a-demo.test')
        ->and($readme)->toContain('coordinator.a2a-demo.test')
        ->and($readme)->toContain('php artisan test --group=live')
        ->and(strpos((string) $readme, 'OPENROUTER_API_KEY'))->toBeLessThan(strpos((string) $readme, 'To use OpenAI instead'));
});
