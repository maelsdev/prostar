@php
$settings = \App\Models\Setting::getSettings();
$homePage = \App\Models\Page::getBySlug('home');
@endphp

<!-- Header -->
<header class="header" role="banner">
    <!-- Top Bar -->
    <div class="header-top">
        <div class="container">
            <div class="header-top-content">
                <!-- Ліва частина: Контакти та соціальні мережі -->
                <div class="header-top-left">
                    <a
                        href="tel:{{ $settings->phone }}"
                        class="phone-link"
                        aria-label="Телефон">
                        <svg
                            class="icon"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>{{ $settings->phone }}</span>
                    </a>
                    <a
                        href="https://t.me/{{ $settings->telegram_username }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="social-icon telegram-icon"
                        aria-label="Telegram">
                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                        </svg>
                    </a>
                    <a
                        href="https://wa.me/{{ $settings->whatsapp_phone }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="social-icon whatsapp-icon"
                        aria-label="WhatsApp">
                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                        </svg>
                    </a>
                    <button
                        class="btn-callback"
                        aria-label="Передзвонити мені">
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>Передзвонити мені</span>
                    </button>
                </div>

                <!-- Права частина: Погода та мова -->
                <div class="header-top-right">
                    <div class="weather-widget">
                        <div class="weather-resort-name">Драгобрат</div>
                        <div class="weather-info">
                            <div class="weather-item">
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                </svg>
                                <span id="temperature">--°C</span>
                            </div>
                            <div class="weather-item">
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2M9.6 4.6A2 2 0 1 1 11 8H2M12.6 19.4A2 2 0 1 0 14 16H2"></path>
                                </svg>
                                <span id="wind-speed">-- м/с</span>
                            </div>
                        </div>
                    </div>
                    @if($settings->show_language_switcher)
                    <div class="language-switcher">
                        <button
                            class="lang-btn active"
                            data-lang="uk"
                            aria-label="Українська">
                            UA
                        </button>
                        <button
                            class="lang-btn"
                            data-lang="en"
                            aria-label="English">
                            EN
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Divider -->
    <div class="header-divider"></div>

    <!-- Main Navigation -->
    <div class="header-main">
        <div class="container">
            <div class="header-main-content">
                <!-- Логотип -->
                <div class="header-brand">
                    <a href="{{ route('home') }}" class="brand-link">
                        @if($settings->logo_image)
                        <img
                            src="{{ asset('storage/' . $settings->logo_image) }}"
                            alt="{{ $settings->logo_text ?? 'Logo' }}"
                            class="brand-logo-image" />
                        @else
                        <div class="brand-names">
                            {{ $settings->logo_text ?? 'PROSTAR | RADUGAUA | SNІGOWEEK' }}
                        </div>
                        @endif
                    </a>
                </div>

                <!-- Мобільна кнопка меню -->
                <button
                    class="mobile-menu-toggle"
                    aria-label="Відкрити меню"
                    aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <!-- Навігація -->
                <nav
                    class="main-nav"
                    role="navigation"
                    aria-label="Головне меню">
                    <ul class="nav-menu">
                        <li><a href="{{ route('home') }}#home">Головна</a></li>
                        <li><a href="{{ route('home') }}#tours">Тури</a></li>
                        @if(!$homePage || ($homePage && $homePage->show_hotels_section))
                        <li><a href="{{ route('home') }}#hotels">Готелі</a></li>
                        @endif
                        @if(!$homePage || ($homePage && $homePage->show_activities_section))
                        <li><a href="{{ route('home') }}#activities">Активності</a></li>
                        @endif
                        @if(!$homePage || ($homePage && $homePage->show_contact_section))
                        <li><a href="{{ route('home') }}#contact">Контакти</a></li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</header>