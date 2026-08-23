<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $clientName }} — Log in</title>
    <style>
        :root {
            --bg: #f4f4f1; --panel: #ffffff; --line: #e4e3de;
            --ink: #1b1b19; --mute: #75746e; --bad: #b3261e; --bad-bg: #fdf1f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; padding: 1.5rem;
            display: flex; align-items: center; justify-content: center;
            font: 15px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--ink); background: var(--bg);
            -webkit-font-smoothing: antialiased;
        }
        .box {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 12px; padding: 2rem 1.9rem;
            width: 100%; max-width: 360px;
        }
        h1 { font-size: 1.15rem; font-weight: 650; margin: 0 0 .15rem; letter-spacing: -.01em; }
        p.sub { color: var(--mute); margin: 0 0 1.7rem; font-size: .9rem; }
        label {
            display: block; margin-bottom: 1.1rem;
            font-size: .78rem; font-weight: 650; color: var(--mute);
            text-transform: uppercase; letter-spacing: .05em;
        }
        input {
            display: block; width: 100%; margin-top: .35rem;
            font: inherit; font-size: .95rem; padding: .55rem .7rem;
            color: var(--ink); background: var(--panel);
            border: 1px solid var(--line); border-radius: 7px;
            text-transform: none; letter-spacing: 0;
        }
        input:focus {
            outline: none; border-color: var(--ink);
            box-shadow: 0 0 0 3px rgba(27,27,25,.08);
        }
        button {
            width: 100%; margin-top: .4rem;
            font: inherit; font-size: .95rem; font-weight: 600; cursor: pointer;
            padding: .6rem; border-radius: 7px;
            background: var(--ink); color: #fff; border: 1px solid var(--ink);
            transition: opacity .12s ease;
        }
        button:hover { opacity: .85; }
        .bad {
            background: var(--bad-bg); color: var(--bad); border: 1px solid #f0c8c5;
            padding: .6rem .75rem; border-radius: 8px;
            margin-bottom: 1.1rem; font-size: .9rem;
        }
    </style>
</head>
<body>

<form class="box" method="post" action="{{ route('client.login', ['client' => $client]) }}">
    @csrf

    <h1>{{ $clientName }}</h1>
    <p class="sub">Attendance</p>

    @if ($error)
        <div class="bad">{{ $error }}</div>
    @endif

    <label>Username
        <input type="text" name="username" autocomplete="username" autofocus required>
    </label>

    <label>Password
        <input type="password" name="password" autocomplete="current-password" required>
    </label>

    <button type="submit">Log in</button>
</form>

</body>
</html>
