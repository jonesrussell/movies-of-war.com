<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Google tag (gtag.js) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-01QMB5XKJF"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-01QMB5XKJF');
        </script>

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Open Graph / Social Media Meta Tags --}}
        @if(isset($page['component']) && $page['component'] === 'Movies/Show' && isset($page['props']['movie']) && is_array($page['props']['movie']))
            @php
                $movie = $page['props']['movie'];
                $title = $movie['title'] ?? 'Movie';
                $releaseYear = $movie['release_year'] ?? '';
                $ogTitle = $title . ($releaseYear ? ' (' . $releaseYear . ')' : '');
                $ogDescription = Str::limit($movie['synopsis'] ?? '', 200);
                $posterUrl = $movie['poster_url'] ?? null;
                $ogImage = $posterUrl && str_starts_with($posterUrl, 'http')
                    ? $posterUrl
                    : url($posterUrl ?? '/images/placeholders/poster-placeholder.png');
                $ogUrl = url('/movies/' . ($movie['slug'] ?? ''));
            @endphp
            <meta property="og:type" content="video.movie">
            <meta property="og:title" content="{{ $ogTitle }}">
            <meta property="og:description" content="{{ $ogDescription }}">
            <meta property="og:image" content="{{ $ogImage }}">
            <meta property="og:url" content="{{ $ogUrl }}">
            <meta property="og:site_name" content="Movies of War">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $ogTitle }}">
            <meta name="twitter:description" content="{{ $ogDescription }}">
            <meta name="twitter:image" content="{{ $ogImage }}">
            <meta name="description" content="{{ $ogDescription }}">
        @else
            {{-- Default OG tags for other pages --}}
            <meta property="og:type" content="website">
            <meta property="og:title" content="Movies of War - Curated War Films Database">
            <meta property="og:description" content="Discover and explore a curated collection of war films from throughout cinema history.">
            <meta property="og:image" content="{{ url('/images/branding/hero-bg.png') }}">
            <meta property="og:url" content="{{ url()->current() }}">
            <meta property="og:site_name" content="Movies of War">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="Movies of War - Curated War Films Database">
            <meta name="twitter:description" content="Discover and explore a curated collection of war films from throughout cinema history.">
            <meta name="twitter:image" content="{{ url('/images/branding/hero-bg.png') }}">
            <meta name="description" content="Discover and explore a curated collection of war films from throughout cinema history.">
        @endif

        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#18181b">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
