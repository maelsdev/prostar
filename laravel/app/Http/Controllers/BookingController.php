<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Tour;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // Перевірка, що запит надходить з того ж домену (захист від прямого виклику API)
        // Дозволяємо відсутній referer (може бути при fetch запитах з певних браузерів)
        // CSRF токен вже захищає від міжсайтових запитів
        $referer = $request->headers->get('referer');
        $appUrl = config('app.url');
        
        // Якщо referer присутній, перевіряємо домен
        if ($referer && $appUrl) {
            // Нормалізуємо URL для порівняння (прибираємо протокол та www)
            $normalizeDomain = function($url) {
                if (!$url) return null;
                $url = strtolower(trim($url));
                $url = preg_replace('#^https?://#', '', $url);
                $url = preg_replace('#^www\.#', '', $url);
                $parsed = parse_url('http://' . $url);
                return $parsed['host'] ?? null;
            };
            
            $appDomain = $normalizeDomain($appUrl);
            $refererDomain = $normalizeDomain($referer);
            
            // Блокуємо тільки якщо referer присутній і домени не співпадають
            if ($refererDomain && $appDomain && $refererDomain !== $appDomain) {
                \Log::warning('Booking request from unauthorized referer', [
                    'referer' => $referer,
                    'referer_domain' => $refererDomain,
                    'app_domain' => $appDomain,
                    'app_url' => $appUrl,
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Недозволений запит'
                ], 403);
            }
        }
        
        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|regex:/^[\+]?[0-9\s\(\)\-]{10,20}$/',
            'telegram_username' => 'nullable|string|max:100',
            'price_option' => 'required|string',
            'places' => 'required|integer|min:1|max:50',
        ], [
            'tour_id.required' => 'Помилка: не вказано тур',
            'tour_id.exists' => 'Помилка: тур не знайдено',
            'first_name.required' => 'Введіть ім\'я',
            'first_name.max' => 'Ім\'я занадто довге',
            'last_name.required' => 'Введіть прізвище',
            'last_name.max' => 'Прізвище занадто довге',
            'phone.required' => 'Введіть номер телефону',
            'phone.regex' => 'Номер телефону має бути у правильному форматі',
            'telegram_username.max' => 'Нікнейм занадто довгий',
            'price_option.required' => 'Оберіть варіант ціни',
            'places.required' => 'Вкажіть кількість місць',
            'places.integer' => 'Кількість місць має бути числом',
            'places.min' => 'Мінімум 1 місце',
            'places.max' => 'Максимум 50 місць',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $tour = Tour::findOrFail($request->tour_id);
        $settings = Setting::getSettings();

        // Формуємо повідомлення для Telegram
        $message = $this->formatTelegramMessage($tour, $request->all());

        // Відправляємо в Telegram
        $telegramSent = false;
        $errorMessage = null;
        
        if (!$settings->telegram_bot_token || !$settings->telegram_chat_id) {
            \Log::warning('Telegram bot not configured', [
                'has_token' => !empty($settings->telegram_bot_token),
                'has_chat_id' => !empty($settings->telegram_chat_id),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Telegram бот не налаштований. Будь ласка, зв\'яжіться з нами за телефоном.'
            ], 500);
        }
        
        // Перевіряємо формат chat_id (може бути негативним для груп)
        $chatId = trim($settings->telegram_chat_id);
        // Перевіряємо, чи це число (може бути негативним)
        if (!preg_match('/^-?\d+$/', $chatId)) {
            \Log::error('Invalid chat_id format', [
                'chat_id' => $settings->telegram_chat_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Неправильний формат Chat ID. Chat ID має бути числом (може бути негативним для груп).'
            ], 500);
        }
        
        $telegramSent = $this->sendToTelegram($settings->telegram_bot_token, $settings->telegram_chat_id, $message, $errorMessage);

        if ($telegramSent) {
            return response()->json([
                'success' => true,
                'message' => 'Ваша заявка успішно відправлена! Ми зв\'яжемося з вами найближчим часом.'
            ]);
        } else {
            \Log::error('Failed to send booking to Telegram', [
                'error' => $errorMessage,
                'tour_id' => $tour->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Помилка відправки заявки: ' . ($errorMessage ?? 'невідома помилка') . '. Будь ласка, спробуйте зв\'язатися з нами безпосередньо за телефоном.'
            ], 500);
        }
    }

    private function formatTelegramMessage($tour, $data)
    {
        // Екрануємо HTML спеціальні символи
        $escapeHtml = function($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        };
        
        $priceOptionText = $escapeHtml($data['price_option']);
        
        $message = "🎿 <b>Нова заявка на бронювання туру</b>\n\n";
        $message .= "📋 <b>Тур:</b> " . $escapeHtml($tour->name) . "\n";
        $message .= "📍 <b>Курорт:</b> " . $escapeHtml($tour->resort) . ", " . $escapeHtml($tour->country) . "\n";
        $message .= "📅 <b>Дата:</b> " . $tour->start_date->format('d.m.Y') . " - " . $tour->end_date->format('d.m.Y') . "\n\n";
        
        $message .= "👤 <b>Контактна інформація:</b>\n";
        $message .= "• Ім'я: " . $escapeHtml($data['first_name']) . "\n";
        $message .= "• Прізвище: " . $escapeHtml($data['last_name']) . "\n";
        $message .= "• Телефон: " . $escapeHtml($data['phone']) . "\n";
        
        if (!empty($data['telegram_username'])) {
            $username = str_replace('@', '', $data['telegram_username']);
            $message .= "• Telegram: @" . $escapeHtml($username) . "\n";
        }
        
        $message .= "\n💰 <b>Деталі бронювання:</b>\n";
        $message .= "• Варіант ціни: " . $priceOptionText . "\n";
        $message .= "• Кількість місць: " . $data['places'] . "\n";
        
        $message .= "\n🕐 <b>Час заявки:</b> " . now()->format('d.m.Y H:i') . "\n";
        
        return $message;
    }

    private function sendToTelegram($botToken, $chatId, $message, &$errorMessage = null)
    {
        try {
            // Переконуємося, що chat_id - це число (може бути негативним для груп)
            $chatId = trim($chatId);
            if (!preg_match('/^-?\d+$/', $chatId)) {
                $errorMessage = 'Chat ID має бути числом (може бути негативним для груп)';
                return false;
            }
            
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            
            // Спочатку спробуємо з HTML форматуванням
            // Використовуємо рядок для chat_id, щоб зберегти негативні значення
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $chatId, // Може бути негативним для груп
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['ok']) && $result['ok'] === true) {
                    return true;
                } else {
                    $errorMessage = $result['description'] ?? 'Невідома помилка Telegram API';
                    \Log::error('Telegram API error', [
                        'response' => $result,
                        'chat_id' => $chatId,
                    ]);
                    
                    // Якщо помилка "chat not found", даємо більш детальну інформацію
                    if (strpos(strtolower($errorMessage), 'chat not found') !== false || 
                        strpos(strtolower($errorMessage), 'chat_id') !== false) {
                        $errorMessage = 'Чат не знайдено. Переконайтеся, що ви надіслали боту команду /start, або що Chat ID правильний.';
                    }
                    
                    // Якщо помилка з HTML, спробуємо без форматування
                    if (strpos($errorMessage, 'parse') !== false || strpos($errorMessage, 'HTML') !== false) {
                        return $this->sendToTelegramPlain($botToken, $chatId, $message, $errorMessage);
                    }
                    
                    return false;
                }
            } else {
                $errorMessage = 'HTTP помилка: ' . $response->status();
                $responseBody = $response->body();
                $responseJson = $response->json();
                
                \Log::error('Telegram HTTP error', [
                    'status' => $response->status(),
                    'body' => $responseBody,
                    'json' => $responseJson,
                    'chat_id' => $chatId,
                ]);
                
                // Якщо 400, спробуємо без форматування
                if ($response->status() == 400) {
                    return $this->sendToTelegramPlain($botToken, $chatId, $message, $errorMessage);
                }
                
                return false;
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            \Log::error('Telegram send exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
    
    private function sendToTelegramPlain($botToken, $chatId, $message, &$errorMessage = null)
    {
        try {
            // Конвертуємо HTML в простий текст
            $plainMessage = strip_tags($message);
            $plainMessage = html_entity_decode($plainMessage, ENT_QUOTES, 'UTF-8');
            // Замінюємо HTML теги на простий текст
            $plainMessage = str_replace(['<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<s>', '</s>', '<code>', '</code>', '<pre>', '</pre>', '<a href="', '">', '</a>'], 
                ['', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], 
                $message);
            $plainMessage = preg_replace('/<[^>]+>/', '', $plainMessage);
            
            // Переконуємося, що chat_id - це число (може бути негативним для груп)
            $chatId = trim($chatId);
            if (!preg_match('/^-?\d+$/', $chatId)) {
                $errorMessage = 'Chat ID має бути числом (може бути негативним для груп)';
                return false;
            }
            
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            
            // Використовуємо рядок для chat_id, щоб зберегти негативні значення
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $chatId, // Може бути негативним для груп
                'text' => $plainMessage,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['ok']) && $result['ok'] === true) {
                    return true;
                } else {
                    $errorMessage = $result['description'] ?? 'Невідома помилка Telegram API';
                    return false;
                }
            } else {
                $errorMessage = 'HTTP помилка: ' . $response->status() . ' - ' . ($response->json()['description'] ?? '');
                return false;
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return false;
        }
    }
}
