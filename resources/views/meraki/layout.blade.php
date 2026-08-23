<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $clientName }} — @yield('title', 'Attendance')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 1.5rem;
            font: 15px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #1f2328; background: #f6f8fa;
        }
        .wrap { max-width: 1500px; margin: 0 auto; }

        header { display: flex; align-items: baseline; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
        h1 { font-size: 1.35rem; margin: 0; }
        .sub { color: #656d76; margin: 0; }

        nav { display: flex; gap: .3rem; align-items: center; margin-left: auto; }
        nav a, nav button {
            font: inherit; font-size: .88rem; text-decoration: none;
            padding: .35rem .7rem; border-radius: 6px;
            color: #1f6feb; background: none; border: 1px solid transparent; cursor: pointer;
        }
        nav a:hover, nav button:hover { background: #eaeef2; }
        nav a.on { background: #1f6feb; color: #fff; }

        form.bar {
            display: flex; gap: .6rem; flex-wrap: wrap; align-items: center;
            background: #fff; border: 1px solid #d1d9e0; border-radius: 8px;
            padding: .8rem; margin-bottom: 1rem;
        }
        label { display: flex; gap: .4rem; align-items: center; color: #656d76; font-size: .9rem; }
        select, input, button.go {
            font: inherit; padding: .45rem .6rem;
            border: 1px solid #d1d9e0; border-radius: 6px; background: #fff; color: #1f2328;
        }
        button.go { background: #1f6feb; color: #fff; border-color: #1f6feb; cursor: pointer; }

        .card { background: #fff; border: 1px solid #d1d9e0; border-radius: 8px; overflow: hidden; }
        .note { color: #656d76; font-size: .85rem; margin: .75rem 0 0; }
        .ok { background: #dafbe1; color: #1a7f37; border: 1px solid #aceebb;
              padding: .6rem .8rem; border-radius: 6px; margin-bottom: 1rem; }
        .bad { background: #ffebe9; color: #cf222e; border: 1px solid #ffcecb;
               padding: .6rem .8rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="wrap">

    <header>
        <h1>{{ $clientName }}</h1>
        <p class="sub">@yield('subtitle')</p>

        <nav>
            <a href="{{ route('client.report', ['client' => $client]) }}"
               class="{{ request()->routeIs('client.report') ? 'on' : '' }}">Calendar</a>
            <a href="{{ route('client.log', ['client' => $client]) }}"
               class="{{ request()->routeIs('client.log') ? 'on' : '' }}">Punch list</a>
            <a href="{{ route('client.settings', ['client' => $client]) }}"
               class="{{ request()->routeIs('client.settings') ? 'on' : '' }}">Settings</a>
            <form method="post" action="{{ route('client.logout', ['client' => $client]) }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </nav>
    </header>

    @yield('content')

</div>
</body>
</html>
