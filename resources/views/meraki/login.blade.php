<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $clientName }} — Log in</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font: 15px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #1f2328; background: #f6f8fa; padding: 1.5rem;
        }
        .box {
            background: #fff; border: 1px solid #d1d9e0; border-radius: 10px;
            padding: 2rem; width: 100%; max-width: 360px;
        }
        h1 { font-size: 1.25rem; margin: 0 0 .2rem; }
        p.sub { color: #656d76; margin: 0 0 1.5rem; font-size: .9rem; }
        label { display: block; font-size: .88rem; color: #656d76; margin-bottom: 1rem; }
        input {
            display: block; width: 100%; margin-top: .3rem;
            font: inherit; padding: .55rem .7rem;
            border: 1px solid #d1d9e0; border-radius: 6px; background: #fff; color: #1f2328;
        }
        input:focus { outline: 2px solid #1f6feb; outline-offset: -1px; border-color: #1f6feb; }
        button {
            width: 100%; font: inherit; font-weight: 600; cursor: pointer;
            padding: .6rem; border-radius: 6px;
            background: #1f6feb; color: #fff; border: 1px solid #1f6feb;
        }
        button:hover { background: #1a60cf; }
        .bad {
            background: #ffebe9; color: #cf222e; border: 1px solid #ffcecb;
            padding: .55rem .7rem; border-radius: 6px; margin-bottom: 1rem; font-size: .9rem;
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
