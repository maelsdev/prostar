@extends('layouts.app')

@section('title', ($tour->name ?? 'Тур') . ' | Prostar')
@section('meta_title', ($tour->name ?? 'Тур') . ' | Prostar')
@section('meta_description', $tour->short_description ?? 'Детальна інформація про гірськолижний тур')
@section('og_title', ($tour->name ?? 'Тур') . ' | Prostar')
@section('og_description', $tour->short_description ?? 'Детальна інформація про гірськолижний тур')
@section('og_image', $tour->mainImage && $tour->mainImage->path ? asset('storage/' . $tour->mainImage->path) : asset('images/hero-background.jpg'))
@section('og_url', url()->current())
@section('canonical', url()->current())

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "TouristTrip",
    "name": "{{ $tour->name }}",
    "description": "{{ $tour->short_description ?? $tour->full_description ?? '' }}",
    "tourBookingPage": "{{ url()->current() }}",
    "itinerary": {
        "@type": "ItemList",
        "itemListElement": [
            {
                "@type": "TouristDestination",
                "name": "{{ $tour->resort }}",
                "address": {
                    "@type": "PostalAddress",
                    "addressCountry": "{{ $tour->country }}"
                }
            }
        ]
    },
    "offers": {
        "@type": "Offer",
        "priceCurrency": "UAH",
        "availability": "https://schema.org/InStock"
    }
}
</script>
@endpush

@section('content')
    @php
        // Форматуємо дати
        $startDate = $tour->start_date;
        $endDate = $tour->end_date;
        $startDateFormatted = $startDate->format('d.m.Y');
        $endDateFormatted = $endDate->format('d.m.Y');
        
        // Форматуємо час
        $departureTime = $tour->departure_time ? \Carbon\Carbon::parse($tour->departure_time)->format('H:i') : null;
        $arrivalTime = $tour->arrival_time ? \Carbon\Carbon::parse($tour->arrival_time)->format('H:i') : null;

        // Використовуємо значення з адмінки або fallback на автоматичний розрахунок
        $nightsInRoad = $tour->nights_in_road ?? 0;
        $nightsInHotel = $tour->nights_in_hotel ?? max(0, $startDate->diffInDays($endDate) - 1);
        $daysOnResort = $tour->days_on_resort ?? max(0, $startDate->diffInDays($endDate) - 1);

        // Отримуємо URL зображення
        $imageUrl = $tour->mainImage && $tour->mainImage->path 
            ? asset('storage/' . $tour->mainImage->path)
            : 'https://images.unsplash.com/photo-1551524164-6cf77f5e1d65?w=1200&h=600&fit=crop';
        
        $imageAlt = $tour->mainImage && $tour->mainImage->alt 
            ? $tour->mainImage->alt 
            : $tour->name;

        // Отримуємо найменшу ціну
        $minPrice = null;
        if ($tour->price_options && is_array($tour->price_options) && count($tour->price_options) > 0) {
            $prices = array_filter(array_column($tour->price_options, 'price'), function($price) {
                return is_numeric($price) && $price > 0;
            });
            if (!empty($prices)) {
                $minPrice = min($prices);
            }
        }
    @endphp

    <!-- Основна інформація про тур -->
    <section class="tour-details">
        <div class="container">
            <!-- Breadcrumbs -->
            <div class="tour-breadcrumbs">
                <a href="{{ route('home') }}">Головна</a>
                <span>/</span>
                <a href="{{ route('home') }}#tours">Тури</a>
                <span>/</span>
                <span>{{ $tour->name }}</span>
            </div>

            <!-- Головна картка: зліва картинка, справа інформація (на всю ширину) -->
            <div class="tour-main-card">
                <div class="tour-main-layout">
                    <div class="tour-main-image">
                        <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" />
                    </div>
                    <div class="tour-main-info">
                        <h2 class="tour-main-title">{{ $tour->name }}</h2>
                        <p class="tour-main-location">
                            <i class="fas fa-map-marker-alt"></i>
                            {{ $tour->resort }}, {{ $tour->country }}
                        </p>

                        <div class="tour-dates-section">
                            <div class="tour-date-row">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="tour-date-label">Виїзд з Києва:</span>
                                <span class="tour-date-value">
                                    {{ $startDateFormatted }}
                                    @if($departureTime)
                                        <span class="tour-time">о {{ $departureTime }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="tour-date-row">
                                <i class="fas fa-calendar-check"></i>
                                <span class="tour-date-label">Прибуття в Київ:</span>
                                <span class="tour-date-value">
                                    {{ $endDateFormatted }}
                                    @if($arrivalTime)
                                        <span class="tour-time">о {{ $arrivalTime }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="tour-duration-section">
                            @if($nightsInRoad > 0)
                            <div class="tour-duration-item">
                                <span class="tour-duration-number">{{ $nightsInRoad }}</span>
                                <span class="tour-duration-label">ночі в дорозі</span>
                            </div>
                            @endif
                            @if($nightsInHotel > 0)
                            <div class="tour-duration-item">
                                <span class="tour-duration-number">{{ $nightsInHotel }}</span>
                                <span class="tour-duration-label">ночі в готелі</span>
                            </div>
                            @endif
                            @if($daysOnResort > 0)
                            <div class="tour-duration-item">
                                <span class="tour-duration-number">{{ $daysOnResort }}</span>
                                <span class="tour-duration-label">дні на курорті</span>
                            </div>
                            @endif
                        </div>

                        @if($tour->short_description)
                            <p class="tour-main-description">{{ $tour->short_description }}</p>
                        @endif

                        <div class="tour-transfer-section">
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

                        <!-- Готель та харчування в основній картці -->
                        @if($tour->hotel_name || $tour->meals_breakfast || $tour->meals_dinner)
                        <div class="tour-hotel-main-info">
                            @if($tour->hotel_name)
                                <div class="tour-hotel-main-item">
                                    <i class="fas fa-hotel"></i>
                                    <span class="tour-hotel-main-label">Готель:</span>
                                    <span class="tour-hotel-main-value">{{ $tour->hotel_name }}</span>
                                </div>
                            @endif
                            
                            <div class="tour-meals-main-item">
                                <i class="fas fa-utensils"></i>
                                <span class="tour-meals-main-label">Харчування:</span>
                                <span class="tour-meals-main-value">
                                    @if($tour->meals_breakfast && $tour->meals_dinner)
                                        Сніданки та вечері
                                    @elseif($tour->meals_breakfast)
                                        Сніданки
                                    @elseif($tour->meals_dinner)
                                        Вечері
                                    @else
                                        Без харчування
                                    @endif
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tour-details-grid">
                <!-- Ліва колонка - Основна інформація -->
                <div class="tour-details-main">
                    <!-- Готель та харчування -->
                    <div class="tour-detail-card">
                        <h2 class="tour-detail-title">
                            <i class="fas fa-hotel"></i>
                            Готель та харчування
                        </h2>
                        <div class="tour-hotel-info">
                            @if($tour->hotel_name)
                                <h3 class="tour-hotel-name">{{ $tour->hotel_name }}</h3>
                            @endif
                            
                            @if($tour->hotel_description)
                                <p class="tour-hotel-description">{{ $tour->hotel_description }}</p>
                            @endif
                            
                            <div class="tour-meals-info">
                                <h4 class="tour-meals-title">Харчування:</h4>
                                <div class="tour-meals-list">
                                    @if($tour->meals_breakfast && $tour->meals_dinner)
                                        <span class="tour-meal-item">
                                            <i class="fas fa-check-circle"></i>
                                            Сніданки та вечері
                                        </span>
                                    @elseif($tour->meals_breakfast)
                                        <span class="tour-meal-item">
                                            <i class="fas fa-check-circle"></i>
                                            Сніданки
                                        </span>
                                    @elseif($tour->meals_dinner)
                                        <span class="tour-meal-item">
                                            <i class="fas fa-check-circle"></i>
                                            Вечері
                                        </span>
                                    @else
                                        <span class="tour-meal-item tour-meal-none">
                                            <i class="fas fa-times-circle"></i>
                                            Без харчування
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Галерея зображень -->
                    @if($tour->images && $tour->images->count() > 0)
                    <div class="tour-detail-card">
                        <h2 class="tour-detail-title">
                            <i class="fas fa-images"></i>
                            Галерея зображень
                        </h2>
                        <div class="tour-gallery">
                            @foreach($tour->images as $tourImage)
                                @if($tourImage->mediaFile && $tourImage->mediaFile->path)
                                <div class="tour-gallery-item">
                                    <img 
                                        src="{{ asset('storage/' . $tourImage->mediaFile->path) }}" 
                                        alt="{{ $tourImage->mediaFile->alt ?? $tourImage->mediaFile->name }}"
                                        loading="lazy"
                                    />
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Повний опис -->
                    @if($tour->full_description)
                    <div class="tour-detail-card">
                        <h2 class="tour-detail-title">
                            <i class="fas fa-info-circle"></i>
                            Опис туру
                        </h2>
                        <div class="tour-full-description">
                            {!! $tour->full_description !!}
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Права колонка - Бокова панель -->
                <div class="tour-details-sidebar">
                    <!-- Варіанти ціни -->
                    @if($tour->price_options && count($tour->price_options) > 0)
                    <div class="tour-sidebar-card">
                        <h3 class="tour-sidebar-title">Варіанти ціни</h3>
                        <div class="tour-price-options">
                            @foreach($tour->price_options as $option)
                                @if(isset($option['price']) && isset($option['description']))
                                <div class="tour-price-option">
                                    <div class="tour-price-option-header">
                                        <span class="tour-price-option-price">
                                            {{ number_format($option['price'], 0, ',', ' ') }} грн
                                        </span>
                                    </div>
                                    <p class="tour-price-option-description">{{ $option['description'] }}</p>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @if($minPrice)
                        <div class="tour-min-price">
                            <span class="tour-min-price-label">Від</span>
                            <span class="tour-min-price-value">{{ number_format($minPrice, 0, ',', ' ') }}</span>
                            <span class="tour-min-price-currency">грн</span>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Кнопка бронювання -->
                    <div class="tour-sidebar-card">
                        @if($tour->is_booking_enabled ?? true)
                            <button type="button" class="btn btn-primary btn-block tour-book-btn" onclick="openBookingModal({{ $tour->id }})">
                                <i class="fas fa-calendar-check"></i>
                                Забронювати тур
                            </button>
                        @else
                            <div class="booking-disabled-message" style="padding: 1.5rem; text-align: center; background: #f3f4f6; border-radius: 8px; margin-bottom: 1rem;">
                                <i class="fas fa-calendar-times" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.5rem;"></i>
                                <p style="color: #6b7280; font-size: 1rem; margin: 0; line-height: 1.5;">
                                    На жаль, місць в турі більше не залишилось :(
                                </p>
                            </div>
                        @endif
                        <a href="tel:+380981212011" class="btn btn-outline btn-block tour-call-btn">
                            <i class="fas fa-phone-alt"></i>
                            +38(098) 12-12-011
                        </a>
                    </div>

                    <!-- Додаткова інформація -->
                    <div class="tour-sidebar-card">
                        <h3 class="tour-sidebar-title">Контакти</h3>
                        <div class="tour-contact-info">
                            <div class="tour-contact-item">
                                <i class="fab fa-telegram"></i>
                                <a href="https://t.me/pro_s_tar" target="_blank" rel="noopener noreferrer">@pro_s_tar</a>
                            </div>
                            <div class="tour-contact-item">
                                <i class="fab fa-whatsapp"></i>
                                <a href="https://wa.me/380981212011" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Модальне вікно бронювання -->
    <div id="bookingModal" class="booking-modal">
        <div class="booking-modal-overlay" onclick="closeBookingModal()"></div>
        <div class="booking-modal-content">
            <div class="booking-modal-header">
                <h2 class="booking-modal-title">Бронювання туру</h2>
                <button type="button" class="booking-modal-close" onclick="closeBookingModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="bookingForm" class="booking-form">
                <input type="hidden" name="tour_id" id="booking_tour_id" value="{{ $tour->id }}">
                
                <div class="booking-form-row">
                    <div class="booking-form-group">
                        <label for="booking_first_name">Ім'я <span class="required">*</span></label>
                        <input type="text" id="booking_first_name" name="first_name" required>
                        <span class="booking-error" id="error_first_name"></span>
                    </div>
                    <div class="booking-form-group">
                        <label for="booking_last_name">Прізвище <span class="required">*</span></label>
                        <input type="text" id="booking_last_name" name="last_name" required>
                        <span class="booking-error" id="error_last_name"></span>
                    </div>
                </div>
                
                <div class="booking-form-group">
                    <label for="booking_phone">Телефон <span class="required">*</span></label>
                    <input type="tel" id="booking_phone" name="phone" placeholder="+38(098) 12-12-011" required>
                    <span class="booking-error" id="error_phone"></span>
                </div>
                
                <div class="booking-form-group">
                    <label for="booking_telegram_username">Нік в Telegram (якщо є)</label>
                    <input type="text" id="booking_telegram_username" name="telegram_username" placeholder="@username">
                    <span class="booking-error" id="error_telegram_username"></span>
                </div>
                
                @if($tour->price_options && count($tour->price_options) > 0)
                <div class="booking-form-group">
                    <label for="booking_price_option">Варіант ціни <span class="required">*</span></label>
                    <select id="booking_price_option" name="price_option" required>
                        <option value="">Оберіть варіант ціни</option>
                        @foreach($tour->price_options as $index => $option)
                            @if(isset($option['price']) && isset($option['description']))
                            <option value="{{ number_format($option['price'], 0, ',', ' ') }} грн - {{ $option['description'] }}">
                                {{ number_format($option['price'], 0, ',', ' ') }} грн - {{ $option['description'] }}
                            </option>
                            @endif
                        @endforeach
                    </select>
                    <span class="booking-error" id="error_price_option"></span>
                </div>
                @endif
                
                <div class="booking-form-group">
                    <label for="booking_places">Кількість місць <span class="required">*</span></label>
                    <input type="number" id="booking_places" name="places" min="1" max="50" value="1" required>
                    <span class="booking-error" id="error_places"></span>
                </div>
                
                <div class="booking-form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeBookingModal()">Скасувати</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Відправити заявку
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openBookingModal(tourId) {
            document.getElementById('bookingModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            document.getElementById('booking_tour_id').value = tourId;
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('bookingForm').reset();
            // Очистити помилки
            document.querySelectorAll('.booking-error').forEach(el => el.textContent = '');
        }

        // Функції валідації
        function validateFirstName(firstName) {
            if (!firstName || firstName.trim().length === 0) {
                return 'Введіть ім\'я';
            }
            if (firstName.trim().length < 2) {
                return 'Ім\'я має містити мінімум 2 символи';
            }
            if (firstName.trim().length > 255) {
                return 'Ім\'я занадто довге (максимум 255 символів)';
            }
            if (!/^[а-яА-ЯёЁіІїЇєЄґҐa-zA-Z\s\-']+$/.test(firstName.trim())) {
                return 'Ім\'я може містити тільки літери, пробіли, дефіси та апострофи';
            }
            return '';
        }

        function validateLastName(lastName) {
            if (!lastName || lastName.trim().length === 0) {
                return 'Введіть прізвище';
            }
            if (lastName.trim().length < 2) {
                return 'Прізвище має містити мінімум 2 символи';
            }
            if (lastName.trim().length > 255) {
                return 'Прізвище занадто довге (максимум 255 символів)';
            }
            if (!/^[а-яА-ЯёЁіІїЇєЄґҐa-zA-Z\s\-']+$/.test(lastName.trim())) {
                return 'Прізвище може містити тільки літери, пробіли, дефіси та апострофи';
            }
            return '';
        }

        function validatePhone(phone) {
            if (!phone || phone.trim().length === 0) {
                return 'Введіть номер телефону';
            }
            // Прибираємо всі символи крім цифр, +, пробілів, дужок, дефісів
            const cleaned = phone.replace(/[^\d\+\s\(\)\-]/g, '');
            // Перевіряємо формат: +380XXXXXXXXX або 0XXXXXXXXX або інші варіанти
            if (!/^[\+]?[0-9\s\(\)\-]{10,20}$/.test(cleaned)) {
                return 'Номер телефону має бути у правильному форматі (наприклад: +380981234567 або 0981234567)';
            }
            // Перевіряємо, що є достатньо цифр (мінімум 10)
            const digitsOnly = cleaned.replace(/\D/g, '');
            if (digitsOnly.length < 10) {
                return 'Номер телефону має містити мінімум 10 цифр';
            }
            if (digitsOnly.length > 15) {
                return 'Номер телефону занадто довгий';
            }
            return '';
        }

        function validateTelegramUsername(username) {
            if (!username || username.trim().length === 0) {
                return ''; // Необов'язкове поле
            }
            const cleaned = username.trim().replace(/^@/, ''); // Прибираємо @ на початку
            if (cleaned.length < 5) {
                return 'Нікнейм Telegram має містити мінімум 5 символів';
            }
            if (cleaned.length > 32) {
                return 'Нікнейм Telegram занадто довгий (максимум 32 символи)';
            }
            if (!/^[a-zA-Z0-9_]+$/.test(cleaned)) {
                return 'Нікнейм Telegram може містити тільки латинські літери, цифри та підкреслення';
            }
            return '';
        }

        function validatePriceOption(priceOption) {
            if (!priceOption || priceOption.trim().length === 0) {
                return 'Оберіть варіант ціни';
            }
            return '';
        }

        function validatePlaces(places) {
            if (!places || places === '') {
                return 'Вкажіть кількість місць';
            }
            const num = parseInt(places);
            if (isNaN(num)) {
                return 'Кількість місць має бути числом';
            }
            if (num < 1) {
                return 'Мінімум 1 місце';
            }
            if (num > 50) {
                return 'Максимум 50 місць';
            }
            return '';
        }

        // Валідація в реальному часі
        const bookingForm = document.getElementById('bookingForm');
        const firstNameInput = document.getElementById('booking_first_name');
        const lastNameInput = document.getElementById('booking_last_name');
        const phoneInput = document.getElementById('booking_phone');
        const telegramInput = document.getElementById('booking_telegram_username');
        const priceOptionInput = document.getElementById('booking_price_option');
        const placesInput = document.getElementById('booking_places');

        // Валідація при втраті фокусу
        if (firstNameInput) {
            firstNameInput.addEventListener('blur', function() {
                const error = validateFirstName(this.value);
                document.getElementById('error_first_name').textContent = error;
                if (error) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }

        if (lastNameInput) {
            lastNameInput.addEventListener('blur', function() {
                const error = validateLastName(this.value);
                document.getElementById('error_last_name').textContent = error;
                if (error) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }

        if (phoneInput) {
            phoneInput.addEventListener('blur', function() {
                const error = validatePhone(this.value);
                document.getElementById('error_phone').textContent = error;
                if (error) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
            // Форматування телефону при введенні
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^\d\+\s\(\)\-]/g, '');
            });
        }

        if (telegramInput) {
            telegramInput.addEventListener('blur', function() {
                const error = validateTelegramUsername(this.value);
                document.getElementById('error_telegram_username').textContent = error;
                if (error) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
            // Автоматично додаємо @ якщо його немає
            telegramInput.addEventListener('blur', function() {
                if (this.value && !this.value.startsWith('@')) {
                    this.value = '@' + this.value.replace(/^@/, '');
                }
            });
        }

        if (priceOptionInput) {
            priceOptionInput.addEventListener('change', function() {
                const error = validatePriceOption(this.value);
                document.getElementById('error_price_option').textContent = error;
                if (error) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }

        if (placesInput) {
            placesInput.addEventListener('blur', function() {
                const error = validatePlaces(this.value);
                document.getElementById('error_places').textContent = error;
                if (error) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
            // Обмежуємо введення тільки цифрами
            placesInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^\d]/g, '');
            });
        }

        // Валідація при відправці форми
        bookingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Очистити попередні помилки
            document.querySelectorAll('.booking-error').forEach(el => el.textContent = '');
            document.querySelectorAll('.booking-form-group input, .booking-form-group select').forEach(el => el.classList.remove('error'));
            
            // Валідація всіх полів
            let isValid = true;
            const errors = {};
            
            const firstName = document.getElementById('booking_first_name').value;
            const firstNameError = validateFirstName(firstName);
            if (firstNameError) {
                document.getElementById('error_first_name').textContent = firstNameError;
                document.getElementById('booking_first_name').classList.add('error');
                errors.first_name = firstNameError;
                isValid = false;
            }
            
            const lastName = document.getElementById('booking_last_name').value;
            const lastNameError = validateLastName(lastName);
            if (lastNameError) {
                document.getElementById('error_last_name').textContent = lastNameError;
                document.getElementById('booking_last_name').classList.add('error');
                errors.last_name = lastNameError;
                isValid = false;
            }
            
            const phone = document.getElementById('booking_phone').value;
            const phoneError = validatePhone(phone);
            if (phoneError) {
                document.getElementById('error_phone').textContent = phoneError;
                document.getElementById('booking_phone').classList.add('error');
                errors.phone = phoneError;
                isValid = false;
            }
            
            const telegram = document.getElementById('booking_telegram_username').value;
            const telegramError = validateTelegramUsername(telegram);
            if (telegramError) {
                document.getElementById('error_telegram_username').textContent = telegramError;
                document.getElementById('booking_telegram_username').classList.add('error');
                errors.telegram_username = telegramError;
                isValid = false;
            }
            
            if (priceOptionInput) {
                const priceOption = priceOptionInput.value;
                const priceOptionError = validatePriceOption(priceOption);
                if (priceOptionError) {
                    document.getElementById('error_price_option').textContent = priceOptionError;
                    priceOptionInput.classList.add('error');
                    errors.price_option = priceOptionError;
                    isValid = false;
                }
            }
            
            const places = document.getElementById('booking_places').value;
            const placesError = validatePlaces(places);
            if (placesError) {
                document.getElementById('error_places').textContent = placesError;
                document.getElementById('booking_places').classList.add('error');
                errors.places = placesError;
                isValid = false;
            }
            
            // Якщо є помилки валідації, не відправляємо форму
            if (!isValid) {
                // Прокрутити до першої помилки
                const firstError = form.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                return;
            }
            
            // Відключити кнопку
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Відправка...';
            
            try {
                const formData = new FormData(form);
                const response = await fetch('{{ route("booking.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closeBookingModal();
                } else {
                    if (data.errors) {
                        // Показати помилки валідації з сервера
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.getElementById('error_' + field);
                            const inputElement = document.getElementById('booking_' + field);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field][0];
                            }
                            if (inputElement) {
                                inputElement.classList.add('error');
                            }
                        });
                        // Прокрутити до першої помилки
                        const firstError = form.querySelector('.error');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstError.focus();
                        }
                    } else {
                        alert(data.message || 'Помилка відправки заявки');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Помилка відправки заявки. Спробуйте пізніше.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });

        // Закрити модальне вікно при натисканні Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeBookingModal();
            }
        });
    </script>
    @endpush
@endsection
