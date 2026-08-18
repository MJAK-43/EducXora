<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('code') — EduXora</title>
        <style>
            :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
            * { box-sizing: border-box; }
            body { margin: 0; background: #f6f8fb; color: #172033; }
            main { min-height: 100vh; display: grid; place-items: center; padding: 2rem; }
            article { width: 100%; max-width: 34rem; background: #fff; border: 1px solid #d7dee8; border-radius: .875rem; padding: 2.5rem; box-shadow: 0 8px 24px rgba(23,32,51,.10); }
            .brand { color: #2457d6; font-weight: 750; letter-spacing: -.02em; }
            .code { margin: 2rem 0 .5rem; color: #526071; font-size: .875rem; font-weight: 700; letter-spacing: .08em; }
            h1 { margin: 0; font-size: clamp(1.75rem, 6vw, 2.5rem); letter-spacing: -.04em; }
            p { color: #526071; line-height: 1.6; }
            a { display: inline-flex; min-height: 2.75rem; align-items: center; margin-top: 1rem; border-radius: .625rem; padding: 0 1rem; background: #2457d6; color: #fff; font-weight: 700; text-decoration: none; }
            a:focus { outline: 3px solid #6d4aff; outline-offset: 3px; }
        </style>
    </head>
    <body>
        <main>
            <article>
                <div class="brand">EduXora</div>
                <div class="code">ERREUR @yield('code')</div>
                <h1>@yield('title')</h1>
                <p>@yield('message')</p>
                <a href="/">Retour à l'accueil</a>
            </article>
        </main>
    </body>
</html>
