<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Якщо завантажено файл, автоматично встановлюємо тип 'file'
        if (isset($data['path']) && !empty($data['path'])) {
            $data['type'] = 'file';
            
            // Отримуємо інформацію про файл
            $filePath = $data['path'];
            $fullPath = Storage::disk('public')->path($filePath);
            
            if (file_exists($fullPath)) {
                $data['mime_type'] = mime_content_type($fullPath);
                $data['size'] = filesize($fullPath);
            }
        } elseif ($data['type'] === 'folder') {
            // Для папок очищаємо поля файлу
            $data['path'] = null;
            $data['mime_type'] = null;
            $data['size'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

