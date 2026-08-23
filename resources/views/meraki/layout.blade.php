<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $clientName }} — @yield('title', 'Attendance')</title>
    <style>
        :root {
            --bg:      #f4f4f1;
            --panel:   #ffffff;
            --line:    #e4e3de;
            --line-2:  #efeeea;
            --ink:     #1b1b19;
            --mute:    #75746e;
            --ok:      #2f6b4a;
            --extra:   #a15c14;
            --bad:     #b3261e;
            --bad-bg:  #fdf1f0;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0; padding: 1.75rem 1.5rem 3rem;
            font: 15px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--ink); background: var(--bg);
            -webkit-font-smoothing: antialiased;
        }
        .wrap { max-width: 1500px; margin: 0 auto; }

        /* ---------------------------------------------------------- header */

        header {
            display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
            padding-bottom: .9rem; margin-bottom: 1.4rem;
            border-bottom: 1px solid var(--line);
        }
        h1 {
            font-size: 1.15rem; font-weight: 650; margin: 0;
            letter-spacing: -.01em;
        }
        h1 a {
            color: inherit; text-decoration: none;
            border-bottom: 2px solid transparent;
        }
        h1 a:hover { border-bottom-color: var(--ink); }
        .sub { color: var(--mute); margin: 0; font-size: .92rem; }

        nav { display: flex; align-items: center; gap: .25rem; margin-left: auto; }
        nav a {
            font-size: .9rem; text-decoration: none; color: var(--mute);
            padding: .3rem .1rem; margin: 0 .55rem;
            border-bottom: 2px solid transparent;
            transition: color .12s ease, border-color .12s ease;
        }
        nav a:hover { color: var(--ink); }
        nav a.on { color: var(--ink); font-weight: 600; border-bottom-color: var(--ink); }

        nav form { margin-left: .9rem; }
        nav button {
            font: inherit; font-size: .88rem; cursor: pointer;
            padding: .32rem .8rem; border-radius: 999px;
            color: var(--bad); background: transparent;
            border: 1px solid #f0c8c5;
            transition: background .12s ease, color .12s ease, border-color .12s ease;
        }
        nav button:hover { background: var(--bad); border-color: var(--bad); color: #fff; }

        /* ------------------------------------------------------ filter bar */

        form.bar {
            display: flex; gap: 1.1rem; flex-wrap: wrap; align-items: flex-end;
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 10px; padding: .9rem 1rem; margin-bottom: 1.25rem;
        }
        label {
            display: flex; flex-direction: column; gap: .3rem;
            color: var(--mute); font-size: .8rem;
            text-transform: uppercase; letter-spacing: .05em; font-weight: 600;
        }
        select, input {
            font: inherit; font-size: .92rem; padding: .45rem .6rem;
            color: var(--ink); background: var(--panel);
            border: 1px solid var(--line); border-radius: 7px;
            text-transform: none; letter-spacing: 0;
        }
        select:focus, input:focus {
            outline: none; border-color: var(--ink);
            box-shadow: 0 0 0 3px rgba(27,27,25,.08);
        }
        button.go {
            font: inherit; font-size: .92rem; font-weight: 550; cursor: pointer;
            padding: .48rem 1.1rem; border-radius: 7px;
            background: var(--ink); color: #fff; border: 1px solid var(--ink);
            transition: opacity .12s ease;
        }
        button.go:hover { opacity: .85; }

        /* ----------------------------------------------------------- boxes */

        .card {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 10px; overflow: hidden;
        }
        .ok, .bad-box, .info {
            padding: .65rem .9rem; border-radius: 8px;
            margin-bottom: 1rem; font-size: .92rem;
        }
        .ok      { background: #eef6f1; color: var(--ok);  border: 1px solid #cde3d7; }
        .bad-box { background: var(--bad-bg); color: var(--bad); border: 1px solid #f0c8c5; }
        .info    { background: #faf9f7; color: var(--mute); border: 1px solid var(--line); }

        .note { color: var(--mute); font-size: .88rem; margin: .9rem 0 0; }
    </style>
</head>
<body>
<div class="wrap">

    <header>
        <h1>
            <a href="{{ route('client.report', ['client' => $client]) }}">{{ $clientName }}</a>
        </h1>

        <p class="sub">@yield('subtitle')</p>

        <nav>
            <a href="{{ route('client.report', ['client' => $client]) }}"
               class="{{ request()->routeIs('client.report') ? 'on' : '' }}">Calendar</a>
            <a href="{{ route('client.overtime', ['client' => $client]) }}"
               class="{{ request()->routeIs('client.overtime') ? 'on' : '' }}">Overtime</a>
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
