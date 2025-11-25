<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;

        // Перевірка на циклічні посилання: папка не може бути своєю батьківською
        if (isset($data['folder_id']) && $data['folder_id'] == $record->id) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['folder_id' => ['Папка не може бути своєю батьківською папкою']]
            );
        }

        // Перевірка на циклічні посилання: папка не може бути в своїй дочірній папці
        if (isset($data['folder_id']) && $data['folder_id']) {
            $childFolderIds = $this->getChildFolderIds($record->id);
            if (in_array($data['folder_id'], $childFolderIds)) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['folder_id' => ['Папка не може бути поміщена в свою дочірню папку']]
                );
            }
        }

        // Якщо змінюється тип з файлу на папку
        if ($record->isFile() && $data['type'] === 'folder') {
            // Видаляємо старий файл
            if ($record->path && Storage::disk('public')->exists($record->path)) {
                Storage::disk('public')->delete($record->path);
            }
            $data['path'] = null;
            $data['mime_type'] = null;
            $data['size'] = null;
        }
        // Якщо змінюється тип з папки на файл
        elseif ($record->isFolder() && $data['type'] === 'file') {
            // Перевіряємо, чи завантажено новий файл
            if (!isset($data['path']) || empty($data['path'])) {
                // Якщо файл не завантажено, залишаємо тип папки
                $data['type'] = 'folder';
            }
        }

        // Якщо завантажено новий файл
        if ($data['type'] === 'file' && isset($data['path']) && $data['path'] !== $record->path) {
            // Видаляємо старий файл
            if ($record->path && Storage::disk('public')->exists($record->path)) {
                Storage::disk('public')->delete($record->path);
            }

            // Отримуємо інформацію про новий файл
            $filePath = $data['path'];
            $fullPath = Storage::disk('public')->path($filePath);
            
            if (file_exists($fullPath)) {
                $data['mime_type'] = mime_content_type($fullPath);
                $data['size'] = filesize($fullPath);
            }
        }

        return $data;
    }

    /**
     * Отримати всі ID дочірніх папок (рекурсивно)
     */
    protected function getChildFolderIds(int $folderId): array
    {
        $ids = [];
        $children = \App\Models\MediaFile::where('folder_id', $folderId)
            ->where('type', 'folder')
            ->pluck('id')
            ->toArray();
        
        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getChildFolderIds($childId));
        }
        
        return $ids;
    }
}

