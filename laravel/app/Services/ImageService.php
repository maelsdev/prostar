<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Стискає та конвертує зображення в WebP
     * 
     * @param string $path Шлях до оригінального файлу
     * @param string $disk Диск для зберігання
     * @param int $maxWidth Максимальна ширина (за замовчуванням 1920px)
     * @param int $quality Якість WebP (0-100, за замовчуванням 85)
     * @param string|null $customName Кастомна назва файлу (без розширення)
     * @return string|null Шлях до обробленого файлу або null при помилці
     */
    public function compressAndConvertToWebP(
        string $path,
        string $disk = 'public',
        int $maxWidth = 1920,
        int $quality = 85,
        ?string $customName = null
    ): ?string {
        try {
            $fullPath = Storage::disk($disk)->path($path);
            
            // Перевіряємо, чи існує файл
            if (!file_exists($fullPath)) {
                \Log::error("ImageService: File not found: {$fullPath}");
                return null;
            }

            // Завантажуємо зображення
            $image = $this->manager->read($fullPath);

            // Отримуємо розміри
            $width = $image->width();
            $height = $image->height();

            // Зменшуємо розмір, якщо потрібно
            if ($width > $maxWidth) {
                $image->scale(width: $maxWidth);
            }

            // Формуємо новий шлях з розширенням .webp
            $pathInfo = pathinfo($path);
            
            // Використовуємо кастомну назву, якщо вказано, інакше - оригінальну
            $filename = $customName 
                ? $this->sanitizeFilename($customName) 
                : $pathInfo['filename'];
            
            $newPath = $pathInfo['dirname'] . '/' . $filename . '.webp';
            $newFullPath = Storage::disk($disk)->path($newPath);

            // Зберігаємо як WebP
            $encoded = $image->toWebp($quality);
            $encoded->save($newFullPath);

            // Видаляємо оригінальний файл, якщо він не WebP
            if (strtolower($pathInfo['extension']) !== 'webp') {
                Storage::disk($disk)->delete($path);
            }

            \Log::info("ImageService: Image compressed and converted to WebP: {$newPath}");
            
            return $newPath;
        } catch (\Exception $e) {
            \Log::error("ImageService: Error processing image: " . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Очищає назву файлу від небезпечних символів та конвертує кирилицю в латиницю
     * 
     * @param string $filename Оригінальна назва
     * @return string Очищена назва
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Транслітерація кирилиці в латиницю
        $translit = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'є' => 'ye', 'ж' => 'zh', 'з' => 'z', 'и' => 'y',
            'і' => 'i', 'ї' => 'yi', 'й' => 'y', 'к' => 'k', 'л' => 'l',
            'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h',
            'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ь' => '',
            'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Є' => 'Ye', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'Y',
            'І' => 'I', 'Ї' => 'Yi', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L',
            'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H',
            'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '',
            'Ю' => 'Yu', 'Я' => 'Ya',
        ];
        
        $filename = strtr($filename, $translit);
        
        // Замінюємо пробіли та спецсимволи на дефіси
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $filename);
        $filename = preg_replace('/[\-]+/', '-', $filename);
        $filename = trim($filename, '-_');
        
        // Обмежуємо довжину
        $filename = mb_substr($filename, 0, 100);
        
        // Якщо після очищення порожньо, використовуємо дефолтну назву
        if (empty($filename)) {
            $filename = 'image-' . time();
        }
        
        return strtolower($filename);
    }
}

