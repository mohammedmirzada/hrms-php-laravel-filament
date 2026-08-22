<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="35; url={{ route('client.report', ['client' => $client]) }}">
    <title>Refreshing names</title>
    <style>
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font: 14px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #1f2328; background: #f6f8fa; padding: 2rem;
        }
        .box {
            background: #fff; border: 1px solid #d1d9e0; border-radius: 8px;
            padding: 1.75rem 2rem; max-width: 26rem; text-align: center;
        }
        h1 { font-size: 1.1rem; margin: 0 0 .5rem; }
        p { color: #656d76; margin: 0 0 .5rem; }
        code { background: #f6f8fa; padding: .1rem .35rem; border-radius: 4px; }
        a { color: #1f6feb; }
    </style>
</head>
<body>
<div class="box">
    <h1>Asked the device for its user list</h1>
    <p>Device <code>{{ $sn }}</code> picks this up on its next poll, within 30 seconds.</p>
    <p>This page returns to the report automatically.</p>
    <p><a href="{{ route('client.report', ['client' => $client]) }}">Go back now</a></p>
</div>
</body>
</html>
