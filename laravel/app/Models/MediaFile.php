<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'alt',
        'type',
        'path',
        'folder_id',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Отримати батьківську папку
     */
    public function folder()
    {
        return $this->belongsTo(MediaFile::class, 'folder_id');
    }

    /**
     * Отримати дочірні елементи (файли та папки)
     */
    public function children()
    {
        return $this->hasMany(MediaFile::class, 'folder_id')->orderBy('type')->orderBy('name');
    }

    /**
     * Перевірити, чи це папка
     */
    public function isFolder(): bool
    {
        return $this->type === 'folder';
    }

    /**
     * Перевірити, чи це файл
     */
    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /**
     * Отримати повний шлях до файлу/папки
     */
    public function getFullPath(): string
    {
        $path = $this->name;
        $parent = $this->folder;
        
        while ($parent) {
            $path = $parent->name . '/' . $path;
            $parent = $parent->folder;
        }
        
        return $path;
    }

    /**
     * Отримати URL для доступу до файлу
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->isFile() && $this->path) {
            return Storage::disk('public')->url($this->path);
        }
        return null;
    }

    /**
     * Транслітерація українського тексту в латиницю
     */
    public static function transliterate(string $text): string
    {
        $translit = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g',
            'д' => 'd', 'е' => 'e', 'є' => 'ie', 'ж' => 'zh', 'з' => 'z',
            'и' => 'y', 'і' => 'i', 'ї' => 'i', 'й' => 'i', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p',
            'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ь' => '', 'ю' => 'iu', 'я' => 'ia',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'H', 'Ґ' => 'G',
            'Д' => 'D', 'Е' => 'E', 'Є' => 'Ye', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'Y', 'І' => 'I', 'Ї' => 'Yi', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P',
            'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
            'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch',
            'Ь' => '', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];

        $text = strtr($text, $translit);
        
        // Видаляємо розширення файлу для генерації alt
        $text = preg_replace('/\.[^.]+$/', '', $text);
        
        // Перетворюємо на lowercase та замінюємо пробіли та спецсимволи на дефіси
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }

    /**
     * Отримати alt текст (автоматично генерується з назви, якщо не вказано)
     */
    public function getAltAttribute($value): ?string
    {
        // Якщо alt вказано, повертаємо його
        if (!empty($value)) {
            return $value;
        }
        
        // Якщо alt не вказано, генеруємо автоматично з назви
        if (!empty($this->name)) {
            return self::transliterate($this->name);
        }
        
        return null;
    }

    /**
     * Видалити файл з диску при видаленні запису
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($mediaFile) {
            // Автоматично генеруємо alt, якщо не вказано
            if (empty($mediaFile->alt) && !empty($mediaFile->name)) {
                $mediaFile->alt = self::transliterate($mediaFile->name);
            }
        });

        static::deleting(function ($mediaFile) {
            if ($mediaFile->isFile() && $mediaFile->path) {
                Storage::disk('public')->delete($mediaFile->path);
            }
        });
    }
}

