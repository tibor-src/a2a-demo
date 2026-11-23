<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\Agents\CoordinatorAgent;
use App\Ai\Agents\WorkerAgent;
use Illuminate\Support\ServiceProvider;
use TiborSrc\A2A\Laravel\A2AManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bindAgentFromHost();
        $this->app['router']->a2a();
    }

    private function bindAgentFromHost(): void
    {
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        $host = request()->getHost();
        $workerUrl = rtrim((string) config('a2a.agents.worker.card', 'https://worker.a2a-demo.test'), '/');
        $coordinatorUrl = rtrim((string) config('a2a.coordinator_url', 'https://coordinator.a2a-demo.test'), '/');

        if (str_contains($host, 'worker.a2a-demo.test') || str_starts_with($host, 'worker.')) {
            config([
                'a2a.agent' => WorkerAgent::class,
                'a2a.base_url' => $workerUrl,
                'a2a.name' => 'Worker',
            ]);
            $this->app->forgetInstance(A2AManager::class);

            return;
        }

        if (str_contains($host, 'coordinator.a2a-demo.test') || str_starts_with($host, 'coordinator.')) {
            config([
                'a2a.agent' => CoordinatorAgent::class,
                'a2a.base_url' => $coordinatorUrl,
                'a2a.name' => 'Coordinator',
            ]);
            $this->app->forgetInstance(A2AManager::class);
        }
    }
}
