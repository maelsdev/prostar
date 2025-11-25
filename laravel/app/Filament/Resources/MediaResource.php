<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Models\MediaFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaResource extends Resource
{
    protected static ?string $model = MediaFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $navigationLabel = 'Медіатека';
    
    protected static ?string $navigationGroup = 'Контент';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Тип елемента')
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->label('Тип')
                            ->options([
                                'folder' => 'Папка',
                                'file' => 'Файл',
                            ])
                            ->default('file')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'folder') {
                                    $set('path', null);
                                    $set('mime_type', null);
                                    $set('size', null);
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Основна інформація')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Назва')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Назва файлу або папки')
                            ->helperText('Введіть назву файлу або папки')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Автоматично генеруємо alt з назви, якщо alt порожній
                                if (empty($get('alt')) && !empty($state)) {
                                    $translit = MediaFile::transliterate($state);
                                    $set('alt', $translit);
                                }
                            }),

                        Forms\Components\TextInput::make('alt')
                            ->label('Alt текст')
                            ->maxLength(255)
                            ->placeholder('Альтернативний текст для зображення')
                            ->helperText('Alt текст для зображення. Якщо не вказано, буде автоматично згенеровано з назви.')
                            ->visible(fn ($get) => $get('type') === 'file'),

                        Forms\Components\Select::make('folder_id')
                            ->label('Батьківська папка')
                            ->relationship('folder', 'name', modifyQueryUsing: function ($query) {
                                return $query->where('type', 'folder');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getFullPath())
                            ->searchable()
                            ->preload()
                            ->placeholder('Коренева папка (без батьківської)')
                            ->helperText('Виберіть папку, в яку буде поміщено цей елемент'),
                    ]),

                Forms\Components\Section::make('Файл')
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('Завантажити файл')
                            ->disk('public')
                            ->directory('media')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif', 'image/svg+xml'])
                            ->maxSize(20480) // 20MB
                            ->deletable()
                            ->downloadable()
                            ->helperText('Завантажте файл. Підтримуються формати: JPEG, PNG, JPG, WebP, GIF, SVG. Максимальний розмір: 20MB.')
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('type') === 'file')
                            ->required(fn ($get) => $get('type') === 'file')
                            ->rules([
                                'nullable',
                                'file',
                                'mimes:jpeg,png,jpg,webp,gif,svg',
                                'max:20480',
                            ])
                            ->validationMessages([
                                'path.file' => 'Файл має бути валідним файлом',
                                'path.max' => 'Розмір файлу не повинен перевищувати 20MB',
                            ]),
                    ])
                    ->visible(fn ($get) => $get('type') === 'file'),

                Forms\Components\Section::make('Інформація про файл')
                    ->schema([
                        Forms\Components\TextInput::make('mime_type')
                            ->label('MIME тип')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($get) => $get('type') === 'file'),

                        Forms\Components\TextInput::make('size')
                            ->label('Розмір (байти)')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($get) => $get('type') === 'file'),
                    ])
                    ->columns(2)
                    ->visible(fn ($get, $record) => $get('type') === 'file' && $record && $record->path),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 5,
                '2xl' => 6,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('path')
                        ->label('')
                        ->disk('public')
                        ->height(200)
                        ->width('full')
                        ->defaultImageUrl(function ($record) {
                            if (!$record || !$record->isFile()) {
                                return null;
                            }
                            if (!$record->mime_type || !Str::startsWith($record->mime_type, 'image/')) {
                                return null;
                            }
                            return url('/images/placeholder.jpg');
                        })
                        ->extraAttributes(function ($record) {
                            return [
                                'class' => 'object-cover rounded-t-lg w-full',
                                'alt' => $record ? ($record->alt ?? '') : '',
                            ];
                        })
                        ->visible(function ($record) {
                            if (!$record) return false;
                            if (!$record->isFile()) return false;
                            return $record->mime_type && Str::startsWith($record->mime_type, 'image/');
                        }),

                Tables\Columns\IconColumn::make('type')
                        ->label('')
                    ->icon(fn (string $state): string => match ($state) {
                        'folder' => 'heroicon-o-folder',
                            'file' => 'heroicon-o-document',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'folder' => 'warning',
                        'file' => 'success',
                        default => 'gray',
                        })
                        ->size(64)
                        ->extraAttributes(['class' => 'mx-auto py-8'])
                        ->visible(function ($record) {
                            if (!$record) return false;
                            if ($record->isFolder()) return true;
                            if ($record->isFile() && (!$record->mime_type || !Str::startsWith($record->mime_type, 'image/'))) {
                                return true;
                            }
                            return false;
                        }),

                Tables\Columns\TextColumn::make('name')
                        ->label('')
                    ->searchable()
                    ->sortable()
                        ->weight('bold')
                        ->size('sm')
                        ->limit(30)
                        ->tooltip(function ($record) {
                            return $record ? $record->name : null;
                    })
                    ->url(function ($record) {
                            if ($record && $record->isFolder()) {
                                return static::getUrl('index') . '?folder=' . $record->id;
                            }
                            return null;
                        })
                        ->extraAttributes(['class' => 'px-4 pt-2 text-center']),

                    Tables\Columns\TextColumn::make('file_info')
                        ->label('')
                        ->getStateUsing(function ($record) {
                            if (!$record || !$record->isFile()) return null;
                            $info = [];
                            if ($record->size) {
                                $info[] = self::formatBytes($record->size);
                            }
                            return !empty($info) ? implode(' • ', $info) : null;
                        })
                        ->size('xs')
                        ->color('gray')
                        ->extraAttributes(['class' => 'px-4 pb-4 text-center']),
                ])
                ->space(2)
                ->extraAttributes(function ($record) {
                    $classes = 'border rounded-lg overflow-hidden hover:shadow-lg transition-shadow bg-white dark:bg-gray-800';
                    $attrs = ['class' => $classes];
                    
                    if ($record && $record->isFolder()) {
                        $classes .= ' cursor-pointer';
                        $url = static::getUrl('index') . '?folder=' . $record->id;
                        $attrs['class'] = $classes;
                        $attrs['onclick'] = "window.location.href='" . $url . "'";
                    }
                    
                    return $attrs;
                }),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'file' => 'Файл',
                        'folder' => 'Папка',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Відкрити')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn ($record) => static::getUrl('index') . '?folder=' . $record->id)
                    ->visible(fn ($record) => $record && $record->isFolder())
                    ->color('warning'),
                    
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record || !$record->isFolder()),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view')
                    ->label('Переглянути')
                    ->icon('heroicon-o-eye')
                    ->url(function ($record) {
                        if (!$record || !$record->isFile() || !$record->path) {
                            return null;
                        }
                        try {
                            // Використовуємо asset() для правильного URL з портом
                            return asset('storage/' . $record->path);
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record && $record->isFile() && $record->path),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Немає файлів')
            ->emptyStateDescription('Створіть перший файл або папку')
            ->emptyStateIcon('heroicon-o-photo');
    }

    /**
     * Отримати всі ID дочірніх папок (рекурсивно)
     */
    protected static function getChildFolderIds(int $folderId): array
    {
        $ids = [];
        $children = MediaFile::where('folder_id', $folderId)
            ->where('type', 'folder')
            ->pluck('id')
            ->toArray();
        
        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, self::getChildFolderIds($childId));
        }
        
        return $ids;
    }

    /**
     * Форматування розміру файлу
     */
    protected static function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}

