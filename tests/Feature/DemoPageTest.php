<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Types\SendMessageRequest;

it('shows the blade demo page with coordinator and worker https URLs', function (): void {
    $this->serveCoordinator();

    A2A::fake()
        ->withCard('https://coordinator.a2a-demo.test', $this->coordinatorCard())
        ->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $this->get('/')
        ->assertOk()
        ->assertSee('https://coordinator.a2a-demo.test', false)
        ->assertSee('https://worker.a2a-demo.test', false)
        ->assertSee('CoordinatorAgent')
        ->assertSee('WorkerAgent')
        ->assertSee('SummarizeText')
        ->assertSee('summarize-then-translate');
});

it('sends a form message to the coordinator over https', function (): void {
    $this->serveCoordinator();
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $fake = A2A::fake()
        ->withCard('https://coordinator.a2a-demo.test', $this->coordinatorCard())
        ->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $this->from('/')
        ->post('/send', [
            'text' => 'Herd serves two HTTPS hosts.',
            'language' => 'German',
        ])
        ->assertRedirect();

    $fake->assertSent('https://coordinator.a2a-demo.test', function (SendMessageRequest $request): bool {
        $text = (string) $request->message->parts[0]->text;

        return str_contains($text, 'Herd serves two HTTPS hosts.')
            && str_contains($text, 'German');
    });
});
