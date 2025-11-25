<!DOCTYPE html>
<html lang="uk">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- Primary Meta Tags -->
        <title>@yield('title', 'Гірськолижні Тури Під Ключ | Prostar')</title>
        <meta name="title" content="@yield('meta_title', 'Гірськолижні Тури Під Ключ | Prostar')" />
        <meta
            name="description"
            content="@yield('meta_description', 'Prostartravel.com.ua - організація гірськолижних турів під ключ для новачків, професіоналів, та сімейного відпочинку. Сезон 2025-2026. Альпійські курорти (Австрія, Швейцарія), Карпатські траси (Буковель, Драгобрат), Скандинавські курорти (Норвегія, Швеція). Організація корпоративного відпочинку. +38(098) 12-12-011')"
        />
        <meta
            name="keywords"
            content="@yield('meta_keywords', 'гірськолижні тури, тури під ключ, снігові тури, гірськолижні курорти, Prostar, RADUGAUA, SNІGOWEEK')"
        />
        <meta name="author" content="ProStar Travel" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="@yield('canonical', config('app.url'))" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="@yield('og_url', config('app.url'))" />
        <meta
            property="og:title"
            content="@yield('og_title', 'Гірськолижні Тури Під Ключ | Prostar')"
        />
        <meta
            property="og:description"
            content="@yield('og_description', 'Prostartravel.com - організація гірськолижних турів під ключ для новачків, професіоналів, та сімейного відпочинку. Організація корпоративного відпочинку.')"
        />
        <meta
            property="og:image"
            content="@yield('og_image', config('app.url') . '/images/hero-background.jpg')"
        />
        <meta property="og:image:width" content="1920" />
        <meta property="og:image:height" content="1080" />
        <meta
            property="og:image:alt"
            content="@yield('og_image_alt', 'Гірськолижні тури Prostar - сезон 2025-2026')"
        />
        <meta property="og:image:type" content="image/jpeg" />
        <meta property="og:site_name" content="PROSTAR" />
        <meta property="og:locale" content="uk_UA" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="@yield('twitter_url', config('app.url'))" />
        <meta
            name="twitter:title"
            content="@yield('twitter_title', 'Гірськолижні Тури Під Ключ | Prostar')"
        />
        <meta
            name="twitter:description"
            content="@yield('twitter_description', 'Prostartravel.com - організація гірськолижних турів під ключ для новачків, професіоналів, та сімейного відпочинку. Організація корпоративного відпочинку.')"
        />
        <meta
            name="twitter:image"
            content="@yield('twitter_image', config('app.url') . '/images/hero-background.jpg')"
        />
        <meta
            name="twitter:image:alt"
            content="@yield('twitter_image_alt', 'Гірськолижні тури Prostar - сезон 2025-2026')"
        />

        <!-- Favicon -->
        <link
            rel="icon"
            type="image/png"
            sizes="192x192"
            href="https://static.wixstatic.com/media/8b9266_6ed838e5a4f043d29a1c7a0cc48d3043~mv2.png/v1/fill/w_192,h_192,lg_1,usm_0.66_1.00_0.01/8b9266_6ed838e5a4f043d29a1c7a0cc48d3043~mv2.png"
        />
        <link
            rel="shortcut icon"
            href="https://static.wixstatic.com/media/8b9266_6ed838e5a4f043d29a1c7a0cc48d3043~mv2.png/v1/fill/w_32,h_32,lg_1,usm_0.66_1.00_0.01/8b9266_6ed838e5a4f043d29a1c7a0cc48d3043~mv2.png"
        />
        <link
            rel="apple-touch-icon"
            href="https://static.wixstatic.com/media/8b9266_6ed838e5a4f043d29a1c7a0cc48d3043~mv2.png/v1/fill/w_180,h_180,lg_1,usm_0.66_1.00_0.01/8b9266_6ed838e5a4f043d29a1c7a0cc48d3043~mv2.png"
        />

        <!-- RSS Feed -->
        <link
            rel="alternate"
            href="{{ config('app.url') }}/blog-feed.xml"
            type="application/rss+xml"
            title="PROSTAR - RSS"
        />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />

        <!-- Font Awesome -->
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
            integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        />

        <!-- Preload Critical Resources -->
        <link rel="preload" href="{{ asset('images/hero-background.jpg') }}" as="image" />
        <link rel="preload" href="{{ asset('css/style.css') }}" as="style" />
        <link
            rel="preload"
            href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap"
            as="style"
        />

        <!-- Styles -->
        <link rel="stylesheet" href="{{ asset('css/style.css') }}" />

        <!-- Additional SEO Meta Tags -->
        <meta name="theme-color" content="#0071e3" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta
            name="apple-mobile-web-app-status-bar-style"
            content="black-translucent"
        />
        <meta name="format-detection" content="telephone=yes" />
        <meta name="geo.region" content="UA" />
        <meta name="geo.placename" content="Україна" />
        <meta name="language" content="Ukrainian" />
        <meta name="revisit-after" content="7 days" />
        <meta name="distribution" content="global" />
        <meta name="rating" content="general" />

        <!-- Structured Data (JSON-LD) -->
        @stack('structured_data')
        
        @if(!isset($skip_default_structured_data) || !$skip_default_structured_data)
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "WebSite",
                "name": "PROSTAR",
                "alternateName": "ProStar Travel",
                "url": "{{ config('app.url') }}",
                "description": "Організація гірськолижних турів під ключ для новачків, професіоналів, та сімейного відпочинку",
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": "{{ config('app.url') }}/search?q={search_term_string}",
                    "query-input": "required name=search_term_string"
                }
            }
        </script>
        @endif

        <!-- Additional Head Content -->
        @stack('head')
        
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
        <!-- Header -->
        @include('partials.header')

        <!-- Main Content -->
        <main role="main">
            @yield('content')
        </main>

        <!-- Footer -->
        @include('partials.footer')

        <!-- Scripts -->
        <script src="{{ asset('js/main.js') }}"></script>
        @stack('scripts')
    </body>
</html>

