<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#308330">
        <meta name="description" content="FAKT belső szervezeti alkalmazás">
        <link rel="manifest" href="/manifest.webmanifest">

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

        <link rel="icon" href="/fakt-icon.svg" type="image/svg+xml">

        @php
            $manifestPath = public_path('build/manifest.json');
            $viteManifest = file_exists($manifestPath)
                ? json_decode(file_get_contents($manifestPath), true)
                : [];
        @endphp

        @foreach (['resources/css/app.css', 'resources/js/app.ts'] as $entryName)
            @if (isset($viteManifest[$entryName]))
                @foreach (($viteManifest[$entryName]['css'] ?? []) as $cssFile)
                    <link rel="stylesheet" href="{{ asset('build/'.$cssFile) }}">
                @endforeach

                @if (substr($viteManifest[$entryName]['file'], -4) === '.css')
                    <link rel="stylesheet" href="{{ asset('build/'.$viteManifest[$entryName]['file']) }}">
                @else
                    <script type="module" src="{{ asset('build/'.$viteManifest[$entryName]['file']) }}"></script>
                @endif
            @endif
        @endforeach

        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
