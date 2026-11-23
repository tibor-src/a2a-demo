<?php

declare(strict_types=1);

use App\A2A\A2AText;
use App\A2A\LanguageHints;
use App\A2A\WorkerMarker;
use App\A2A\WorkerSkills;

it('appends a delimited worker marker once', function (): void {
    $marked = WorkerMarker::append('A short summary');

    expect($marked)->toBe("A short summary\n".WorkerMarker::line())
        ->and(WorkerMarker::append($marked))->toBe($marked)
        ->and(WorkerMarker::extract($marked))->toBe(WorkerMarker::AGENT)
        ->and(WorkerMarker::strip($marked))->toBe('A short summary')
        ->and(WorkerMarker::present('no marker'))->toBeFalse();
});

it('matches spanish and french stopwords without requiring exact model wording', function (): void {
    expect(LanguageHints::matches('Laravel es un marco para los artesanos.', LanguageHints::spanish()))->toBeTrue()
        ->and(LanguageHints::matches('Laravel est un cadre pour les artisans.', LanguageHints::french()))->toBeTrue()
        ->and(LanguageHints::matches('Laravel is a PHP framework.', LanguageHints::spanish()))->toBeFalse()
        ->and(LanguageHints::matches('Laravel is a PHP framework.', LanguageHints::french()))->toBeFalse();
});

it('extracts the translation section from a composed coordinator reply', function (): void {
    $composed = "Summary:\nLaravel is a PHP framework.\n\nTranslation (Spanish):\nLaravel es un marco.\n".WorkerMarker::line();

    expect(A2AText::translationSection($composed, 'Spanish'))->toBe('Laravel es un marco.');
});

it('parses coordinator skill prompts for the worker', function (): void {
    $skills = new WorkerSkills;
    $summarize = "Use the SummarizeText skill. Summarize the following text:\n\nLaravel is a PHP framework for web artisans.";
    $translate = "Use the TranslateText skill. Translate the following summary into French:\n\nLaravel is a PHP framework.";

    expect($skills->isTranslate($summarize))->toBeFalse()
        ->and($skills->payload($summarize))->toBe('Laravel is a PHP framework for web artisans.')
        ->and($skills->isTranslate($translate))->toBeTrue()
        ->and($skills->language($translate))->toBe('French')
        ->and($skills->payload($translate))->toBe('Laravel is a PHP framework.');
});
