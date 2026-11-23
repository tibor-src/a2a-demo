<?php

declare(strict_types=1);

use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Types\SendMessageRequest;

it('calls the coordinator over https from the artisan client command', function (): void {
    $this->serveCoordinator();

    $fake = A2A::fake()
        ->withCard('https://coordinator.a2a-demo.test', $this->coordinatorCard())
        ->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $this->artisan('a2a:demo', [
        'text' => 'Laravel is a PHP framework.',
        '--language' => 'French',
    ])->assertSuccessful();

    $fake->assertSent('https://coordinator.a2a-demo.test', function (SendMessageRequest $request): bool {
        $text = (string) $request->message->parts[0]->text;

        return str_contains($text, 'Laravel is a PHP framework.')
            && str_contains($text, 'French');
    });
});

it('streams from the coordinator when --stream is passed', function (): void {
    $this->serveCoordinator();

    A2A::fake()
        ->withCard('https://coordinator.a2a-demo.test', $this->coordinatorCard())
        ->withCard('https://worker.a2a-demo.test', $this->workerCard());

    $this->artisan('a2a:demo', [
        'text' => 'Stream this.',
        '--stream' => true,
    ])->assertSuccessful();
});
