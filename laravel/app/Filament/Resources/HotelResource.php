<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotelResource\Pages;
use App\Filament\Resources\HotelResource\RelationManagers;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\MediaFile;
use App\Models\HotelSchemeCategory;
use App\Models\HotelSchemeItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationLabel = 'Готелі';
    
    protected static ?string $navigationGroup = 'Контент';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('HotelTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Інформація про готель')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Назва готелю')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Назва готелю')
                                    ->helperText('Введіть назву готелю')
                                    ->columnSpanFull(),
                                
                                Forms\Components\Section::make('Кімнати готелю')
                                    ->schema([
                                        Forms\Components\Repeater::make('rooms')
                                            ->relationship('rooms')
                                            ->label('Кімнати')
                                            ->schema([
                                                Forms\Components\TextInput::make('room_type')
                                                    ->label('Тип номера')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Наприклад: Стандарт, Люкс, Сюїт')
                                                    ->helperText('Вкажіть назву типу номера')
                                                    ->afterStateHydrated(function ($component, $state, $record) {
                                                        if (empty($state) && $record) {
                                                            $component->state($record->room_type ?? '');
                                                        }
                                                    })
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('bed_single_count')
                                                            ->label('Односпальних ліжок')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->afterStateHydrated(function ($component, $state, $record) {
                                                                if ($record && $record->bed_types) {
                                                                    $bedTypes = is_array($record->bed_types) ? $record->bed_types : json_decode($record->bed_types ?? '{}', true);
                                                                    if (is_array($bedTypes)) {
                                                                        $component->state($bedTypes['single'] ?? 0);
                                                                    }
                                                                }
                                                            })
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $single = (int)($state ?? 0);
                                                                $double = (int)($get('bed_double_count') ?? 0);
                                                                $set('bed_types', ['single' => $single, 'double' => $double]);
                                                            }),
                                                        
                                                        Forms\Components\TextInput::make('bed_double_count')
                                                            ->label('Двоспальних ліжок')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->default(0)
                                                            ->reactive()
                                                            ->afterStateHydrated(function ($component, $state, $record) {
                                                                if ($record && $record->bed_types) {
                                                                    $bedTypes = is_array($record->bed_types) ? $record->bed_types : json_decode($record->bed_types ?? '{}', true);
                                                                    if (is_array($bedTypes)) {
                                                                        $component->state($bedTypes['double'] ?? 0);
                                                                    }
                                                                }
                                                            })
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $single = (int)($get('bed_single_count') ?? 0);
                                                                $double = (int)($state ?? 0);
                                                                $set('bed_types', ['single' => $single, 'double' => $double]);
                                                            }),
                                                    ])
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\Hidden::make('bed_types')
                                                    ->default(['single' => 0, 'double' => 0])
                                                    ->dehydrated()
                                                    ->afterStateHydrated(function ($component, $state, $record) {
                                                        if ($record && $record->bed_types) {
                                                            $bedTypes = is_array($record->bed_types) ? $record->bed_types : json_decode($record->bed_types, true);
                                                            $component->state([
                                                                'single' => $bedTypes['single'] ?? 0,
                                                                'double' => $bedTypes['double'] ?? 0,
                                                            ]);
                                                        }
                                                    })
                                                    ->reactive(),
                                                
                                                Forms\Components\Select::make('rooms_count')
                                                    ->label('Кількість кімнат у номері')
                                                    ->options([
                                                        1 => '1 кімната',
                                                        2 => '2 кімнати',
                                                    ])
                                                    ->required()
                                                    ->default(1)
                                                    ->afterStateHydrated(function ($component, $state, $record) {
                                                        if (empty($state) && $record) {
                                                            $component->state($record->rooms_count ?? 1);
                                                        }
                                                    })
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Кількість номерів')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->helperText('Скільки номерів цього типу є в готелі (наприклад: 6)')
                                                    ->afterStateHydrated(function ($component, $state, $record) {
                                                        if (empty($state) && $record) {
                                                            $component->state($record->quantity ?? 1);
                                                        }
                                                    })
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\Toggle::make('is_hostel')
                                                    ->label('Хостел')
                                                    ->helperText('Номер можна продавати як окреме ліжко')
                                                    ->default(false)
                                                    ->columnSpan(1),
                                                
                                                Forms\Components\Hidden::make('id')
                                                    ->dehydrated()
                                                    ->afterStateHydrated(function ($component, $state, $record) {
                                                        if ($record && $record->id) {
                                                            $component->state($record->id);
                                                        }
                                                    }),
                                                
                                                Forms\Components\View::make('filament.forms.components.save-room-button')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->itemLabel(function (array $state): ?string {
                                                $roomType = $state['room_type'] ?? 'Новий номер';
                                                $quantity = (int)($state['quantity'] ?? 1);
                                                
                                                // Рахуємо кількість місць в одному номері
                                                $bedTypes = $state['bed_types'] ?? [];
                                                if (is_string($bedTypes)) {
                                                    $bedTypes = json_decode($bedTypes, true) ?? [];
                                                }
                                                
                                                $singleBeds = (int)($bedTypes['single'] ?? $state['bed_single_count'] ?? 0);
                                                $doubleBeds = (int)($bedTypes['double'] ?? $state['bed_double_count'] ?? 0);
                                                
                                                // 1 односпальне = 1 місце, 1 двоспальне = 2 місця
                                                $placesPerRoom = $singleBeds + ($doubleBeds * 2);
                                                
                                                // Загальна кількість місць = місць в номері × кількість номерів
                                                $totalPlaces = $placesPerRoom * $quantity;
                                                
                                                return $roomType . ' - ' . $quantity . ' шт. (' . $totalPlaces . ' місць)';
                                            })
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->collapsed()
                                            ->collapseAllAction(null)
                                            ->expandAllAction(null)
                                            ->addActionLabel('Додати кімнату')
                                            ->reorderable()
                                            ->deletable()
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $get): array {
                                                // Встановлюємо hotel_id з форми
                                                $hotelId = null;
                                                
                                                // Спробуємо отримати через $get, але обережно
                                                try {
                                                    // Не викликаємо $get напряму з шляхом, який може повернути рядок
                                                    // Замість цього спробуємо отримати через Livewire
                                                    $livewire = \Livewire\Livewire::current();
                                                    if ($livewire) {
                                                        if (method_exists($livewire, 'getRecord')) {
                                                            try {
                                                                $record = $livewire->getRecord();
                                                                if ($record && is_object($record) && isset($record->id)) {
                                                                    $hotelId = $record->id;
                                                                }
                                                            } catch (\Exception $e) {
                                                                // Ігноруємо помилку
                                                            }
                                                        } elseif (property_exists($livewire, 'record') && $livewire->record) {
                                                            $record = $livewire->record;
                                                            if (is_object($record) && isset($record->id)) {
                                                                $hotelId = $record->id;
                                                            }
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    // Ігноруємо помилку - при створенні нового готелю hotel_id буде встановлено в afterCreate
                                                }
                                                
                                                // Якщо отримали hotel_id, встановлюємо його
                                                // Якщо ні - при створенні нового готелю він буде встановлений в afterCreate
                                                if ($hotelId) {
                                                    $data['hotel_id'] = $hotelId;
                                                }
                                                
                                                // Формуємо bed_types
                                                $single = (int)($data['bed_single_count'] ?? $data['bed_types']['single'] ?? 0);
                                                $double = (int)($data['bed_double_count'] ?? $data['bed_types']['double'] ?? 0);
                                                $data['bed_types'] = ['single' => $single, 'double' => $double];
                                                
                                                unset($data['bed_single_count'], $data['bed_double_count']);
                                                return $data;
                                            })
                                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record): array {
                                                // Формуємо bed_types
                                                $single = (int)($data['bed_single_count'] ?? $data['bed_types']['single'] ?? 0);
                                                $double = (int)($data['bed_double_count'] ?? $data['bed_types']['double'] ?? 0);
                                                $data['bed_types'] = ['single' => $single, 'double' => $double];
                                                
                                                unset($data['bed_single_count'], $data['bed_double_count']);
                                                return $data;
                                            }),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->columnSpanFull(),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Схема готелю')
                            ->schema([
                                Forms\Components\View::make('filament.forms.components.hotel-scheme-table')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва готелю')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Кількість номерів')
                    ->getStateUsing(function ($record) {
                        // Рахуємо загальну кількість номерів: сума quantity всіх типів кімнат
                        return $record->rooms()->sum('quantity') ?? 0;
                    })
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_places')
                    ->label('Кількість місць')
                    ->getStateUsing(function ($record) {
                        // Рахуємо загальну кількість місць у готелі
                        $totalPlaces = 0;
                        
                        foreach ($record->rooms as $room) {
                            // Кількість місць в одному номері
                            $bedTypes = is_array($room->bed_types) ? $room->bed_types : json_decode($room->bed_types ?? '{}', true);
                            if (!is_array($bedTypes)) {
                                $bedTypes = [];
                            }
                            
                            $singleBeds = (int)($bedTypes['single'] ?? 0);
                            $doubleBeds = (int)($bedTypes['double'] ?? 0);
                            
                            // 1 односпальне = 1 місце, 1 двоспальне = 2 місця
                            $placesPerRoom = $singleBeds + ($doubleBeds * 2);
                            
                            // Загальна кількість місць для цього типу = місць в номері × кількість номерів
                            $quantity = $room->quantity ?? 1;
                            $totalPlaces += $placesPerRoom * $quantity;
                        }
                        
                        return $totalPlaces;
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // Кімнати тепер у вкладці "Інформація про готель"
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
