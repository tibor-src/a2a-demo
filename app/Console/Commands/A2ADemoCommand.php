<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\A2A\A2AText;
use App\A2A\CoordinatorOrchestrator;
use Illuminate\Console\Command;
use TiborSrc\A2A\Id;
use TiborSrc\A2A\Laravel\Facades\A2A;
use TiborSrc\A2A\Types\GetExtendedAgentCardRequest;
use TiborSrc\A2A\Types\GetTaskRequest;
use TiborSrc\A2A\Types\Message;
use TiborSrc\A2A\Types\Part;
use TiborSrc\A2A\Types\Role;
use TiborSrc\A2A\Types\SendMessageRequest;
use TiborSrc\A2A\Types\Task;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

final class A2ADemoCommand extends Command
{
    protected $signature = 'a2a:demo
                            {text : Text to summarize, then translate}
                            {--language=Spanish : Target language for the translation}
                            {--stream : Use SendStreamingMessage against the coordinator}';

    protected $description = 'Call the coordinator over HTTPS to summarize then translate via the worker';

    public function handle(CoordinatorOrchestrator $orchestrator): int
    {
        $text = (string) $this->argument('text');
        $language = (string) $this->option('language');
        $coordinatorUrl = $orchestrator->coordinatorUrl();
        $workerUrl = $orchestrator->workerUrl();

        if (! str_starts_with($coordinatorUrl, 'https://')) {
            warning("Coordinator URL must be https, got [{$coordinatorUrl}].");

            return self::FAILURE;
        }

        $client = A2A::client($coordinatorUrl);
        $card = $client->getExtendedAgentCard(new GetExtendedAgentCardRequest);

        info("Coordinator: {$card->name} at {$coordinatorUrl}");
        note("Worker URL (A2A_WORKER_URL): {$workerUrl}");

        $prompt = "Summarize then translate into {$language}:\n\n{$text}";
        $request = new SendMessageRequest(new Message(
            messageId: Id::uuid(),
            role: Role::User,
            parts: [Part::text($prompt)],
        ));

        if ($this->option('stream')) {
            $events = $client->sendStreamingMessage($request);
            $this->line('Streaming '.$this->count($events).' event(s) from the coordinator...');

            foreach ($events as $event) {
                if ($event->message instanceof Message) {
                    $this->line(A2AText::fromMessage($event->message));
                }

                if ($event->task instanceof Task) {
                    $this->line(A2AText::fromTask($event->task));
                    $this->line('Task '.$event->task->id.' state '.$event->task->status->state->value);
                }
            }

            return self::SUCCESS;
        }

        $response = $client->sendMessage($request);
        $this->line(A2AText::fromResponse($response));

        if ($response->task instanceof Task) {
            $task = $client->getTask(new GetTaskRequest($response->task->id));
            note('GetTask '.$task->id.' state '.$task->status->state->value);
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<mixed>  $events
     */
    private function count(array $events): int
    {
        return count($events);
    }
}
