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
                                Forms\Components\Section::make('Бронювання')
                    ->schema([
                        Forms\Components\Toggle::make('is_booking_enabled')
                            ->label('Увімкнути бронювання')
                            ->helperText('Якщо увімкнено, користувачі зможуть бронювати цей тур. Якщо вимкнено, буде показано повідомлення про відсутність місць.')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
                    
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
                                    // Підтягуємо опис з готелю (саме поле description, НЕ структуру номерів)
                                    $hotel = Hotel::find($state);
                                    if ($hotel) {
                                        // Підтягуємо САМЕ поле description з готелю, не scheme_description і не структуру номерів
                                        $description = $hotel->getAttribute('description');
                                        // Якщо description порожнє або null, залишаємо поле порожнім
                                        $set('hotel_description', $description ?: null);
                                    } else {
                                        $set('hotel_description', null);
                                    }
                                } else {
                                    // Якщо готель не вибрано, очищаємо опис
                                    $set('hotel_description', null);
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
                            
                        Forms\Components\RichEditor::make('hotel_description')
                            ->label('Опис готелю')
                            ->placeholder('Опис готелю, розташування, умови проживання')
                            ->helperText(fn ($get) => $get('hotel_id') 
                                ? 'Опис автоматично підтягнуто з готелю. Можна редагувати та форматувати текст.' 
                                : 'Введіть опис готелю вручну з можливістю форматування')
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
                            ])
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // Якщо є запис і вибрано готель, але опис не заповнений, підтягуємо з готелю
                                if ($record && $record->hotel_id && empty($state)) {
                                    $hotel = Hotel::find($record->hotel_id);
                                    if ($hotel) {
                                        // Підтягуємо саме поле description з готелю, не структуру номерів
                                        $description = $hotel->description ?? null;
                                        if ($description) {
                                            $component->state($description);
                                        }
                                    }
                                }
                            })
                            ->reactive()
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
                            ->columnSpanFull(),
                            
                        Forms\Components\Placeholder::make('meals_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<p class="text-sm text-gray-600 dark:text-gray-400">' .
                                'Якщо нічого не відмічено, буде відображатися "Без харчування"' .
                                '</p>'
                            ))
                            ->columnSpanFull(),
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
                                // Компактна шапка з варіантами ціни
                                Forms\Components\Placeholder::make('price_variants_header')
                                    ->label('')
                                    ->content(function ($get, $record) {
                                        $roomTypes = $get('calculator_room_types') ?? [];
                                        
                                        if (empty($roomTypes)) {
                                            return new \Illuminate\Support\HtmlString('<div class="text-xs text-gray-400 py-1">Оберіть готель</div>');
                                        }
                                        
                                        // Розраховуємо вартість трансферів на 1 особу
                                        $transfers = $get('calculator_transfers') ?? [];
                                        $transferCostPerPerson = 0;
                                        
                                        if (is_array($transfers)) {
                                            foreach ($transfers as $transfer) {
                                                if (!is_array($transfer)) continue;
                                                
                                                $transferType = $transfer['transfer_type'] ?? null;
                                                
                                                // Для потяга
                                                if ($transferType === 'train') {
                                                    $trainToPrice = (float)($transfer['train_to_price'] ?? 0);
                                                    $trainToBooking = (float)($transfer['train_to_booking'] ?? 0);
                                                    $trainFromPrice = (float)($transfer['train_from_price'] ?? 0);
                                                    $trainFromBooking = (float)($transfer['train_from_booking'] ?? 0);
                                                    $transferCostPerPerson += $trainToPrice + $trainToBooking + $trainFromPrice + $trainFromBooking;
                                                }
                                                // Для ГАЗ 66
                                                elseif ($transferType === 'gaz66') {
                                                    $gaz66ToPrice = (float)($transfer['gaz66_to_price'] ?? 0);
                                                    $gaz66ToSeats = (float)($transfer['gaz66_to_seats'] ?? 1);
                                                    $gaz66FromPrice = (float)($transfer['gaz66_from_price'] ?? 0);
                                                    $gaz66FromSeats = (float)($transfer['gaz66_from_seats'] ?? 1);
                                                    
                                                    if ($gaz66ToSeats > 0) {
                                                        $transferCostPerPerson += $gaz66ToPrice / $gaz66ToSeats;
                                                    }
                                                    if ($gaz66FromSeats > 0) {
                                                        $transferCostPerPerson += $gaz66FromPrice / $gaz66FromSeats;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Отримуємо кількість ночей з даних туру
                                        $nightsCount = (int)($get('nights_in_hotel') ?? $record?->nights_in_hotel ?? 1);
                                        if ($nightsCount < 1) {
                                            $nightsCount = 1;
                                        }
                                        
                                        // Розраховуємо загальну вартість додаткових витрат
                                        $additionalCosts = $get('calculator_additional_costs') ?? [];
                                        $totalAdditionalCosts = 0;
                                        if (is_array($additionalCosts)) {
                                            foreach ($additionalCosts as $cost) {
                                                if (is_array($cost) && isset($cost['cost'])) {
                                                    $totalAdditionalCosts += (float)($cost['cost'] ?? 0);
                                                }
                                            }
                                        }
                                        
                                        $variants = [];
                                        foreach ($roomTypes as $type) {
                                            if (!is_array($type)) continue;
                                            
                                            $places = (int)($type['places'] ?? 0);
                                            $price = (float)($type['price_per_place'] ?? 0);
                                            $margin = (float)($type['margin'] ?? 0);
                                            
                                            // Розрахунок: (ціна_за_місце * кількість_ночей) + маржа + трансфери + додаткові витрати
                                            $hotelCost = $price * $nightsCount;
                                            $total = $hotelCost + $margin + $transferCostPerPerson + $totalAdditionalCosts;
                                            
                                            if ($places > 0 && ($price > 0 || $margin > 0 || $transferCostPerPerson > 0)) {
                                                $placesLabel = match($places) {
                                                    1 => '1-місне',
                                                    2 => '2-місне',
                                                    3 => '3-місне',
                                                    4 => '4-місне',
                                                    5 => '5-місне',
                                                    default => $places . '-місне',
                                                };
                                                
                                                $variants[] = '<span class="text-xs">' . e($placesLabel) . ' <strong class="text-primary-600">' . number_format($total, 0, '.', '') . ' грн</strong></span>';
                                            }
                                        }
                                        
                                        $html = '<div class="space-y-1 py-1">';
                                        
                                        // Показуємо загальну вартість трансферів окремо
                                        if ($transferCostPerPerson > 0) {
                                            $html .= '<div class="text-xs border-b border-gray-200 dark:border-gray-700 pb-1 mb-1">';
                                            $html .= '<span class="text-gray-600 dark:text-gray-400">Трансфери:</span> ';
                                            $html .= '<strong class="text-primary-600 font-semibold">' . number_format($transferCostPerPerson, 0, '.', '') . ' грн</strong>';
                                            $html .= '</div>';
                                        }
                                        
                                        // Показуємо загальну вартість додаткових витрат окремо
                                        if ($totalAdditionalCosts > 0) {
                                            $html .= '<div class="text-xs border-b border-gray-200 dark:border-gray-700 pb-1 mb-1">';
                                            $html .= '<span class="text-gray-600 dark:text-gray-400">Додаткові витрати:</span> ';
                                            $html .= '<strong class="text-primary-600 font-semibold">' . number_format($totalAdditionalCosts, 0, '.', '') . ' грн</strong>';
                                            $html .= '</div>';
                                        }
                                        
                                        // Показуємо варіанти розміщення
                                        if (empty($variants)) {
                                            $html .= '<div class="text-xs text-gray-400">Вкажіть ціни</div>';
                                        } else {
                                            $html .= '<div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs">' . implode(' | ', $variants) . '</div>';
                                        }
                                        
                                        $html .= '</div>';
                                        
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->live()
                                    ->key(fn ($get, $record) => md5(json_encode([
                                        $get('calculator_room_types'),
                                        $get('calculator_transfers'),
                                        $get('calculator_additional_costs'),
                                        $get('nights_in_hotel') ?? $record?->nights_in_hotel,
                                    ])))
                                    ->columnSpanFull(),
                                
                                Forms\Components\Select::make('calculator_hotel_id')
                                    ->label('Готель')
                                    ->relationship('calculatorHotel', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->extraAttributes(['class' => 'text-sm'])
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if (!$state) {
                                            $set('calculator_room_types', []);
                                            return;
                                        }
                                        
                                        $hotel = \App\Models\Hotel::with('rooms')->find($state);
                                        if (!$hotel) {
                                            $set('calculator_room_types', []);
                                            return;
                                        }
                                        
                                        // Завжди починаємо з нульових значень при виборі готелю
                                        $roomTypes = [];
                                        foreach ($hotel->rooms as $room) {
                                            $places = $room->places_per_room;
                                            if ($places > 0) {
                                                if (!isset($roomTypes[$places])) {
                                                    $roomTypes[$places] = [
                                                        'places' => $places,
                                                        'quantity' => 0,
                                                        'price_per_place' => 0,
                                                        'margin' => 0,
                                                    ];
                                                }
                                                $roomTypes[$places]['quantity'] += ($room->quantity ?? 1);
                                            }
                                        }
                                        
                                        ksort($roomTypes);
                                        $set('calculator_room_types', array_values($roomTypes));
                                    })
                                    ->columnSpanFull(),
                                
                                Forms\Components\Repeater::make('calculator_room_types')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Grid::make(5)
                                            ->schema([
                                                Forms\Components\Placeholder::make('room_type_label')
                                                    ->label('Тип номера')
                                                    ->content(function ($get) {
                                                        $places = (int)($get('places') ?? 0);
                                                        if ($places > 0) {
                                                            $label = match($places) {
                                                                1 => 'Одномісний',
                                                                2 => 'Двомісний',
                                                                3 => 'Тримісний',
                                                                4 => 'Чотиримісний',
                                                                5 => 'П\'ятимісний',
                                                                default => $places . '-місний',
                                                            };
                                                            return new \Illuminate\Support\HtmlString('<div class="text-xs py-1 px-1">' . e($label) . '</div>');
                                                        }
                                                        return new \Illuminate\Support\HtmlString('<div class="text-xs py-1 px-1 text-gray-400">-</div>');
                                                    })
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\TextInput::make('places')
                                                    ->label('Місць')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->extraInputAttributes(['class' => 'text-xs py-1 px-1 h-7'])
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Кількість')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->extraInputAttributes(['class' => 'text-xs py-1 px-1 h-7'])
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\TextInput::make('price_per_place')
                                                    ->label('Ціна/місце')
                                                    ->numeric()
                                                    ->prefix('₴')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->reactive()
                                                    ->extraInputAttributes(['class' => 'text-xs py-1 px-1 h-7'])
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\TextInput::make('margin')
                                                    ->label('Маржа')
                                                    ->numeric()
                                                    ->prefix('₴')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->reactive()
                                                    ->extraInputAttributes(['class' => 'text-xs py-1 px-1 h-7'])
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->defaultItems(0)
                                    ->disableItemCreation()
                                    ->disableItemDeletion()
                                    ->itemLabel(function (array $state): ?string {
                                        $places = (int)($state['places'] ?? 0);
                                        return $places > 0 ? $places . ' місць' : null;
                                    })
                                    ->visible(fn ($get) => !empty($get('calculator_hotel_id')))
                                    ->columnSpanFull(),
                                
                                Forms\Components\Repeater::make('calculator_transfers')
                                    ->label('Трансфери')
                                    ->schema([
                                        Forms\Components\Select::make('transfer_type')
                                            ->label('Тип трансферу')
                                            ->options([
                                                'train' => 'Потяг',
                                                'gaz66' => 'Газ 66',
                                            ])
                                            ->required()
                                            ->reactive()
                                            ->columnSpanFull(),
                                        
                                        // Поля для потяга
                                        Forms\Components\Section::make('Потяг туди')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('train_to_number')
                                                            ->label('Номер потяга')
                                                            ->maxLength(255)
                                                            ->columnSpan(1),
                                                        
                                                        Forms\Components\TextInput::make('train_to_booking')
                                                            ->label('Бронювання')
                                                            ->numeric()
                                                            ->prefix('₴')
                                                            ->step(0.01)
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                        
                                                        Forms\Components\TextInput::make('train_to_price')
                                                            ->label('Ціна за квиток')
                                                            ->numeric()
                                                            ->prefix('₴')
                                                            ->step(0.01)
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                    ]),
                                            ])
                                            ->visible(fn ($get) => $get('transfer_type') === 'train')
                                            ->collapsible(),
                                        
                                        Forms\Components\Section::make('Потяг назад')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('train_from_number')
                                                            ->label('Номер потяга')
                                                            ->maxLength(255)
                                                            ->columnSpan(1),
                                                        
                                                        Forms\Components\TextInput::make('train_from_booking')
                                                            ->label('Бронювання')
                                                            ->numeric()
                                                            ->prefix('₴')
                                                            ->step(0.01)
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                        
                                                        Forms\Components\TextInput::make('train_from_price')
                                                            ->label('Ціна за квиток')
                                                            ->numeric()
                                                            ->prefix('₴')
                                                            ->step(0.01)
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                    ]),
                                            ])
                                            ->visible(fn ($get) => $get('transfer_type') === 'train')
                                            ->collapsible(),
                                        
                                        // Поля для ГАЗ 66
                                        Forms\Components\Section::make('ГАЗ 66 туди')
                                            ->schema([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('gaz66_to_price')
                                                            ->label('Вартість')
                                                            ->numeric()
                                                            ->prefix('₴')
                                                            ->step(0.01)
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                        
                                                        Forms\Components\TextInput::make('gaz66_to_seats')
                                                            ->label('Кількість місць')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->default(1)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                    ]),
                                                
                                                Forms\Components\Placeholder::make('gaz66_to_per_person')
                                                    ->label('Вартість за 1 місце')
                                                    ->content(function ($get) {
                                                        $price = (float)($get('gaz66_to_price') ?? 0);
                                                        $seats = (float)($get('gaz66_to_seats') ?? 1);
                                                        $perPerson = $seats > 0 ? $price / $seats : 0;
                                                        return new \Illuminate\Support\HtmlString(
                                                            '<div class="text-xs font-semibold text-primary-600">₴' . number_format($perPerson, 2, '.', '') . '</div>'
                                                        );
                                                    })
                                                    ->reactive()
                                                    ->columnSpanFull(),
                                            ])
                                            ->visible(fn ($get) => $get('transfer_type') === 'gaz66')
                                            ->collapsible(),
                                        
                                        Forms\Components\Section::make('ГАЗ 66 назад')
                                            ->schema([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('gaz66_from_price')
                                                            ->label('Вартість')
                                                            ->numeric()
                                                            ->prefix('₴')
                                                            ->step(0.01)
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                        
                                                        Forms\Components\TextInput::make('gaz66_from_seats')
                                                            ->label('Кількість місць')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->default(1)
                                                            ->reactive()
                                                            ->columnSpan(1),
                                                    ]),
                                                
                                                Forms\Components\Placeholder::make('gaz66_from_per_person')
                                                    ->label('Вартість за 1 місце')
                                                    ->content(function ($get) {
                                                        $price = (float)($get('gaz66_from_price') ?? 0);
                                                        $seats = (float)($get('gaz66_from_seats') ?? 1);
                                                        $perPerson = $seats > 0 ? $price / $seats : 0;
                                                        return new \Illuminate\Support\HtmlString(
                                                            '<div class="text-xs font-semibold text-primary-600">₴' . number_format($perPerson, 2, '.', '') . '</div>'
                                                        );
                                                    })
                                                    ->reactive()
                                                    ->columnSpanFull(),
                                            ])
                                            ->visible(fn ($get) => $get('transfer_type') === 'gaz66')
                                            ->collapsible(),
                                    ])
                                    ->defaultItems(0)
                                    ->addActionLabel('Додати трансфер')
                                    ->itemLabel(function (array $state): ?string {
                                        $type = $state['transfer_type'] ?? null;
                                        if ($type === 'train') {
                                            return 'Потяг';
                                        } elseif ($type === 'gaz66') {
                                            return 'ГАЗ 66';
                                        }
                                        return 'Новий трансфер';
                                    })
                                    ->collapsible()
                                    ->columnSpanFull(),
                                
                                Forms\Components\Repeater::make('calculator_additional_costs')
                                    ->label('Додаткові витрати')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Назва')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\TextInput::make('cost')
                                                    ->label('Вартість')
                                                    ->numeric()
                                                    ->prefix('₴')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->required()
                                                    ->reactive()
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->defaultItems(0)
                                    ->addActionLabel('Додати витрату')
                                    ->itemLabel(function (array $state): ?string {
                                        $name = $state['name'] ?? null;
                                        $cost = (float)($state['cost'] ?? 0);
                                        if ($name) {
                                            return $name . ($cost > 0 ? ' (' . number_format($cost, 0, '.', '') . ' грн)' : '');
                                        }
                                        return 'Нова витрата';
                                    })
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn ($record) => $record && $record->exists),
                        
                        Forms\Components\Tabs\Tab::make('СРМ')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Forms\Components\Section::make('CRM таблиця')
                                    ->schema([
                                        Forms\Components\Placeholder::make('crm_info')
                                            ->label('')
                                            ->content(function ($get, $record) {
                                                // Перевіряємо чи існує CRM таблиця
                                                $hasCrm = false;
                                                try {
                                                    if ($record && isset($record->id)) {
                                                        $hasCrm = \App\Models\CrmTable::where('tour_id', $record->id)->exists();
                                                    }
                                                } catch (\Exception $e) {
                                                    // Ігноруємо помилку
                                                }
                                                
                                                if ($hasCrm) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="text-center py-4">
                                                            <p class="text-sm text-gray-600">CRM таблиця створена. Дані відображаються нижче.</p>
                                                        </div>'
                                                    );
                                                }
                                                
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="text-center py-12">
                                                        <div class="max-w-md mx-auto">
                                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            <h3 class="mt-2 text-sm font-medium text-gray-900">CRM таблиця не створена</h3>
                                                            <p class="mt-1 text-sm text-gray-500">Натисніть кнопку "Згенерувати СРМ" нижче для створення таблиці зі схеми готелю.</p>
                                                        </div>
                                                    </div>'
                                                );
                                            })
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('generate_crm')
                                                ->label('Згенерувати СРМ')
                                                ->icon('heroicon-o-sparkles')
                                                ->color('success')
                                                ->visible(function ($get, $record) {
                                                    if (!$record || !isset($record->id)) {
                                                        return false;
                                                    }
                                                    
                                                    // Перевіряємо чи вже згенерована CRM
                                                    try {
                                                        $hasCrm = \App\Models\CrmTable::where('tour_id', $record->id)->exists();
                                                        if ($hasCrm) {
                                                            return false;
                                                        }
                                                    } catch (\Exception $e) {
                                                        return false;
                                                    }
                                                    
                                                    // Перевіряємо чи обрано готель в калькуляторі
                                                    $hotelId = $get('calculator_hotel_id');
                                                    return !empty($hotelId);
                                                })
                                                ->requiresConfirmation()
                                                ->modalHeading('Згенерувати CRM таблицю')
                                                ->modalDescription('Ця дія створить нову CRM таблицю зі схеми обраного готелю.')
                                                ->modalSubmitActionLabel('Згенерувати')
                                                ->modalCancelActionLabel('Скасувати')
                                                ->action(function ($get, $set, $record) {
                                                    // Перевіряємо чи вже існує CRM таблиця
                                                    if (\App\Models\CrmTable::where('tour_id', $record->id)->exists()) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('CRM вже згенерована')
                                                            ->warning()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Отримуємо дані з калькулятора
                                                    $hotelId = $get('calculator_hotel_id');
                                                    
                                                    if (!$hotelId) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Помилка')
                                                            ->body('Оберіть готель в калькуляторі перед генерацією CRM')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Завантажуємо готель зі схемою
                                                    $hotel = \App\Models\Hotel::with(['schemeCategories.room', 'schemeCategories.items.places', 'rooms'])->find($hotelId);
                                                    if (!$hotel) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Помилка')
                                                            ->body('Готель не знайдено')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Перевіряємо чи заповнені номери в схемі
                                                    $hasEmptyRooms = false;
                                                    foreach ($hotel->schemeCategories as $category) {
                                                        if ($category->items->isEmpty()) {
                                                            $hasEmptyRooms = true;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    if ($hasEmptyRooms) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Помилка')
                                                            ->body('Схема готелю не заповнена. Заповніть номери в схемі готелю перед генерацією CRM.')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Створюємо CRM таблицю
                                                    $crmTable = \App\Models\CrmTable::create([
                                                        'tour_id' => $record->id,
                                                        'hotel_id' => $hotelId,
                                                    ]);
                                                    
                                                    // Отримуємо дані з калькулятора для розрахунку цін
                                                    // Спробуємо отримати з форми, якщо не вийде - з запису
                                                    $roomTypes = $get('calculator_room_types') ?? $record->calculator_room_types ?? [];
                                                    $nightsCount = (int)($get('nights_in_hotel') ?? $record->nights_in_hotel ?? 1);
                                                    $transfers = $get('calculator_transfers') ?? $record->calculator_transfers ?? [];
                                                    $additionalCosts = $get('calculator_additional_costs') ?? $record->calculator_additional_costs ?? [];
                                                    
                                                    // Якщо roomTypes порожній, спробуємо завантажити з запису
                                                    if (empty($roomTypes) && $record->calculator_room_types) {
                                                        $roomTypes = is_array($record->calculator_room_types) 
                                                            ? $record->calculator_room_types 
                                                            : json_decode($record->calculator_room_types, true) ?? [];
                                                    }
                                                    
                                                    // Розраховуємо вартість трансферів на 1 особу (один раз для всіх типів)
                                                    $transferCostPerPerson = 0;
                                                    if (is_array($transfers)) {
                                                        foreach ($transfers as $transfer) {
                                                            if (!is_array($transfer)) continue;
                                                            
                                                            $transferType = $transfer['transfer_type'] ?? null;
                                                            
                                                            if ($transferType === 'train') {
                                                                $trainToPrice = (float)($transfer['train_to_price'] ?? 0);
                                                                $trainToBooking = (float)($transfer['train_to_booking'] ?? 0);
                                                                $trainFromPrice = (float)($transfer['train_from_price'] ?? 0);
                                                                $trainFromBooking = (float)($transfer['train_from_booking'] ?? 0);
                                                                $transferCostPerPerson += $trainToPrice + $trainToBooking + $trainFromPrice + $trainFromBooking;
                                                            } elseif ($transferType === 'gaz66') {
                                                                $gaz66ToPrice = (float)($transfer['gaz66_to_price'] ?? 0);
                                                                $gaz66ToSeats = (float)($transfer['gaz66_to_seats'] ?? 1);
                                                                $gaz66FromPrice = (float)($transfer['gaz66_from_price'] ?? 0);
                                                                $gaz66FromSeats = (float)($transfer['gaz66_from_seats'] ?? 1);
                                                                
                                                                if ($gaz66ToSeats > 0) {
                                                                    $transferCostPerPerson += $gaz66ToPrice / $gaz66ToSeats;
                                                                }
                                                                if ($gaz66FromSeats > 0) {
                                                                    $transferCostPerPerson += $gaz66FromPrice / $gaz66FromSeats;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Розраховуємо загальну вартість додаткових витрат (один раз для всіх типів)
                                                    $totalAdditionalCosts = 0;
                                                    if (is_array($additionalCosts)) {
                                                        foreach ($additionalCosts as $cost) {
                                                            if (is_array($cost) && isset($cost['cost'])) {
                                                                $totalAdditionalCosts += (float)($cost['cost'] ?? 0);
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Перевіряємо чи є дані в калькуляторі
                                                    if (empty($roomTypes)) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Помилка')
                                                            ->body('Заповніть дані в калькуляторі перед генерацією CRM')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Створюємо мапу цін за типом номера (кількість місць -> ціна за місце)
                                                    $priceMap = [];
                                                    foreach ($roomTypes as $type) {
                                                        if (!is_array($type)) continue;
                                                        
                                                        $places = (int)($type['places'] ?? 0);
                                                        $pricePerPlace = (float)($type['price_per_place'] ?? 0);
                                                        $margin = (float)($type['margin'] ?? 0);
                                                        
                                                        // Ціна за місце = (ціна_за_місце * ночі) + маржа + трансфери + додаткові витрати
                                                        $totalPrice = ($pricePerPlace * $nightsCount) + $margin + $transferCostPerPerson + $totalAdditionalCosts;
                                                        // Округлюємо до цілих чисел
                                                        $priceMap[$places] = round($totalPrice);
                                                    }
                                                    
                                                    // Перевіряємо чи є хоча б одна ціна в мапі
                                                    if (empty($priceMap)) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Помилка')
                                                            ->body('Не вдалося розрахувати ціни. Перевірте дані в калькуляторі.')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Копіюємо категорії та номери
                                                    $sortOrder = 0;
                                                    foreach ($hotel->schemeCategories as $originalCategory) {
                                                        $crmCategory = \App\Models\CrmCategory::create([
                                                            'crm_table_id' => $crmTable->id,
                                                            'room_id' => $originalCategory->room_id,
                                                            'name' => $originalCategory->name,
                                                            'price_type' => $originalCategory->price_type,
                                                            'rooms_count' => $originalCategory->rooms_count,
                                                            'sort_order' => $sortOrder++,
                                                        ]);
                                                        
                                                        // Спочатку копіюємо тільки батьківські номери (is_parent = true)
                                                        $parentItemsMap = []; // Мапа для збереження зв'язків original_id => crm_id
                                                        foreach ($originalCategory->items->where('is_parent', true) as $originalParentItem) {
                                                            $crmParentItem = \App\Models\CrmItem::create([
                                                                'crm_category_id' => $crmCategory->id,
                                                                'parent_id' => null,
                                                                'place_number' => $originalParentItem->place_number,
                                                                'is_parent' => true,
                                                                'room_number' => $originalParentItem->room_number,
                                                                'meals' => $originalParentItem->meals,
                                                                'price' => $originalParentItem->price,
                                                                'first_name' => $originalParentItem->first_name,
                                                                'last_name' => $originalParentItem->last_name,
                                                                'phone' => $originalParentItem->phone,
                                                                'telegram' => $originalParentItem->telegram,
                                                                'advance' => $originalParentItem->advance ?? 0,
                                                                'balance' => $originalParentItem->balance ?? 0,
                                                                'has_transfer_there' => true,
                                                                'has_transfer_back' => true,
                                                                'info' => $originalParentItem->info,
                                                                'sort_order' => $originalParentItem->sort_order,
                                                            ]);
                                                            
                                                            // Зберігаємо зв'язок для копіювання місць
                                                            $parentItemsMap[$originalParentItem->id] = $crmParentItem->id;
                                                        }
                                                        
                                                        // Визначаємо кількість місць у номері для цієї категорії
                                                        $room = $originalCategory->room;
                                                        $placesPerRoom = 0;
                                                        
                                                        if ($room) {
                                                            // Розраховуємо кількість місць з bed_types
                                                            $bedTypes = is_array($room->bed_types) ? $room->bed_types : json_decode($room->bed_types ?? '{}', true);
                                                            if (is_array($bedTypes)) {
                                                                $singleBeds = (int)($bedTypes['single'] ?? 0);
                                                                $doubleBeds = (int)($bedTypes['double'] ?? 0);
                                                                // 1 односпальне = 1 місце, 1 двоспальне = 2 місця
                                                                $placesPerRoom = $singleBeds + ($doubleBeds * 2);
                                                            }
                                                        }
                                                        
                                                        // Отримуємо ціну за місце для цього типу номера
                                                        $pricePerPlace = $priceMap[$placesPerRoom] ?? 0;
                                                        
                                                        // Якщо ціна не знайдена, використовуємо 0 (користувач може ввести вручну)
                                                        if ($pricePerPlace == 0 && $placesPerRoom > 0) {
                                                            // Можна додати попередження, але поки що просто використовуємо 0
                                                        }
                                                        
                                                        // Тепер копіюємо тільки місця (is_parent = false), які мають parent_id (належать до батьківського номера)
                                                        foreach ($originalCategory->items->where('is_parent', false)->whereNotNull('parent_id') as $originalPlace) {
                                                            $crmParentId = $parentItemsMap[$originalPlace->parent_id] ?? null;
                                                            if ($crmParentId) {
                                                                // Розраховуємо баланс на основі нової ціни (ціна вже округлена в мапі)
                                                                $newPrice = $pricePerPlace; // Вже округлена в мапі
                                                                $advance = (float)($originalPlace->advance ?? 0);
                                                                $balance = round($newPrice - $advance); // Округлюємо баланс
                                                                
                                                                \App\Models\CrmItem::create([
                                                                    'crm_category_id' => $crmCategory->id,
                                                                    'parent_id' => $crmParentId,
                                                                    'place_number' => $originalPlace->place_number,
                                                                    'is_parent' => false,
                                                                    'room_number' => $originalPlace->room_number,
                                                                    'meals' => $originalPlace->meals,
                                                                    'price' => $newPrice, // Використовуємо ціну з калькулятора
                                                                    'first_name' => $originalPlace->first_name,
                                                                    'last_name' => $originalPlace->last_name,
                                                                    'phone' => $originalPlace->phone,
                                                                    'telegram' => $originalPlace->telegram,
                                                                    'advance' => $advance,
                                                                    'balance' => $balance, // Перераховуємо баланс
                                                                    'has_transfer_there' => true,
                                                                    'has_transfer_back' => true,
                                                                    'info' => $originalPlace->info,
                                                                    'sort_order' => $originalPlace->sort_order,
                                                                ]);
                                                            }
                                                        }
                                                    }
                                                    
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('CRM згенеровано успішно')
                                                        ->body('Схема готелю скопійована з усіма номерами')
                                                        ->success()
                                                        ->send();
                                                    
                                                    // Оновлюємо record
                                                    $record->refresh();
                                                    
                                                    // Перенаправляємо на ту ж сторінку для оновлення
                                                    redirect(\App\Filament\Resources\TourResource::getUrl('edit', ['record' => $record]));
                                                }),
                                            
                                            Forms\Components\Actions\Action::make('delete_crm')
                                                ->label('Видалити СРМ')
                                                ->icon('heroicon-o-trash')
                                                ->color('danger')
                                                ->visible(function ($get, $record) {
                                                    if (!$record || !isset($record->id)) {
                                                        return false;
                                                    }
                                                    
                                                    // Показуємо тільки якщо CRM таблиця існує
                                                    try {
                                                        return \App\Models\CrmTable::where('tour_id', $record->id)->exists();
                                                    } catch (\Exception $e) {
                                                        return false;
                                                    }
                                                })
                                                ->requiresConfirmation()
                                                ->modalHeading('Видалити CRM таблицю')
                                                ->modalDescription('Ви впевнені, що хочете видалити CRM таблицю? Ця дія видалить всі дані та не може бути скасована.')
                                                ->modalSubmitActionLabel('Видалити')
                                                ->modalCancelActionLabel('Скасувати')
                                                ->action(function ($get, $set, $record) {
                                                    try {
                                                        $crmTable = \App\Models\CrmTable::where('tour_id', $record->id)->first();
                                                        
                                                        if (!$crmTable) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Помилка')
                                                                ->body('CRM таблиця не знайдена')
                                                                ->danger()
                                                                ->send();
                                                            return;
                                                        }
                                                        
                                                        // Видаляємо CRM таблицю (каскадне видалення через foreign keys видалить категорії та елементи)
                                                        $crmTable->delete();
                                                        
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('CRM таблицю видалено')
                                                            ->body('Всі дані CRM таблиці видалено успішно')
                                                            ->success()
                                                            ->send();
                                                        
                                                        // Оновлюємо record
                                                        $record->refresh();
                                                        
                                                        // Перенаправляємо на ту ж сторінку для оновлення
                                                        redirect(\App\Filament\Resources\TourResource::getUrl('edit', ['record' => $record]));
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Помилка видалення')
                                                            ->body('Не вдалося видалити CRM таблицю: ' . $e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ])
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\View::make('filament.forms.components.crm-table')
                                            ->columnSpanFull()
                                            ->visible(function ($get, $record) {
                                                try {
                                                    if (!$record || !isset($record->id)) {
                                                        return false;
                                                    }
                                                    return \App\Models\CrmTable::where('tour_id', $record->id)->exists();
                                                } catch (\Exception $e) {
                                                    return false;
                                                }
                                            }),
                                    ])
                                    ->columnSpanFull(),
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
