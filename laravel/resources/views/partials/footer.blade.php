@php
$homePage = \App\Models\Page::getBySlug('home');
@endphp

<!-- Footer -->
<footer class="footer" role="contentinfo">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>ProStar Travel</h3>
                <p>
                    Організація гірськолижних турів та бронювання
                    готелів
                </p>
            </div>
            <div class="footer-col">
                <h4>Навігація</h4>
                <ul>
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
                    @if(!$homePage || ($homePage && $homePage->show_about_section_after_tours))
                    <li><a href="{{ route('home') }}#about-after-tours">Про нас</a></li>
                    @endif
                </ul>
            </div>
            <div class="footer-col">
                <h4>Контакти</h4>
                <p>
                    Телефон:
                    <a href="tel:+380981212011">+38(098) 12-12-011</a>
                </p>
                <p>
                    Telegram:
                    <a
                        href="https://t.me/pro_s_tar"
                        target="_blank"
                        rel="noopener noreferrer"
                        >@pro_s_tar</a
                    >
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} ProStar Travel. Всі права захищені.</p>
        </div>
    </div>
</footer>

