<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'dark') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Inline script to detect appearance preference and apply it immediately --}}
    <script>
        (function () {
            const appearance = '{{ $appearance ?? "dark" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            } else if (appearance === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: rgb(250, 250, 250);
        }

        html.dark {
            background-color: rgb(0, 0, 0);
        }
    </style>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Clipper-MS">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    {{-- Load font asynchronously to avoid render blocking --}}
    <link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet"></noscript>

    {{-- SEO & Open Graph --}}
    <link rel="canonical" href="{{ $metaCanonical }}" />
    <meta name="description" content="{{ $metaDescription }}">

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Clipper-MS" />
    <meta property="og:title" content="{{ $metaTitle }}" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:image" content="{{ $metaImage }}" />
    <meta property="og:url" content="{{ $metaCanonical }}" />


    <meta name="twitter:card" content="summary_large_image" />

    <!-- Google tag (gtag.js) — Consent Mode v2: analytics blocked by default until user opts in (AVG/GDPR) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-49X7GJ790J"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        // Default to denied — analytics only fire after explicit user consent
        gtag('consent', 'default', {
            'analytics_storage': 'denied',
            'wait_for_update': 500
        });

        gtag('js', new Date());
        gtag('config', 'G-49X7GJ790J');
    </script>

    @routes @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
