<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'telegram_phone',
        'whatsapp_phone',
        'telegram_username',
        'telegram_bot_token',
        'telegram_chat_id',
        'show_language_switcher',
        'weather_resorts',
        'default_weather_resort',
        'openweather_api_key',
        'weatherapi_key',
        'weather_source',
        'logo_text',
        'logo_image',
        'weather_requests_remaining',
        'weather_last_updated',
    ];

    protected $casts = [
        'show_language_switcher' => 'boolean',
        'weather_resorts' => 'array',
        'weather_last_updated' => 'datetime',
    ];
    
    public static function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:255', 'regex:/^[\+]?[0-9\s\(\)\-]{10,20}$/'],
            'telegram_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9]{10,15}$/'],
            'whatsapp_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9]{10,15}$/'],
            'telegram_username' => ['required', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_]{5,32}$/'],
            'show_language_switcher' => ['boolean'],
            'weather_resorts' => ['required', 'array', 'min:1'],
            'weather_resorts.*.value' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'weather_resorts.*.label' => ['required', 'string', 'max:100'],
            'default_weather_resort' => ['required', 'string'],
        ];
    }

    /**
     * Отримати поточні налаштування (singleton)
     */
    public static function getSettings()
    {
        $settings = static::first();
        
        // Якщо weather_source не встановлено, встановити за замовчуванням
        if ($settings && empty($settings->weather_source)) {
            $settings->weather_source = 'weatherapi';
            $settings->save();
        }
        
        return $settings ?? static::create([
            'phone' => '+38(098) 12-12-011',
            'telegram_phone' => '380981212011',
            'whatsapp_phone' => '380981212011',
            'telegram_username' => 'pro_s_tar',
            'show_language_switcher' => true,
            'weather_resorts' => [
                ['value' => 'dragobrat', 'label' => 'Драгобрат'],
                ['value' => 'bukovel', 'label' => 'Буковель'],
                ['value' => 'slavske', 'label' => 'Славське'],
                ['value' => 'pylypets', 'label' => 'Пилипець'],
            ],
            'default_weather_resort' => 'dragobrat',
            'logo_text' => 'PROSTAR | RADUGAUA | SNІGOWEEK',
            'logo_image' => null,
            'weather_source' => 'weatherapi',
        ]);
    }
}
