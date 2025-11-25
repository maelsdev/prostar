<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'resort',
        'country',
        'start_date',
        'end_date',
        'departure_time',
        'arrival_time',
        'nights_in_road',
        'nights_in_hotel',
        'days_on_resort',
        'hotel_name',
        'hotel_description',
        'meals_breakfast',
        'meals_dinner',
        'main_image_id',
        'hotel_id',
        'transfer_train',
        'transfer_bus',
        'transfer_plane',
        'transfer_taxi',
        'transfer_gaz66',
        'transfer_price_to_tour',
        'transfer_price_from_tour',
        'margin',
        'room_prices',
        'short_description',
        'full_description',
        'price_options',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'transfer_train' => 'boolean',
        'transfer_bus' => 'boolean',
        'transfer_plane' => 'boolean',
        'transfer_taxi' => 'boolean',
        'transfer_gaz66' => 'boolean',
        'meals_breakfast' => 'boolean',
        'meals_dinner' => 'boolean',
        'transfer_price_to_tour' => 'decimal:2',
        'transfer_price_from_tour' => 'decimal:2',
        'margin' => 'decimal:2',
        'room_prices' => 'array',
        'price_options' => 'array',
    ];

    /**
     * Отримати головне зображення туру
     */
    public function mainImage()
    {
        return $this->belongsTo(MediaFile::class, 'main_image_id');
    }

    /**
     * Отримати всі зображення туру (галерея)
     */
    public function images()
    {
        return $this->hasMany(TourImage::class)->orderBy('sort_order');
    }

    /**
     * Отримати готель туру
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Транслітерація українського тексту в латиницю для slug
     */
    public static function transliterate(string $text): string
    {
        $translit = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'h',
            'ґ' => 'g',
            'д' => 'd',
            'е' => 'e',
            'є' => 'ie',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'y',
            'і' => 'i',
            'ї' => 'i',
            'й' => 'i',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'kh',
            'ц' => 'ts',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'shch',
            'ь' => '',
            'ю' => 'iu',
            'я' => 'ia',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'H',
            'Ґ' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Є' => 'Ye',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'Y',
            'І' => 'I',
            'Ї' => 'Yi',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'Kh',
            'Ц' => 'Ts',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Shch',
            'Ь' => '',
            'Ю' => 'Yu',
            'Я' => 'Ya',
        ];

        $text = strtr($text, $translit);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }

    /**
     * Автоматично генеруємо slug при збереженні
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tour) {
            if (empty($tour->slug)) {
                $tour->slug = self::generateUniqueSlug($tour->name);
            }
        });

        static::updating(function ($tour) {
            // Якщо змінилася назва і slug порожній, генеруємо новий
            if ($tour->isDirty('name') && empty($tour->slug)) {
                $tour->slug = self::generateUniqueSlug($tour->name, $tour->id);
            }
        });
    }

    /**
     * Генерує унікальний slug
     */
    protected static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = self::transliterate($name);
        $slug = $baseSlug;
        $counter = 1;

        $query = self::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            $query = self::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
