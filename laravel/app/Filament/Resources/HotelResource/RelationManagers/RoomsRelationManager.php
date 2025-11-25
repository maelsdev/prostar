<?php

namespace App\Filament\Resources\HotelResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomsRelationManager extends RelationManager
{
    protected static string $relationship = 'rooms';

    protected static ?string $title = 'Кімнати готелю';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('room_type')
                    ->label('Тип номера')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Наприклад: Стандарт, Люкс, Сюїт')
                    ->helperText('Вкажіть назву типу номера')
                    ->dehydrated()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        // Завантажуємо значення з запису, якщо поле порожнє
                        if (empty($state) && $record) {
                            $component->state($record->room_type ?? '');
                        }
                    }),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('bed_single_count')
                            ->label('Кількість односпальних ліжок')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Вкажіть кількість односпальних ліжок (0 або більше)')
                            ->reactive()
                            ->dehydrated()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $single = (int)($state ?? 0);
                                $double = (int)($get('bed_double_count') ?? 0);
                                $bedTypes = [
                                    'single' => $single,
                                    'double' => $double,
                                ];
                                $set('bed_types', $bedTypes);
                            })
                            ->rules([
                                function ($get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $single = (int)($value ?? 0);
                                        $double = (int)($get('bed_double_count') ?? 0);
                                        if ($single === 0 && $double === 0) {
                                            $fail('Вкажіть хоча б одну кількість ліжок (односпальних або двоспальних).');
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\TextInput::make('bed_double_count')
                            ->label('Кількість двоспальних ліжок')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Вкажіть кількість двоспальних ліжок (0 або більше)')
                            ->reactive()
                            ->dehydrated()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $single = (int)($get('bed_single_count') ?? 0);
                                $double = (int)($state ?? 0);
                                $bedTypes = [
                                    'single' => $single,
                                    'double' => $double,
                                ];
                                $set('bed_types', $bedTypes);
                            })
                            ->rules([
                                function ($get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $single = (int)($get('bed_single_count') ?? 0);
                                        $double = (int)($value ?? 0);
                                        if ($single === 0 && $double === 0) {
                                            $fail('Вкажіть хоча б одну кількість ліжок (односпальних або двоспальних).');
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->bed_types) {
                            $bedTypes = is_array($record->bed_types) ? $record->bed_types : json_decode($record->bed_types, true);
                            $component->getChildComponentContainer()
                                ->fill([
                                    'bed_single_count' => $bedTypes['single'] ?? 0,
                                    'bed_double_count' => $bedTypes['double'] ?? 0,
                                ]);
                        }
                    }),
                
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
                
                Forms\Components\Placeholder::make('bed_types_info')
                    ->label('')
                    ->content(function ($get) {
                        $single = (int)($get('bed_single_count') ?? 0);
                        $double = (int)($get('bed_double_count') ?? 0);
                        if ($single === 0 && $double === 0) {
                            return new \Illuminate\Support\HtmlString(
                                '<p class="text-sm text-amber-600 dark:text-amber-400">⚠️ Вкажіть хоча б одну кількість ліжок</p>'
                            );
                        }
                        return new \Illuminate\Support\HtmlString(
                            '<p class="text-sm text-gray-600 dark:text-gray-400">✓ Всього ліжок: ' . ($single + $double) . '</p>'
                        );
                    })
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('rooms_count')
                    ->label('Кількість кімнат')
                    ->options([
                        1 => '1 кімната',
                        2 => '2 кімнати',
                    ])
                    ->required()
                    ->default(1)
                    ->helperText('Оберіть кількість кімнат у номері')
                    ->dehydrated()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        // Завантажуємо значення з запису, якщо поле порожнє
                        if (empty($state) && $record) {
                            $component->state($record->rooms_count ?? 1);
                        }
                    }),
                
                Forms\Components\Toggle::make('is_hostel')
                    ->label('Хостел')
                    ->helperText('Якщо увімкнено, номер можна продавати як окреме ліжко')
                    ->default(false)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('room_type')
            ->columns([
                Tables\Columns\TextColumn::make('room_type')
                    ->label('Тип номера')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bed_types_display')
                    ->label('Ліжка')
                    ->getStateUsing(function ($record) {
                        $bedTypes = is_array($record->bed_types) ? $record->bed_types : json_decode($record->bed_types, true);
                        $parts = [];
                        if (isset($bedTypes['single']) && $bedTypes['single'] > 0) {
                            $parts[] = $bedTypes['single'] . ' односпальн' . ($bedTypes['single'] > 1 ? 'их' : 'е');
                        }
                        if (isset($bedTypes['double']) && $bedTypes['double'] > 0) {
                            $parts[] = $bedTypes['double'] . ' двоспальн' . ($bedTypes['double'] > 1 ? 'их' : 'е');
                        }
                        return !empty($parts) ? implode(', ', $parts) : 'не вказано';
                    }),
                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Кімнат')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_hostel')
                    ->label('Хостел')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Встановлюємо hotel_id з owner record
                        $data['hotel_id'] = $this->getOwnerRecord()->id;
                        
                        // Переконуємося, що bed_types правильно сформовано
                        $single = (int)($data['bed_single_count'] ?? $data['bed_types']['single'] ?? 0);
                        $double = (int)($data['bed_double_count'] ?? $data['bed_types']['double'] ?? 0);
                        
                        $data['bed_types'] = [
                            'single' => $single,
                            'double' => $double,
                        ];
                        
                        // Видаляємо тимчасові поля
                        unset($data['bed_single_count'], $data['bed_double_count']);
                        return $data;
                    })
                    ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                        // Додаткова перевірка перед створенням
                        if (!isset($data['bed_types']) || !is_array($data['bed_types'])) {
                            $data['bed_types'] = [
                                'single' => (int)($data['bed_single_count'] ?? 0),
                                'double' => (int)($data['bed_double_count'] ?? 0),
                            ];
                        }
                        
                        // Переконуємося, що hotel_id встановлено
                        if (!isset($data['hotel_id'])) {
                            $data['hotel_id'] = $this->getOwnerRecord()->id;
                        }
                        
                        unset($data['bed_single_count'], $data['bed_double_count']);
                        return $model::create($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        // Завантажуємо дані з запису, якщо вони не передані
                        if ($record) {
                            $data['room_type'] = $data['room_type'] ?? $record->room_type ?? '';
                            $data['rooms_count'] = $data['rooms_count'] ?? $record->rooms_count ?? 1;
                            
                            // Завантажуємо bed_types
                            if (!isset($data['bed_single_count']) && !isset($data['bed_double_count'])) {
                                if ($record->bed_types) {
                                    $bedTypes = is_array($record->bed_types) ? $record->bed_types : json_decode($record->bed_types, true);
                                    $data['bed_single_count'] = $bedTypes['single'] ?? 0;
                                    $data['bed_double_count'] = $bedTypes['double'] ?? 0;
                                } else {
                                    $data['bed_single_count'] = 0;
                                    $data['bed_double_count'] = 0;
                                }
                            }
                        }
                        
                        return $data;
                    })
                    ->using(function ($record, array $data): \Illuminate\Database\Eloquent\Model {
                        // Переконуємося, що bed_types правильно сформовано перед збереженням
                        $single = (int)($data['bed_single_count'] ?? $data['bed_types']['single'] ?? 0);
                        $double = (int)($data['bed_double_count'] ?? $data['bed_types']['double'] ?? 0);
                        
                        $data['bed_types'] = [
                            'single' => $single,
                            'double' => $double,
                        ];
                        
                        // Видаляємо тимчасові поля
                        unset($data['bed_single_count'], $data['bed_double_count']);
                        
                        $record->update($data);
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('room_type', 'asc');
    }
}
