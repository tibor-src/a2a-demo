<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\A2A\A2AText;
use App\A2A\CoordinatorOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use TiborSrc\A2A\Id;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Types\GetExtendedAgentCardRequest;
use TiborSrc\A2A\Types\Message;
use TiborSrc\A2A\Types\Part;
use TiborSrc\A2A\Types\Role;
use TiborSrc\A2A\Types\SendMessageRequest;
use TiborSrc\A2A\Types\SendMessageResponse;

final class DemoController extends Controller
{
    public function __construct(
        private readonly CoordinatorOrchestrator $orchestrator,
    ) {}

    public function show(): View
    {
        $coordinatorUrl = $this->orchestrator->coordinatorUrl();
        $workerUrl = $this->orchestrator->workerUrl();

        return view('demo', [
            'coordinatorUrl' => $coordinatorUrl,
            'workerUrl' => $workerUrl,
            'coordinatorCard' => $this->coordinatorCard($coordinatorUrl),
            'workerCard' => $this->workerCard(),
            'result' => session('a2a_result'),
            'error' => session('a2a_error'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
            'language' => ['nullable', 'string', 'max:40'],
        ]);

        $language = is_string($validated['language'] ?? null) && $validated['language'] !== ''
            ? $validated['language']
            : 'Spanish';
        $prompt = 'Summarize then translate into '.$language.":\n\n".$validated['text'];
        $coordinatorUrl = $this->orchestrator->coordinatorUrl();

        try {
            $response = A2A::client($coordinatorUrl)->sendMessage(new SendMessageRequest(
                new Message(
                    messageId: Id::uuid(),
                    role: Role::User,
                    parts: [Part::text($prompt)],
                ),
            ));

            return back()->with('a2a_result', $this->resultPayload($response));
        } catch (Throwable $exception) {
            return back()->with('a2a_error', $exception->getMessage())->withInput();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function coordinatorCard(string $coordinatorUrl): ?array
    {
        try {
            return A2A::client($coordinatorUrl)->getExtendedAgentCard(new GetExtendedAgentCardRequest)->toArray();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function workerCard(): ?array
    {
        try {
            return A2A::registry()->card('worker')->toArray();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{text: string, task_id: string|null, state: string|null}
     */
    private function resultPayload(SendMessageResponse $response): array
    {
        return [
            'text' => A2AText::fromResponse($response),
            'task_id' => $response->task?->id,
            'state' => $response->task?->status->state->value,
        ];
    }
}
