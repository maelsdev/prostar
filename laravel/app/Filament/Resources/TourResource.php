<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourResource\Pages;
use App\Filament\Resources\TourResource\RelationManagers;
use App\Models\Tour;
use App\Models\MediaFile;
use App\Models\TourImage;
use App\Models\Hotel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    
    protected static ?string $navigationLabel = 'Тури';
    
    protected static ?string $navigationGroup = 'Контент';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('TourTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Основна інформація')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Основна інформація')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Назва туру')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Альпійські курорти')
                            ->helperText('Назва туру для відображення')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Автоматично генеруємо slug з назви, якщо slug порожній
                                if (empty($get('slug'))) {
                                    $slug = \App\Models\Tour::transliterate($state);
                                    $set('slug', $slug);
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->helperText('Автоматично генерується з назви туру. Можна редагувати вручну.')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),
                            
                        Forms\Components\TextInput::make('resort')
                            ->label('Курорт')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Буковель')
                            ->helperText('Назва гірськолижного курорту'),
                            
                        Forms\Components\TextInput::make('country')
                            ->label('Країна')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Україна')
                            ->helperText('Країна, де розташований курорт'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Дати та час туру')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата старту')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->helperText('Дата початку туру'),
                            
                        Forms\Components\TimePicker::make('departure_time')
                            ->label('Час відправлення')
                            ->native(false)
                            ->displayFormat('H:i')
                            ->helperText('Час відправлення з Києва')
                            ->seconds(false),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата завершення')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->helperText('Дата завершення туру')
                            ->after('start_date'),
                            
                        Forms\Components\TimePicker::make('arrival_time')
                            ->label('Час прибуття')
                            ->native(false)
                            ->displayFormat('H:i')
                            ->helperText('Час прибуття в Київ')
                            ->seconds(false),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Тривалість туру')
                    ->schema([
                        Forms\Components\TextInput::make('nights_in_road')
                            ->label('Ночі в дорозі')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Кількість ночей в дорозі'),
                            
                        Forms\Components\TextInput::make('nights_in_hotel')
                            ->label('Ночі в готелі')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Кількість ночей в готелі'),
                            
                        Forms\Components\TextInput::make('days_on_resort')
                            ->label('Дні на курорті')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Кількість днів на курорті'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Готель')
                    ->schema([
                        Forms\Components\Select::make('hotel_id')
                            ->label('Оберіть готель')
                            ->relationship('hotel', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Оберіть готель зі списку')
                            ->helperText('Виберіть готель зі списку або створіть новий')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('create_hotel')
                                    ->label('Створити новий готель')
                                    ->icon('heroicon-o-plus')
                                    ->url(fn () => \App\Filament\Resources\HotelResource::getUrl('create'))
                                    ->openUrlInNewTab()
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Назва готелю')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return Hotel::create($data)->id;
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Очищаємо старі поля, якщо вибрано готель
                                if ($state) {
                                    $set('hotel_name', null);
                                }
                            }),
                            
                        Forms\Components\Placeholder::make('hotel_info')
                            ->label('')
                            ->content(function ($get) {
                                $hotelId = $get('hotel_id');
                                if ($hotelId) {
                                    $hotel = Hotel::with('rooms')->find($hotelId);
                                    if ($hotel) {
                                        $roomsInfo = $hotel->rooms->map(function ($room) {
                                            $bedTypesArray = [];
                                            if (is_array($room->bed_types)) {
                                                if (isset($room->bed_types['single']) && $room->bed_types['single'] > 0) {
                                                    $bedTypesArray[] = $room->bed_types['single'] . ' односпальн' . ($room->bed_types['single'] > 1 ? 'их' : 'е');
                                                }
                                                if (isset($room->bed_types['double']) && $room->bed_types['double'] > 0) {
                                                    $bedTypesArray[] = $room->bed_types['double'] . ' двоспальн' . ($room->bed_types['double'] > 1 ? 'их' : 'е');
                                                }
                                            }
                                            $bedTypes = !empty($bedTypesArray) ? implode(', ', $bedTypesArray) : 'не вказано';
                                            
                                            return sprintf(
                                                '<strong>%s</strong> - %s кімн., ліжка: %s',
                                                $room->room_type,
                                                $room->rooms_count,
                                                $bedTypes
                                            );
                                        })->join('<br>');
                                        
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">' .
                                            '<p class="font-semibold text-sm mb-2">Номери готелю:</p>' .
                                            '<div class="text-sm text-gray-600 dark:text-gray-400">' .
                                            ($roomsInfo ?: '<em>Номерів ще не додано</em>') .
                                            '</div>' .
                                            '</div>'
                                        );
                                    }
                                }
                                return new \Illuminate\Support\HtmlString(
                                    '<p class="text-sm text-gray-500 dark:text-gray-400">Оберіть готель, щоб побачити доступні номери</p>'
                                );
                            })
                            ->visible(fn ($get) => $get('hotel_id'))
                            ->columnSpanFull(),
                            
                        // Старі поля для сумісності (якщо не використовується готель)
                        Forms\Components\TextInput::make('hotel_name')
                            ->label('Назва готелю (вручну)')
                            ->maxLength(255)
                            ->placeholder('Назва готелю')
                            ->helperText('Або вкажіть назву готелю вручну')
                            ->visible(fn ($get) => !$get('hotel_id')),
                            
                        Forms\Components\Textarea::make('hotel_description')
                            ->label('Опис готелю')
                            ->rows(3)
                            ->placeholder('Опис готелю, розташування, умови проживання')
                            ->helperText('Додаткова інформація про готель')
                            ->visible(fn ($get) => !$get('hotel_id'))
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('meals_breakfast')
                                    ->label('☕ Сніданки')
                                    ->helperText('Включені сніданки')
                                    ->default(false),
                                    
                                Forms\Components\Toggle::make('meals_dinner')
                                    ->label('🍽️ Вечері')
                                    ->helperText('Включені вечері')
                                    ->default(false),
                            ])
                            ->columnSpanFull()
                            ->visible(fn ($get) => !$get('hotel_id')),
                            
                        Forms\Components\Placeholder::make('meals_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<p class="text-sm text-gray-600 dark:text-gray-400">' .
                                'Якщо нічого не відмічено, буде відображатися "Без харчування"' .
                                '</p>'
                            ))
                            ->columnSpanFull()
                            ->visible(fn ($get) => !$get('hotel_id')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Головне фото туру')
                    ->schema([
                        Forms\Components\Select::make('main_image_id')
                            ->label('Оберіть зображення з медіатеки')
                            ->relationship('mainImage', 'name', modifyQueryUsing: function ($query) {
                                return $query->where('type', 'file')
                                    ->whereNotNull('mime_type')
                                    ->where(function ($q) {
                                        $q->where('mime_type', 'like', 'image/%');
                                    });
                            })
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->name . ' (' . ($record->mime_type ?? 'image') . ')';
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->placeholder('Оберіть зображення з медіатеки')
                            ->helperText('Виберіть головне фото туру з медіатеки')
                            ->columnSpanFull()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('open_media')
                                    ->label('Відкрити медіатеку')
                                    ->icon('heroicon-o-photo')
                                    ->url(fn () => \App\Filament\Resources\MediaResource::getUrl('index'))
                                    ->openUrlInNewTab()
                            ),
                            
                        Forms\Components\Placeholder::make('image_preview')
                            ->label('Прев\'ю зображення')
                            ->content(function ($get, $record) {
                                $imageId = $get('main_image_id') ?? $record?->main_image_id;
                                if ($imageId) {
                                    $image = MediaFile::find($imageId);
                                    if ($image && $image->path) {
                                        $url = asset('storage/' . $image->path);
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="mt-2">
                                                <img src="' . e($url) . '" 
                                                     alt="' . e($image->alt ?? $image->name) . '" 
                                                     class="max-w-full h-auto rounded-lg border border-gray-300 dark:border-gray-700"
                                                     style="max-height: 300px;">
                                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">' . e($image->name) . '</p>
                                            </div>'
                                        );
                                    }
                                }
                                return new \Illuminate\Support\HtmlString('<p class="text-gray-500 dark:text-gray-400 text-sm">Зображення не обрано</p>');
                            })
                            ->visible(fn ($get) => $get('main_image_id'))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Галерея зображень туру')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship('images')
                            ->schema([
                                Forms\Components\Select::make('media_file_id')
                                    ->label('Зображення')
                                    ->relationship('mediaFile', 'name', modifyQueryUsing: function ($query) {
                                        return $query->where('type', 'file')
                                            ->whereNotNull('mime_type')
                                            ->where(function ($q) {
                                                $q->where('mime_type', 'like', 'image/%');
                                            });
                                    })
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        return $record->name . ' (' . ($record->mime_type ?? 'image') . ')';
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('open_media')
                                            ->label('Відкрити медіатеку')
                                            ->icon('heroicon-o-photo')
                                            ->url(fn () => \App\Filament\Resources\MediaResource::getUrl('index'))
                                            ->openUrlInNewTab()
                                    ),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Порядок сортування')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Менше число = вище в списку'),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['media_file_id'] ? 'Зображення #' . ($state['sort_order'] ?? 0) : null
                            )
                            ->addActionLabel('Додати зображення')
                            ->columnSpanFull()
                            ->helperText('Додайте кілька зображень для галереї туру'),
                    ]),

                Forms\Components\Section::make('Трансфери')
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\Toggle::make('transfer_train')
                                    ->label('🚂 Потяг')
                                    ->helperText('Трансфер потягом')
                                    ->default(false)
                                    ->inline(false),

                                Forms\Components\Toggle::make('transfer_bus')
                                    ->label('🚌 Автобус')
                                    ->helperText('Трансфер автобусом')
                                    ->default(false)
                                    ->inline(false),

                                Forms\Components\Toggle::make('transfer_plane')
                                    ->label('✈️ Літак')
                                    ->helperText('Трансфер літаком')
                                    ->default(false)
                                    ->inline(false),

                                Forms\Components\Toggle::make('transfer_taxi')
                                    ->label('🚕 Маршрутне таксі')
                                    ->helperText('Трансфер маршрутним таксі')
                                    ->default(false)
                                    ->inline(false),

                                Forms\Components\Toggle::make('transfer_gaz66')
                                    ->label('🚛 ГАЗ 66')
                                    ->helperText('Трансфер ГАЗ 66')
                                    ->default(false)
                                    ->inline(false),
                            ]),
                    ])
                    ->description('Оберіть доступні типи трансферу для туру'),

                Forms\Components\Section::make('Опис туру')
                    ->schema([
                        Forms\Components\Textarea::make('short_description')
                            ->label('Короткий опис')
                            ->placeholder('Коротке речення про тур')
                            ->helperText('Одне речення, яке коротко описує тур')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('full_description')
                            ->label('Повний опис')
                            ->placeholder('Детальний опис туру...')
                            ->helperText('Повний опис туру з можливістю форматування тексту')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'codeBlock',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('price_options')
                            ->label('Варіанти ціни')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Ціна')
                                    ->placeholder('1000')
                                    ->numeric()
                                    ->prefix('₴')
                                    ->required()
                                    ->helperText('Вкажіть ціну в гривнях'),

                                Forms\Components\Textarea::make('description')
                                    ->label('Опис варіанту')
                                    ->placeholder('Опис цього варіанту ціни')
                                    ->rows(2)
                                    ->required()
                                    ->helperText('Опишіть, що включено в цю ціну'),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['price'] ? '₴' . number_format($state['price'], 0, ',', ' ') . ' - ' . ($state['description'] ?? 'Без опису') : null
                            )
                            ->addActionLabel('Додати варіант ціни')
                            ->columnSpanFull(),
                    ]),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Калькулятор туру')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Forms\Components\Section::make('Готель')
                                    ->schema([
                                        Forms\Components\Select::make('hotel_id')
                                            ->label('Оберіть готель')
                                            ->options(Hotel::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->placeholder('Оберіть готель зі списку')
                                            ->helperText('Оберіть готель для імпорту типів номерів')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // Очищаємо room_prices при зміні готелю
                                                $set('room_prices', null);
                                            })
                                            ->visible(fn ($get) => empty($get('room_prices'))),
                                        
                                        Forms\Components\View::make('filament.forms.components.import-hotel-rooms-button')
                                            ->visible(fn ($get) => !empty($get('hotel_id')) && empty($get('room_prices'))),
                                        
                                        Forms\Components\View::make('filament.forms.components.tour-room-prices')
                                            ->viewData(fn ($get) => [
                                                'hotel_id' => $get('hotel_id'),
                                                'room_prices' => $get('room_prices') ?? [],
                                                'transfer_price_to_tour' => $get('transfer_price_to_tour') ?? 0,
                                                'transfer_price_from_tour' => $get('transfer_price_from_tour') ?? 0,
                                            ])
                                            ->visible(fn ($get) => !empty($get('hotel_id'))),
                                    ]),
                                
                                Forms\Components\Section::make('Трансфери')
                                    ->schema([
                                        Forms\Components\TextInput::make('transfer_price_to_tour')
                                            ->label('Трансфер в тур')
                                            ->numeric()
                                            ->prefix('₴')
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->placeholder('0.00')
                                            ->helperText('Вартість трансферу до місця призначення')
                                            ->reactive(),
                                        
                                        Forms\Components\TextInput::make('transfer_price_from_tour')
                                            ->label('Трансфер з туру')
                                            ->numeric()
                                            ->prefix('₴')
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->placeholder('0.00')
                                            ->helperText('Вартість трансферу з місця призначення')
                                            ->reactive(),
                                    ])
                                    ->columns(2),
                                
                                Forms\Components\Section::make('Дії')
                                    ->schema([
                                        Forms\Components\View::make('filament.forms.components.clear-calculator-button'),
                                    ])
                                    ->collapsible(false),
                            ])
                            ->visible(fn ($record) => $record && $record->exists),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->with('mainImage');
            })
            ->columns([
                Tables\Columns\ImageColumn::make('mainImage.path')
                    ->label('Фото')
                    ->disk('public')
                    ->size(60)
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.jpg'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Назва туру')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('resort')
                    ->label('Курорт')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('country')
                    ->label('Країна')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Дата старту')
                    ->date('d.m.Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Дата завершення')
                    ->date('d.m.Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->label('Країна')
                    ->options(function () {
                        return \App\Models\Tour::query()
                            ->distinct()
                            ->pluck('country', 'country')
                            ->toArray();
                    }),
                    
                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date_from')
                            ->label('Дата старту від')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                        Forms\Components\DatePicker::make('start_date_until')
                            ->label('Дата старту до')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['start_date_from'],
                                fn ($query, $date) => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_date_until'],
                                fn ($query, $date) => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('future_tours')
                    ->label('Тільки майбутні тури')
                    ->query(fn ($query) => $query->where('start_date', '>=', now()->toDateString())),
                    
                Tables\Filters\Filter::make('past_tours')
                    ->label('Тільки минулі тури')
                    ->query(fn ($query) => $query->where('end_date', '<', now()->toDateString())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'asc');
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
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
