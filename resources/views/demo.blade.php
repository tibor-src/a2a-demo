<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>a2a-demo</title>
    <style>
        :root { color-scheme: light dark; --bg: #0f172a; --card: #1e293b; --fg: #e2e8f0; --muted: #94a3b8; --accent: #38bdf8; --ok: #86efac; --err: #fca5a5; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: var(--bg); color: var(--fg); }
        main { max-width: 880px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        h1 { font-size: 1.6rem; margin: 0 0 .35rem; }
        p.lead, .muted { color: var(--muted); }
        a { color: var(--accent); }
        section { background: var(--card); border-radius: 12px; padding: 1.25rem 1.4rem; margin: 1.25rem 0; }
        h2 { font-size: 1.05rem; margin: 0 0 .75rem; }
        label { display: block; font-size: .85rem; margin: .75rem 0 .35rem; color: var(--muted); }
        textarea, input[type=text] { width: 100%; box-sizing: border-box; border: 1px solid #334155; background: #0b1220; color: var(--fg); border-radius: 8px; padding: .7rem .8rem; font: inherit; }
        textarea { min-height: 9rem; }
        button { margin-top: 1rem; background: var(--accent); color: #0f172a; border: 0; border-radius: 8px; padding: .65rem 1rem; font-weight: 650; cursor: pointer; }
        pre { white-space: pre-wrap; word-break: break-word; background: #0b1220; padding: .9rem 1rem; border-radius: 8px; font-size: .85rem; }
        .ok { color: var(--ok); }
        .err { color: var(--err); }
        ul { margin: .4rem 0 0; padding-left: 1.2rem; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .9em; }
    </style>
</head>
<body>
<main>
    <h1>a2a-demo</h1>
    <p class="lead">Two A2A agents on separate HTTPS hosts. This page talks to the coordinator over HTTPS; the coordinator discovers the worker card and delegates summarize then translate.</p>

    <section>
        <h2>HTTPS hosts</h2>
        <p>Coordinator: <a href="{{ $coordinatorUrl }}/.well-known/agent-card.json"><code>{{ $coordinatorUrl }}</code></a></p>
        <p>Worker: <a href="{{ $workerUrl }}/.well-known/agent-card.json"><code>{{ $workerUrl }}</code></a></p>
    </section>

    <section>
        <h2>Coordinator agent card</h2>
        @if ($coordinatorCard)
            <p><strong>{{ $coordinatorCard['name'] ?? 'Coordinator' }}</strong></p>
            <p class="muted">{{ $coordinatorCard['description'] ?? '' }}</p>
            <ul>
                @foreach (($coordinatorCard['skills'] ?? []) as $skill)
                    <li><code>{{ $skill['id'] ?? '' }}</code> — {{ $skill['description'] ?? '' }}</li>
                @endforeach
            </ul>
        @else
            <p class="err">Could not load the coordinator card over HTTPS. Serve <code>coordinator.a2a-demo.test</code> with <code>herd secure</code>.</p>
        @endif
    </section>

    <section>
        <h2>Worker agent card</h2>
        @if ($workerCard)
            <p><strong>{{ $workerCard['name'] ?? 'Worker' }}</strong></p>
            <p class="muted">{{ $workerCard['description'] ?? '' }}</p>
            <ul>
                @foreach (($workerCard['skills'] ?? []) as $skill)
                    <li><code>{{ $skill['id'] ?? '' }}</code> — {{ $skill['description'] ?? '' }}</li>
                @endforeach
            </ul>
        @else
            <p class="err">Could not load the worker card over HTTPS from <code>A2A_WORKER_URL</code>.</p>
        @endif
    </section>

    <section>
        <h2>Send a message to the coordinator</h2>
        <form method="post" action="{{ route('demo.send') }}">
            @csrf
            <label for="text">Text</label>
            <textarea id="text" name="text" required>{{ old('text', 'Laravel is a PHP framework for web artisans. It ships routing, Eloquent, queues, and now an AI SDK.') }}</textarea>
            <label for="language">Target language</label>
            <input id="language" type="text" name="language" value="{{ old('language', 'Spanish') }}">
            <button type="submit">Summarize then translate over HTTPS</button>
        </form>

        @if ($error)
            <p class="err">{{ $error }}</p>
        @endif

        @if ($result)
            <p class="ok">Task {{ $result['task_id'] ?? 'n/a' }} {{ $result['state'] ?? '' }}</p>
            <pre>{{ $result['text'] ?? '' }}</pre>
        @endif
    </section>
</main>
</body>
</html>
