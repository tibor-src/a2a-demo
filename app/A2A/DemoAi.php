<?php

declare(strict_types=1);

namespace App\A2A;

final class DemoAi
{
    public const DEFAULT_PROVIDER = 'openrouter';

    public const DEFAULT_MODEL = 'meta-llama/llama-3.1-8b-instruct';

    public static function provider(): string
    {
        $provider = config('ai.default', self::DEFAULT_PROVIDER);

        return is_string($provider) && $provider !== '' ? $provider : self::DEFAULT_PROVIDER;
    }

    public static function model(): string
    {
        $model = config('ai.demo_model', self::DEFAULT_MODEL);

        return is_string($model) && $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    public static function liveKeyEnv(): string
    {
        return self::provider() === 'openai' ? 'OPENAI_API_KEY' : 'OPENROUTER_API_KEY';
    }

    public static function missingLiveKey(): bool
    {
        $key = config('ai.providers.'.self::provider().'.key');

        return blank(is_string($key) ? $key : null);
    }
}
