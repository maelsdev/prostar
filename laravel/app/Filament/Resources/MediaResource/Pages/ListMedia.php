<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use App\Models\MediaFile;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    public ?int $folderId = null;
    public ?MediaFile $currentFolder = null;

    public function mount(): void
    {
        parent::mount();

        $this->folderId = request()->get('folder');

        if ($this->folderId) {
            $this->currentFolder = MediaFile::find($this->folderId);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Назад')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => $this->currentFolder && $this->currentFolder->folder_id
                    ? MediaResource::getUrl('index') . '?folder=' . $this->currentFolder->folder_id
                    : MediaResource::getUrl('index'))
                ->visible(fn() => $this->folderId !== null)
                ->color('gray'),

            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    if ($this->folderId) {
                        $data['folder_id'] = $this->folderId;
                    }
                    return $data;
                }),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            'Медіатека' => MediaResource::getUrl('index'),
        ];

        if ($this->currentFolder) {
            $folder = $this->currentFolder;
            $path = [];

            while ($folder) {
                array_unshift($path, $folder);
                $folder = $folder->folder;
            }

            foreach ($path as $folder) {
                $breadcrumbs[$folder->name] = MediaResource::getUrl('index') . '?folder=' . $folder->id;
            }
        }

        return $breadcrumbs;
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        if ($this->folderId) {
            $query->where('folder_id', $this->folderId);
        } else {
            $query->whereNull('folder_id');
        }

        return $query;
    }
}
