<?php

declare(strict_types=1);

namespace App\A2A;

use TiborSrc\A2A\Types\Message;
use TiborSrc\A2A\Types\SendMessageResponse;
use TiborSrc\A2A\Types\Task;

final class A2AText
{
    public static function fromResponse(SendMessageResponse $response): string
    {
        if ($response->message instanceof Message) {
            $text = self::fromMessage($response->message);

            if ($text !== '') {
                return $text;
            }
        }

        if ($response->task instanceof Task) {
            return self::fromTask($response->task);
        }

        return '';
    }

    public static function fromTask(Task $task): string
    {
        if ($task->status->message instanceof Message) {
            $text = self::fromMessage($task->status->message);

            if ($text !== '') {
                return $text;
            }
        }

        foreach ($task->artifacts as $artifact) {
            foreach ($artifact->parts as $part) {
                if ($part->text !== null && $part->text !== '') {
                    return $part->text;
                }
            }
        }

        return $task->status->state->value;
    }

    public static function fromMessage(Message $message): string
    {
        $chunks = [];

        foreach ($message->parts as $part) {
            if ($part->text !== null && $part->text !== '') {
                $chunks[] = $part->text;
            }
        }

        return implode("\n", $chunks);
    }

    public static function translationSection(string $composed, string $language): string
    {
        $label = 'Translation ('.$language.'):';
        $position = mb_stripos($composed, $label);

        if ($position === false) {
            return trim(WorkerMarker::strip($composed));
        }

        return trim(WorkerMarker::strip(mb_substr($composed, $position + mb_strlen($label))));
    }
}
