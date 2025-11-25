// Мінімалістичний та швидкий JavaScript
(function() {
    'use strict';

    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    const navLinks = document.querySelectorAll('.nav-menu a');

    if (mobileMenuToggle && mainNav) {
        function openMenu() {
            mobileMenuToggle.setAttribute('aria-expanded', 'true');
            mainNav.classList.add('active');
            document.body.classList.add('menu-open');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            mainNav.classList.remove('active');
            document.body.classList.remove('menu-open');
            document.body.style.overflow = '';
        }

        mobileMenuToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Закрити меню при кліку на посилання
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMenu();
            });
        });

        // Закрити меню при кліку на overlay
        document.addEventListener('click', function(e) {
            if (mainNav.classList.contains('active') && 
                !mainNav.contains(e.target) && 
                !mobileMenuToggle.contains(e.target)) {
                closeMenu();
            }
        });

        // Закрити меню при ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mainNav.classList.contains('active')) {
                closeMenu();
            }
        });
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // Додатковий обробник для кнопок з класом scroll-to-section
    document.querySelectorAll('.scroll-to-section').forEach(button => {
        button.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    // Перемикач мови
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Функція "Передзвонити мені"
    const callbackBtn = document.querySelector('.btn-callback');
    if (callbackBtn) {
        callbackBtn.addEventListener('click', function() {
            const message = encodeURIComponent('Добрий день! Будь ласка, передзвоніть мені.');
            window.open(`https://wa.me/380981212011?text=${message}`, '_blank');
        });
    }

    // Погода для Драгобрату
    async function loadDragobratWeather() {
        const temperatureEl = document.getElementById('temperature');
        const windSpeedEl = document.getElementById('wind-speed');
        
        if (!temperatureEl || !windSpeedEl) return;
        
        // Показати завантаження
        temperatureEl.textContent = '...';
        windSpeedEl.textContent = '...';
        
        try {
            const response = await fetch('/api/weather');
            const data = await response.json();
            
            if (data.error) {
                temperatureEl.textContent = '--°C';
                windSpeedEl.textContent = '-- м/с';
                return;
            }
            
            temperatureEl.textContent = data.temp || '--°C';
            windSpeedEl.textContent = data.wind || '-- м/с';
        } catch (error) {
            console.error('Помилка отримання погоди:', error);
            temperatureEl.textContent = '--°C';
            windSpeedEl.textContent = '-- м/с';
        }
    }

    // Завантажити погоду при завантаженні сторінки
    if (document.getElementById('temperature') && document.getElementById('wind-speed')) {
        loadDragobratWeather();
        
        // Оновлювати погоду кожні 10 хвилин
        setInterval(loadDragobratWeather, 600000);
    }

    // Scroll animations - Легкий та швидкий
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Спостерігаємо за секціями
    document.querySelectorAll('section').forEach(section => {
        observer.observe(section);
    });

    // Activities - Розгортання/згортання карток активностей
    const activityCards = document.querySelectorAll('.activity-card');
    
    activityCards.forEach(card => {
        const toggle = card.querySelector('.activity-toggle');
        const header = card.querySelector('.activity-header');
        const activityBtn = card.querySelector('.activity-btn');
        
        function toggleCard() {
            const isActive = card.classList.contains('active');
            
            // Закриваємо всі інші картки (опціонально - можна залишити відкритими кілька)
            // activityCards.forEach(c => {
            //     if (c !== card) {
            //         c.classList.remove('active');
            //     }
            // });
            
            // Перемикаємо поточну картку
            if (isActive) {
                card.classList.remove('active');
                toggle.setAttribute('aria-label', 'Розгорнути');
            } else {
                card.classList.add('active');
                toggle.setAttribute('aria-label', 'Згорнути');
            }
        }
        
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleCard();
            });
        }
        
        if (header) {
            header.addEventListener('click', function(e) {
                // Не перемикаємо якщо клікнули на кнопку toggle
                if (!e.target.closest('.activity-toggle')) {
                    toggleCard();
                }
            });
        }
        
        // Запобігаємо закриттю картки при кліку на кнопку "Розважатись"
        if (activityBtn) {
            activityBtn.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });

    // Google Maps - Перемикання локацій
    const locationButtons = document.querySelectorAll('.location-btn');
    const mapIframe = document.getElementById('map-iframe');
    
    // Координати та назви локацій
    const locations = {
        dragobrat: {
            coords: '48.2636,24.2394',
            name: 'Драгобрат',
            zoom: 13
        },
        bukovel: {
            coords: '48.3656,24.4000',
            name: 'Буковель',
            zoom: 13
        },
        slavske: {
            coords: '48.8333,23.4500',
            name: 'Славське',
            zoom: 13
        },
        kyiv: {
            coords: '50.4501,30.5234',
            name: 'Київ',
            zoom: 12
        }
    };
    
    function updateMap(locationKey) {
        const location = locations[locationKey];
        if (!location || !mapIframe) return;
        
        const [lat, lng] = location.coords.split(',');
        // Створюємо правильний URL для Google Maps embed з координатами
        // Використовуємо простий формат з координатами та zoom
        const zoom = location.zoom || 13;
        const newSrc = `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5080!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2z${zoom}!5e0!3m2!1suk!2sua!4v${Date.now()}!5m2!1suk!2sua&q=${lat},${lng}+(${encodeURIComponent(location.name)})`;
        
        mapIframe.src = newSrc;
        
        // Оновлюємо активну кнопку
        locationButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.location === locationKey) {
                btn.classList.add('active');
            }
        });
    }
    
    // Обробники подій для кнопок локацій
    if (locationButtons.length > 0 && mapIframe) {
        locationButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const locationKey = this.dataset.location;
                updateMap(locationKey);
            });
        });
        
        // Ініціалізація з початковою локацією (Драгобрат)
        const initialLocation = locationButtons[0].dataset.location;
        updateMap(initialLocation);
    }
})();
