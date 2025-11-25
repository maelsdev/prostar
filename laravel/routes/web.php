<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\BookingController;

/*
|--------------------------------------------------------------------------
| Основні маршрути
|--------------------------------------------------------------------------
*/

// Головна сторінка
Route::get('/', [HomeController::class, 'index'])->name('home');

// Сторінка туру
Route::get('/tour/{slug}', [TourController::class, 'show'])->name('tour');

/*
|--------------------------------------------------------------------------
| API маршрути
|--------------------------------------------------------------------------
*/

// Погода
Route::get('/api/weather', [WeatherController::class, 'getWeather'])->name('weather');
Route::post('/api/weather/force-update', [WeatherController::class, 'forceUpdate'])->name('weather.force-update');

// Бронювання
Route::post('/api/booking', [BookingController::class, 'store'])
    ->middleware('throttle:5,15')
    ->name('booking.store');

/*
|--------------------------------------------------------------------------
| Додаткові маршрути (опціонально)
|--------------------------------------------------------------------------
*/

// Редирект з www на без www (або навпаки)
Route::get('/robots.txt', function () {
    return response()->file(public_path('robots.txt'));
})->name('robots');

// Sitemap (якщо потрібно в майбутньому)
// Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
