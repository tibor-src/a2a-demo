# a2a-demo

Public Laravel demo that consumes [`tibor-src/a2a-laravel`](https://github.com/tibor-src/a2a-laravel) **v0.1.0** and proves Agent2Agent (A2A) over **HTTPS** between two hosts.

This is a consumer app. It does not change `tibor-src/a2a-laravel` or `tibor-src/a2a-php`. `tibor-src/a2a-laravel` is **not on Packagist**; this app requires it from GitHub via Composer VCS. `tibor-src/a2a-php` ^0.1.0 resolves from Packagist.

## Requirements

- PHP 8.5+
- Laravel 13
- [Laravel Herd](https://herd.laravel.com) (or another reverse proxy) for local HTTPS `.test` sites

## What it proves

Two A2A agents, each with its own agent card, on **separate HTTPS hosts**:

| Host | Role | Skills |
| --- | --- | --- |
| `https://worker.a2a-demo.test` | Worker | `SummarizeText`, `TranslateText` (target language param) via the Laravel AI SDK. No echo skill. |
| `https://coordinator.a2a-demo.test` | Coordinator | Discovers the worker card over HTTPS, picks summarize then translate, delegates with `A2A::client` using `A2A_WORKER_URL` / the card URL, tracks the worker task, and returns a composed artifact. |

The same codebase backs both sites. HTTP host selects the agent (`worker.a2a-demo.test` vs `coordinator.a2a-demo.test`). `A2A_WORKER_URL` can point at the local Herd worker or a remote `https://` worker. The Artisan command and Blade page talk to the **coordinator** over HTTPS, not the worker.

There is no in-process or internal route dispatch for the coordinator-to-worker hop.

## Install

```bash
git clone https://github.com/tibor-src/a2a-demo.git
cd a2a-demo
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`composer.json` already declares a VCS repository for `https://github.com/tibor-src/a2a-laravel.git` and `require`s `tibor-src/a2a-laravel`. After a fresh clone, `composer install` is enough. To add the package to another Laravel app:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/tibor-src/a2a-laravel.git" }
    ]
}
```

```bash
composer require tibor-src/a2a-laravel
php artisan vendor:publish --tag=a2a-config
php artisan vendor:publish --tag=a2a-migrations
php artisan vendor:publish --provider="Laravel\\Ai\\AiServiceProvider"
php artisan migrate
```

Do not `composer require tibor-src/a2a-laravel` from Packagist; it is not published there.

## HTTPS / Herd setup

Park or link this directory twice so Herd serves two hosts, then secure both:

```bash
cd /path/to/a2a-demo
herd link worker.a2a-demo
herd link coordinator.a2a-demo
herd secure worker.a2a-demo.test
herd secure coordinator.a2a-demo.test
```

Set `.env` (never commit `.env`; only `.env.example` is in git):

```
A2A_WORKER_URL=https://worker.a2a-demo.test
A2A_COORDINATOR_URL=https://coordinator.a2a-demo.test
A2A_BASE_URL=https://coordinator.a2a-demo.test
A2A_ROLE=coordinator
A2A_STORE=memory
A2A_QUEUE=false
```

Requests to `worker.a2a-demo.test` serve `WorkerAgent`. Requests to `coordinator.a2a-demo.test` serve `CoordinatorAgent`. Artisan uses `A2A_ROLE` / `A2A_COORDINATOR_URL`.

### Curl: agent cards

```bash
curl -sS https://worker.a2a-demo.test/.well-known/agent-card.json
curl -sS https://coordinator.a2a-demo.test/.well-known/agent-card.json
```

### Curl: send a message to the coordinator, then get the task

```bash
curl -sS -X POST https://coordinator.a2a-demo.test/ \
  -H 'Content-Type: application/json' \
  -H 'A2A-Version: 1.0' \
  -d '{"jsonrpc":"2.0","id":1,"method":"SendMessage","params":{"message":{"messageId":"demo-1","role":"ROLE_USER","parts":[{"text":"Summarize then translate into Spanish: Laravel is a PHP framework for web artisans."}]}}}'
```

Copy the returned `result.task.id` and:

```bash
curl -sS -X POST https://coordinator.a2a-demo.test/ \
  -H 'Content-Type: application/json' \
  -H 'A2A-Version: 1.0' \
  -d '{"jsonrpc":"2.0","id":2,"method":"GetTask","params":{"id":"TASK_ID"}}'
```

## Run locally with a real API key

1. Complete **HTTPS / Herd setup** (`herd secure` on `worker.a2a-demo.test` and `coordinator.a2a-demo.test`).
2. `cp .env.example .env` and `php artisan key:generate` if you have not already.
3. Set `OPENROUTER_API_KEY` in `.env`. The default Laravel AI SDK provider is OpenRouter (`AI_DEFAULT_PROVIDER=openrouter`) via the `openrouter` driver. The default model is `meta-llama/llama-3.1-8b-instruct` (`A2A_DEMO_MODEL`). Optional `OPENROUTER_URL` overrides the driver's default `https://openrouter.ai/api/v1`. Never commit `.env` or paste a key into git, logs, or the README.
4. To use OpenAI instead, set `AI_DEFAULT_PROVIDER=openai`, `OPENAI_API_KEY`, and an OpenAI model id in `A2A_DEMO_MODEL`. Keep the OpenAI provider config; do not remove it.
5. Optional OpenRouter fallback (not the default): `google/gemma-4-31b-it:free`. Official caveats: `:free` variants are capped at 20 requests per minute and 50 requests per day; after purchasing at least $10 of credits (the higher cap starts at $9 to absorb top-up fees) the daily cap rises to 1,000; failed attempts count toward the daily cap; a negative account balance can block even free models until credits are added; free variants may have different rate limits or availability than paid versions; they are usually not suitable for production; if a `:free` model is listed in a `models` fallback array, a fallback to a paid model is billed. See [OpenRouter limits](https://openrouter.ai/docs/api_reference/limits), [free models](https://openrouter.ai/collections/free-models), and [free variants](https://openrouter.ai/docs/guides/routing/model-variants/free).
6. Set `A2A_WORKER_URL=https://worker.a2a-demo.test` (or a remote `https://` worker) and `A2A_COORDINATOR_URL=https://coordinator.a2a-demo.test`.
7. Hit both agent cards over HTTPS:

   ```bash
   curl -sS https://worker.a2a-demo.test/.well-known/agent-card.json | jq .name
   curl -sS https://coordinator.a2a-demo.test/.well-known/agent-card.json | jq .name
   ```

8. Run the Artisan client. It calls the **coordinator** over HTTPS; the coordinator then summarizes and translates through the worker over HTTPS:

   ```bash
   php artisan a2a:demo "Laravel is a PHP framework for web artisans." --language=Spanish
   php artisan a2a:demo "Laravel is a PHP framework for web artisans." --language=French --stream
   ```

9. Open `https://coordinator.a2a-demo.test`, confirm both cards, and submit the Blade form. That POST uses `A2A::client` against the coordinator HTTPS URL.

Live Pest (not run by default). It runs when the key for the configured provider is present (`OPENROUTER_API_KEY` when `AI_DEFAULT_PROVIDER` is `openrouter`, `OPENAI_API_KEY` when it is `openai`) and auto-skips otherwise:

```bash
php artisan test --group=live
```

Default `php artisan test` stays faked and offline. It does not call providers or the Herd hosts.

## Tests

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
```

Default feature tests fake the Laravel AI SDK (`WorkerAgent::fake()`) and may fake outbound A2A (`A2A::fake()`). They still assert agent card URLs are `https://` and that the coordinator client uses `A2A_WORKER_URL`.

A2A TCK: `php artisan a2a:tck` is documented for the package workbench, not as a drop-in for this third-party app. This demo does not run the official [a2a-tck](https://github.com/a2aproject/a2a-tck) suite.

## License

MIT.
