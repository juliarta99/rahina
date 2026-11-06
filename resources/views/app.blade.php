<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Meta SEO Dasar --}}
        <meta name="description" content="Rahina - Sistem Informasi Kalender Bali, perhitungan dewasa ayu (hari baik), konsultasi asisten AI berlandaskan lontar wariga, dan ringkasan harian via WhatsApp.">
        <meta name="keywords" content="kalender bali, wariga bali, dewasa ayu, hari baik bali, otonan, wewaran, 30 wuku, sasih bali, penanggal pangelong, lontar sundarigama, lontar tri pramana, tanya wariga, ai kalender bali, rahinan bali, purnama, tilem, kajeng kliwon, nyepi, galungan, kuningan">
        <meta name="author" content="Rahina">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#8C321B">

        {{-- Mobile & PWA Capabilities --}}
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Rahina">

        {{-- Open Graph / Facebook / WhatsApp --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Rahina">
        <meta property="og:title" content="Rahina - Sistem Kalender Bali, Dewasa Ayu & Tanya Wariga AI">
        <meta property="og:description" content="Kearifan Tradisional Bali & Kecerdasan Buatan Modern. Jelajahi kalender, temukan hari baik, tanya wariga AI, dan notifikasi WhatsApp harian.">
        <meta property="og:image" content="{{ asset('assets/images/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Rahina - Kalender Bali & Tanya Wariga AI">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="id_ID">

        {{-- Twitter / X Cards --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Rahina - Sistem Kalender Bali, Dewasa Ayu & Tanya Wariga AI">
        <meta name="twitter:description" content="Kearifan Tradisional Bali & Kecerdasan Buatan Modern. Kalender, dewasa ayu, dan asisten AI wariga.">
        <meta name="twitter:image" content="{{ asset('assets/images/og-image.png') }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "light" }}';

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

        {{-- Favicons & App Icons berbasis Logo Resmi Rahina --}}
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        {{-- JSON-LD Schema.org Structured Data untuk Rich Results Mesin Pencari --}}
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '/#website',
                    'url' => url('/'),
                    'name' => 'Rahina',
                    'description' => 'Sistem Informasi Kalender Bali, Perhitungan Dewasa Ayu, dan Tanya Wariga AI',
                    'inLanguage' => 'id-ID',
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'Rahina',
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => asset('assets/images/logo_rahina.png'),
                        ],
                    ],
                ],
                [
                    '@type' => 'WebApplication',
                    '@id' => url('/') . '/#app',
                    'name' => 'Rahina Kalender Bali & Tanya Wariga',
                    'url' => url('/'),
                    'applicationCategory' => 'ReferenceApplication',
                    'operatingSystem' => 'All',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'IDR',
                    ],
                    'description' => 'Aplikasi komputasi wariga Bali deterministik, penanggalan, penentuan dewasa ayu, dan asisten kecerdasan buatan wariga.',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
        </script>

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Rahina') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
