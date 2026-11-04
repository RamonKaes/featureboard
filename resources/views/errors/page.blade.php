@php
    [$title, $text] = match ($status) {
        400 => ['Ungültige Anfrage', 'Die Anfrage enthält Daten, die wir nicht verarbeiten können.'],
        403 => ['Kein Zugriff', 'Du darfst diese Seite nicht aufrufen.'],
        404 => ['Seite nicht gefunden', 'Diese Adresse gibt es nicht, oder der Inhalt wurde verschoben.'],
        419 => ['Sitzung abgelaufen', 'Bitte lade die Seite neu und versuche es noch einmal.'],
        429 => ['Zu viele Anfragen', 'Bitte warte einen Moment und versuche es dann noch einmal.'],
        503 => ['Gerade nicht erreichbar', 'Wir sind gleich wieder da. Bitte versuche es später noch einmal.'],
        default => ['Das hat nicht geklappt', 'Bitte versuche es noch einmal.'],
    };
@endphp
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} - {{ config('app.name') }}</title>
        {{-- Inline styles, because this page must also work when the frontend build is the problem. --}}
        <style>
            :root { color-scheme: light dark; }
            body { margin: 0; font-family: system-ui, sans-serif; background: light-dark(#fff, #0a0a0a); color: light-dark(#0a0a0a, #fafafa); }
            main { display: flex; min-height: 100vh; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; box-sizing: border-box; text-align: center; }
            h1 { margin: 0 0 .5rem; font-size: 1.5rem; }
            p { margin: 0 0 1.5rem; }
            a { color: inherit; }
        </style>
    </head>
    <body>
        <main>
            <h1>{{ $title }}</h1>
            <p>{{ $text }}</p>
            <a href="{{ route('home') }}">Zur Startseite</a>
        </main>
    </body>
</html>
