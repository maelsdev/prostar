@extends('layouts.app')

@section('title', 'Гірськолижні Тури Під Ключ | Prostar')

@push('structured_data')
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "TravelAgency",
        "name": "ProStar Travel",
        "alternateName": "PROSTAR | RADUGAUA | SNІGOWEEK",
        "url": "{{ config('app.url') }}",
        "logo": "{{ config('app.url') }}/images/hero-background.jpg",
        "image": "{{ config('app.url') }}/images/hero-background.jpg",
        "description": "Організація гірськолижних турів під ключ для новачків, професіоналів, та сімейного відпочинку. Сезон 2025-2026. Альпійські курорти, Карпатські траси, Скандинавські курорти.",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "UA",
            "addressRegion": "Україна"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+380981212011",
            "contactType": "customer service",
            "availableLanguage": ["uk", "ru"],
            "areaServed": "UA",
            "hoursAvailable": {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": [
                    "Monday",
                    "Tuesday",
                    "Wednesday",
                    "Thursday",
                    "Friday",
                    "Saturday",
                    "Sunday"
                ],
                "opens": "09:00",
                "closes": "21:00"
            }
        },
        "priceRange": "$$",
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "reviewCount": "127"
        },
        "sameAs": [
            "https://www.facebook.com/prostaradventure",
            "https://www.instagram.com/pro_s_tar/",
            "https://t.me/pro_s_tar"
        ],
        "offers": {
            "@type": "Offer",
            "category": "Гірськолижні тури",
            "availability": "https://schema.org/InStock",
            "priceCurrency": "UAH"
        }
    }
</script>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Головна",
                "item": "{{ config('app.url') }}"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Тури",
                "item": "{{ config('app.url') }}#tours"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "Про нас",
                "item": "{{ config('app.url') }}#about"
            }
        ]
    }
</script>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "ProStar Travel",
        "url": "{{ config('app.url') }}",
        "logo": "{{ config('app.url') }}/images/hero-background.jpg",
        "foundingDate": "2014",
        "founders": {
            "@type": "Person",
            "name": "ProStar Travel Team"
        },
        "numberOfEmployees": {
            "@type": "QuantitativeValue",
            "value": "10-50"
        },
        "areaServed": {
            "@type": "Country",
            "name": "Україна"
        }
    }
</script>
@endpush

@section('content')
            <!-- Hero Section -->
            <section id="home" class="hero">
                <div class="hero-overlay"></div>
                <div class="hero-split">
                    <!-- Ліва частина -->
                    <div class="hero-left">
                        <div class="hero-content">
                            @if($page && $page->season)
                                <h1 class="hero-season">{{ $page->season }}</h1>
                            @else
                                <h1 class="hero-season">SEASON 2025-2026</h1>
                            @endif
                            
                            @if($page && $page->h1)
                                <h2 class="hero-title">{{ $page->h1 }}</h2>
                            @else
                                <h2 class="hero-title">ГІРСЬКОЛИЖНІ ТУРИ</h2>
                            @endif
                            
                            @if($page && $page->description)
                                <div class="hero-description">
                                    {!! $page->description !!}
                                </div>
                            @else
                                <p class="hero-description">
                                    Організація гірськолижних турів під ключ для
                                    новачків, професіоналів, та сімейного відпочинку
                                </p>
                            @endif
                            
                            @if($page && $page->button_text)
                                <a href="{{ $page->button_action ?? '#tours' }}" class="btn btn-primary scroll-to-section">
                                    {{ $page->button_text }}
                                </a>
                            @else
                                <a href="#tours" class="btn btn-primary scroll-to-section">
                                    ОБРАТИ ТУР
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Права частина -->
                    <div class="hero-right">
                        <div class="hero-schedule">
                            <h3 class="schedule-title">Розклад турів</h3>
                            <div class="schedule-list">
                                @if($tours && $tours->count() > 0)
                                    @foreach($tours as $tour)
                                        @php
                                            // start_date та end_date вже є Carbon instances через casts
                                            $startDate = $tour->start_date;
                                            $endDate = $tour->end_date;
                                            
                                            // Масив місяців українською
                                            $months = [
                                                1 => 'СІЧЕНЬ', 2 => 'ЛЮТИЙ', 3 => 'БЕРЕЗЕНЬ',
                                                4 => 'КВІТЕНЬ', 5 => 'ТРАВЕНЬ', 6 => 'ЧЕРВЕНЬ',
                                                7 => 'ЛИПЕНЬ', 8 => 'СЕРПЕНЬ', 9 => 'ВЕРЕСЕНЬ',
                                                10 => 'ЖОВТЕНЬ', 11 => 'ЛИСТОПАД', 12 => 'ГРУДЕНЬ'
                                            ];
                                            
                                            $month = $months[$startDate->month] ?? 'ГРУДЕНЬ';
                                            $year = $startDate->format('Y');
                                            $days = $startDate->format('d') . '-' . $endDate->format('d');
                                        @endphp
                                        @if($tour->slug)
                                            <a href="{{ route('tour', $tour->slug) }}" class="schedule-item">
                                        @else
                                            <div class="schedule-item">
                                        @endif
                                            <div class="schedule-date">
                                                <span class="date-month">{{ $month }} {{ $year }}</span>
                                                <span class="date-day">{{ $days }}</span>
                                            </div>
                                            <div class="schedule-info">
                                                <h4>{{ $tour->name }}</h4>
                                                <p>{{ $tour->resort }}, {{ $tour->country }}</p>
                                            </div>
                                        @if($tour->slug)
                                            </a>
                                        @else
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="schedule-item">
                                        <div class="schedule-info">
                                            <p>Тури не знайдено</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- About Section -->
            <section id="about" class="about">
                <div class="container">
                    <div class="about-grid">
                        <div class="about-content">
                            <h2 class="section-title">Про нас</h2>
                            <p>
                                ProStar Travel - це команда професіоналів, яка
                                допоможе вам організувати незабутній
                                гірськолижний відпочинок.
                            </p>
                            <p>
                                Ми пропонуємо найкращі тури та готелі в
                                гірськолижних курортах по всьому світу.
                            </p>
                            <div class="stats">
                                <div class="stat-item">
                                    <div class="stat-number">10+</div>
                                    <div class="stat-label">років досвіду</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">500+</div>
                                    <div class="stat-label">клієнтів</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">50+</div>
                                    <div class="stat-label">напрямків</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Next Tours Section -->
            <section id="tours" class="next-tours">
                <div class="container">
                    <h2 class="section-title">Наступні тури</h2>
                    <p class="section-subtitle">
                        Заплануйте свій ідеальний гірськолижний відпочинок
                    </p>
                    @php
                        $toursCount = $tours ? $tours->count() : 0;
                        $gridClass = '';
                        if ($toursCount > 0) {
                            if ($toursCount % 3 == 0) {
                                $gridClass = 'tours-grid-3';
                            } elseif ($toursCount % 2 == 0) {
                                $gridClass = 'tours-grid-2';
                            }
                        }
                    @endphp
                    <div class="next-tours-list {{ $gridClass }}">
                        @if($tours && $tours->count() > 0)
                            @foreach($tours as $tour)
                                @php
                                    // Отримуємо найменшу ціну з варіантів
                                    $minPrice = null;
                                    if ($tour->price_options && is_array($tour->price_options) && count($tour->price_options) > 0) {
                                        $prices = array_filter(array_column($tour->price_options, 'price'), function($price) {
                                            return is_numeric($price) && $price > 0;
                                        });
                                        if (!empty($prices)) {
                                            $minPrice = min($prices);
                                        }
                                    }

                                    // Форматуємо дати
                                    $months = [
                                        1 => 'СІЧЕНЬ', 2 => 'ЛЮТИЙ', 3 => 'БЕРЕЗЕНЬ',
                                        4 => 'КВІТЕНЬ', 5 => 'ТРАВЕНЬ', 6 => 'ЧЕРВЕНЬ',
                                        7 => 'ЛИПЕНЬ', 8 => 'СЕРПЕНЬ', 9 => 'ВЕРЕСЕНЬ',
                                        10 => 'ЖОВТЕНЬ', 11 => 'ЛИСТОПАД', 12 => 'ГРУДЕНЬ'
                                    ];
                                    
                                    $startDate = $tour->start_date;
                                    $endDate = $tour->end_date;
                                    $month = $months[$startDate->month] ?? 'ГРУДЕНЬ';
                                    $days = $startDate->format('d') . '-' . $endDate->format('d');

                                    // Отримуємо URL зображення
                                    $imageUrl = $tour->mainImage && $tour->mainImage->path 
                                        ? asset('storage/' . $tour->mainImage->path)
                                        : 'https://images.unsplash.com/photo-1551524164-6cf77f5e1d65?w=400&h=300&fit=crop';
                                    
                                    $imageAlt = $tour->mainImage && $tour->mainImage->alt 
                                        ? $tour->mainImage->alt 
                                        : $tour->name;
                                @endphp
                                <a href="{{ route('tour', $tour->slug) }}" class="next-tour-card" data-category="standard">
                                    <div class="tour-image">
                                        <img
                                            src="{{ $imageUrl }}"
                                            alt="{{ $imageAlt }}"
                                            loading="lazy"
                                            width="400"
                                            height="300"
                                        />
                                        <div class="tour-date-overlay">
                                            <span class="tour-month">{{ $month }}</span>
                                            <span class="tour-days">{{ $days }}</span>
                                        </div>
                                    </div>
                                    <div class="tour-content-wrapper">
                                        <div class="tour-info">
                                            <h3 class="tour-name">{{ $tour->name }}</h3>
                                            <p class="tour-location">
                                                {{ $tour->resort }}, {{ $tour->country }}
                                            </p>
                                            <div class="tour-transfer">
                                                @if($tour->transfer_train)
                                                    <span class="transfer-icon" title="Потяг">
                                                        <i class="fas fa-train"></i>
                                                    </span>
                                                @endif
                                                @if($tour->transfer_bus)
                                                    <span class="transfer-icon" title="Автобус">
                                                        <i class="fas fa-bus"></i>
                                                    </span>
                                                @endif
                                                @if($tour->transfer_plane)
                                                    <span class="transfer-icon" title="Літак">
                                                        <i class="fas fa-plane"></i>
                                                    </span>
                                                @endif
                                                @if($tour->transfer_taxi)
                                                    <span class="transfer-icon" title="Маршрутне таксі">
                                                        <i class="fas fa-taxi"></i>
                                                    </span>
                                                @endif
                                                @if($tour->transfer_gaz66)
                                                    <span class="transfer-icon" title="ГАЗ 66">
                                                        <i class="fas fa-truck"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            @if($tour->short_description)
                                                <p class="tour-description">
                                                    {{ $tour->short_description }}
                                                </p>
                                            @endif
                                        </div>
                                        @if($minPrice)
                                            <div class="tour-price-badge">
                                                <span class="price-label">від</span>
                                                <span class="price-value">{{ number_format($minPrice, 0, ',', ' ') }}</span>
                                                <span class="price-currency">грн</span>
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        @else
                            <div class="next-tours-empty">
                                <p>Наразі немає доступних турів. Перевірте пізніше.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <!-- Recommended Hotels Section -->
            @if(!$page || ($page && $page->show_hotels_section))
            <section id="hotels" class="hotels">
                <div class="container">
                    <h2 class="section-title">
                        Рекомендовані готелі Драгобрату
                    </h2>
                    <p class="section-subtitle">
                        Найкращі готелі для вашого комфортного відпочинку в
                        гірськолижному курорті
                    </p>
                    <div class="hotels-grid">
                        <article class="hotel-card">
                            <div class="hotel-image">
                                <img
                                    src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop"
                                    alt="Готель у Драгобраті"
                                    loading="lazy"
                                    width="600"
                                    height="400"
                                />
                                <div class="hotel-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.8</span>
                                </div>
                            </div>
                            <div class="hotel-content">
                                <h3 class="hotel-name">Готель "Карпатський"</h3>
                                <p class="hotel-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Драгобрат
                                </p>
                                <div class="hotel-features">
                                    <span class="hotel-feature">
                                        <i class="fas fa-wifi"></i> Wi-Fi
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-utensils"></i> Ресторан
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-snowflake"></i> Біля
                                        трас
                                    </span>
                                </div>
                                <p class="hotel-description">
                                    Комфортний готель з видом на траси та
                                    сучасними зручностями
                                </p>
                                <div class="hotel-price">
                                    <span class="price-from">від</span>
                                    <span class="price-value">1 200</span>
                                    <span class="price-currency">грн/ніч</span>
                                </div>
                                <a
                                    href="#contact"
                                    class="btn btn-outline hotel-btn"
                                    >Забронювати</a
                                >
                            </div>
                        </article>
                        <article class="hotel-card">
                            <div class="hotel-image">
                                <img
                                    src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop"
                                    alt="Готель у Драгобраті"
                                    loading="lazy"
                                    width="600"
                                    height="400"
                                />
                                <div class="hotel-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.9</span>
                                </div>
                            </div>
                            <div class="hotel-content">
                                <h3 class="hotel-name">Готель "Сніговий"</h3>
                                <p class="hotel-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Драгобрат
                                </p>
                                <div class="hotel-features">
                                    <span class="hotel-feature">
                                        <i class="fas fa-wifi"></i> Wi-Fi
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-swimming-pool"></i>
                                        Басейн
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-spa"></i> SPA
                                    </span>
                                </div>
                                <p class="hotel-description">
                                    Преміум готель з басейном, SPA та рестораном
                                    високої кухні
                                </p>
                                <div class="hotel-price">
                                    <span class="price-from">від</span>
                                    <span class="price-value">2 500</span>
                                    <span class="price-currency">грн/ніч</span>
                                </div>
                                <a
                                    href="#contact"
                                    class="btn btn-outline hotel-btn"
                                    >Забронювати</a
                                >
                            </div>
                        </article>
                        <article class="hotel-card">
                            <div class="hotel-image">
                                <img
                                    src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=600&h=400&fit=crop"
                                    alt="Готель у Драгобраті"
                                    loading="lazy"
                                    width="600"
                                    height="400"
                                />
                                <div class="hotel-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.7</span>
                                </div>
                            </div>
                            <div class="hotel-content">
                                <h3 class="hotel-name">Готель "Альпійський"</h3>
                                <p class="hotel-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Драгобрат
                                </p>
                                <div class="hotel-features">
                                    <span class="hotel-feature">
                                        <i class="fas fa-wifi"></i> Wi-Fi
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-parking"></i> Парковка
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-snowflake"></i> Біля
                                        трас
                                    </span>
                                </div>
                                <p class="hotel-description">
                                    Затишний сімейний готель з домашньою
                                    атмосферою та смачною кухнею
                                </p>
                                <div class="hotel-price">
                                    <span class="price-from">від</span>
                                    <span class="price-value">900</span>
                                    <span class="price-currency">грн/ніч</span>
                                </div>
                                <a
                                    href="#contact"
                                    class="btn btn-outline hotel-btn"
                                    >Забронювати</a
                                >
                            </div>
                        </article>
                        <article class="hotel-card">
                            <div class="hotel-image">
                                <img
                                    src="https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=600&h=400&fit=crop"
                                    alt="Готель у Драгобраті"
                                    loading="lazy"
                                    width="600"
                                    height="400"
                                />
                                <div class="hotel-rating">
                                    <i class="fas fa-star"></i>
                                    <span>4.6</span>
                                </div>
                            </div>
                            <div class="hotel-content">
                                <h3 class="hotel-name">Готель "Гірський"</h3>
                                <p class="hotel-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Драгобрат
                                </p>
                                <div class="hotel-features">
                                    <span class="hotel-feature">
                                        <i class="fas fa-wifi"></i> Wi-Fi
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-fire"></i> Камін
                                    </span>
                                    <span class="hotel-feature">
                                        <i class="fas fa-snowflake"></i> Біля
                                        трас
                                    </span>
                                </div>
                                <p class="hotel-description">
                                    Готель з каміном та видом на гори, ідеальний
                                    для романтичного відпочинку
                                </p>
                                <div class="hotel-price">
                                    <span class="price-from">від</span>
                                    <span class="price-value">1 500</span>
                                    <span class="price-currency">грн/ніч</span>
                                </div>
                                <a
                                    href="#contact"
                                    class="btn btn-outline hotel-btn"
                                    >Забронювати</a
                                >
                            </div>
                        </article>
                    </div>
                </div>
            </section>
            @endif

            <!-- Activities Section -->
            @if(!$page || ($page && $page->show_activities_section))
            <section id="activities" class="activities">
                <div class="container">
                    <h2 class="section-title">Чим зайнятися на курорті</h2>
                    <p class="section-subtitle">
                        Відкрийте для себе різноманітні активності та розваги
                        гірськолижного курорту
                    </p>
                    <div class="activities-grid">
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-skiing"></i>
                                </div>
                                <h3 class="activity-title">
                                    Гірськолижне катання
                                </h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Професійні траси різної складності для
                                    новачків та досвідчених лижників. Прокат
                                    обладнання та інструктори для навчання.
                                    Чудові умови для сноубордингу та фрірайду.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-snowmobile"></i>
                                </div>
                                <h3 class="activity-title">Снігоходи</h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Захоплюючі поїздки на снігоходах по
                                    засніжених трасах та гірських маршрутах.
                                    Екскурсії до мальовничих місць з
                                    професійними гідами. Безпека та комфорт
                                    гарантовані.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-horse"></i>
                                </div>
                                <h3 class="activity-title">Катання на конях</h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Романтичні прогулянки на конях по засніжених
                                    стежках. Підходить для всієї родини.
                                    Досвідчені інструктори допоможуть новачкам.
                                    Незабутні враження та красиві фотографії.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-hiking"></i>
                                </div>
                                <h3 class="activity-title">Піші прогулянки</h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Маршрути різної складності для любителів
                                    піших прогулянок. Мальовничі краєвиди та
                                    чисте гірське повітря. Організовані
                                    екскурсії з гідами та можливість самостійних
                                    походів.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-spa"></i>
                                </div>
                                <h3 class="activity-title">
                                    SPA та відпочинок
                                </h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Розслабляючі SPA-процедури після активного
                                    дня. Масаж, сауна, джакузі з видом на гори.
                                    Відновлення сил та енергії для нових пригод.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h3 class="activity-title">
                                    Ресторани та кафе
                                </h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Смачна гірська кухня та міжнародні страви в
                                    ресторанах курорту. Затишні кафе з гарячими
                                    напоями та випічкою. Вечірні розваги та жива
                                    музика.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <h3 class="activity-title">Фотосесії</h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Професійні фотосесії на тлі гірських
                                    краєвидів. Романтичні та сімейні фотосесії.
                                    Створення незабутніх спогадів про ваш
                                    відпочинок.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-icon">
                                    <i class="fas fa-music"></i>
                                </div>
                                <h3 class="activity-title">Вечірні розваги</h3>
                                <button
                                    class="activity-toggle"
                                    aria-label="Розгорнути"
                                >
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="activity-content">
                                <p class="activity-description">
                                    Жива музика, дискотеки та розважальні
                                    програми ввечері. Барі з гарячими напоями та
                                    коктейлями. Веселий відпочинок після
                                    активного дня на трасах.
                                </p>
                                <a
                                    href="/articles"
                                    class="btn btn-primary activity-btn"
                                >
                                    <i class="fas fa-book-reader"></i>
                                    Розважатись
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            <!-- About Section (after tours) -->
            @if(!$page || ($page && $page->show_about_section_after_tours))
            <section id="about-after-tours" class="about">
                <div class="container">
                    <div class="about-grid">
                        <div class="about-content">
                            <h2 class="section-title">Про нас</h2>
                            <p>
                                ProStar Travel - це команда професіоналів, яка
                                допоможе вам організувати незабутній
                                гірськолижний відпочинок.
                            </p>
                            <p>
                                Ми пропонуємо найкращі тури та готелі в
                                гірськолижних курортах по всьому світу.
                            </p>
                            <div class="stats">
                                <div class="stat-item">
                                    <div class="stat-number">10+</div>
                                    <div class="stat-label">років досвіду</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">500+</div>
                                    <div class="stat-label">клієнтів</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">50+</div>
                                    <div class="stat-label">напрямків</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            <!-- Contact & Map Section -->
            @if(!$page || ($page && $page->show_contact_section))
            <section id="contact" class="contact-map">
                <div class="container">
                    <h2 class="section-title">Контакти та локації</h2>
                    <p class="section-subtitle">
                        Зв'яжіться з нами або знайдіть нас на карті
                    </p>
                    <div class="contact-map-grid">
                        <!-- Контактна інформація -->
                        <div class="contact-info">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Телефон</h4>
                                    <a href="tel:+380981212011"
                                        >+38(098) 12-12-011</a
                                    >
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fab fa-telegram"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Telegram</h4>
                                    <a
                                        href="https://t.me/pro_s_tar"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        >@pro_s_tar</a
                                    >
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>WhatsApp</h4>
                                    <a
                                        href="https://wa.me/380981212011"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        >+38(098) 12-12-011</a
                                    >
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-details">
                                    <h4>Офіс</h4>
                                    <p>Україна, м. Київ</p>
                                </div>
                            </div>
                        </div>

                        <!-- Google Maps -->
                        <div class="map-container">
                            <div class="map-wrapper">
                                <iframe
                                    id="map-iframe"
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5080!2d24.2394!3d48.2636!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTM!5e0!3m2!1suk!2sua!4v1234567890123!5m2!1suk!2sua&q=48.2636,24.2394+(Драгобрат)"
                                    width="100%"
                                    height="100%"
                                    style="border: 0"
                                    allowfullscreen=""
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    title="Локація ProStar Travel"
                                ></iframe>
                            </div>
                            <div class="map-locations">
                                <h4>Наші локації:</h4>
                                <div class="location-list">
                                    <button
                                        class="location-btn active"
                                        data-location="dragobrat"
                                        data-coords="48.2636,24.2394"
                                    >
                                        <i class="fas fa-map-marker-alt"></i>
                                        Драгобрат
                                    </button>
                                    <button
                                        class="location-btn"
                                        data-location="bukovel"
                                        data-coords="48.3656,24.4000"
                                    >
                                        <i class="fas fa-map-marker-alt"></i>
                                        Буковель
                                    </button>
                                    <button
                                        class="location-btn"
                                        data-location="slavske"
                                        data-coords="48.8333,23.4500"
                                    >
                                        <i class="fas fa-map-marker-alt"></i>
                                        Славське
                                    </button>
                                    <button
                                        class="location-btn"
                                        data-location="kyiv"
                                        data-coords="50.4501,30.5234"
                                    >
                                        <i class="fas fa-map-marker-alt"></i>
                                        Київ (Офіс)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            @endif

@endsection
