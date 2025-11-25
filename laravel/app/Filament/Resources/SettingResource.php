<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Налаштування';
    
    protected static ?string $modelLabel = 'Налаштування';
    
    protected static ?string $pluralModelLabel = 'Налаштування';
    
    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('SettingsTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Хедер')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\Section::make('Контактна інформація')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Номер телефону')
                                            ->placeholder('+38(098) 12-12-011')
                                            ->required()
                                            ->maxLength(255)
                                            ->rules([
                                                'required',
                                                'string',
                                                'max:255',
                                                'regex:/^[\+]?[0-9\s\(\)\-]{10,20}$/',
                                            ])
                                            ->validationMessages([
                                                'phone.required' => 'Номер телефону обов\'язковий',
                                                'phone.regex' => 'Номер телефону має бути у правильному форматі (наприклад: +38(098) 12-12-011)',
                                            ]),
                                            
                                        Forms\Components\TextInput::make('telegram_phone')
                                            ->label('Номер для Telegram')
                                            ->placeholder('380981212011')
                                            ->required()
                                            ->helperText('Номер без + та пробілів, тільки цифри')
                                            ->maxLength(20)
                                            ->rules([
                                                'required',
                                                'string',
                                                'max:20',
                                                'regex:/^[0-9]{10,15}$/',
                                            ])
                                            ->validationMessages([
                                                'telegram_phone.required' => 'Номер для Telegram обов\'язковий',
                                                'telegram_phone.regex' => 'Номер має містити тільки цифри (10-15 символів)',
                                            ]),
                                            
                                        Forms\Components\TextInput::make('whatsapp_phone')
                                            ->label('Номер для WhatsApp')
                                            ->placeholder('380981212011')
                                            ->required()
                                            ->helperText('Номер без + та пробілів, тільки цифри')
                                            ->maxLength(20)
                                            ->rules([
                                                'required',
                                                'string',
                                                'max:20',
                                                'regex:/^[0-9]{10,15}$/',
                                            ])
                                            ->validationMessages([
                                                'whatsapp_phone.required' => 'Номер для WhatsApp обов\'язковий',
                                                'whatsapp_phone.regex' => 'Номер має містити тільки цифри (10-15 символів)',
                                            ]),
                                            
                                        Forms\Components\TextInput::make('telegram_username')
                                            ->label('Telegram username')
                                            ->placeholder('pro_s_tar')
                                            ->prefix('@')
                                            ->required()
                                            ->maxLength(32)
                                            ->rules([
                                                'required',
                                                'string',
                                                'max:32',
                                                'regex:/^[a-zA-Z0-9_]{5,32}$/',
                                            ])
                                            ->validationMessages([
                                                'telegram_username.required' => 'Telegram username обов\'язковий',
                                                'telegram_username.regex' => 'Username має містити тільки латинські літери, цифри та підкреслення (5-32 символи)',
                                            ]),
                                    ])
                                    ->columns(2),
                                    
                                Forms\Components\Section::make('Telegram бот для бронювань')
                                    ->description('Налаштування для відправки заявок на бронювання в Telegram')
                                    ->schema([
                                        Forms\Components\TextInput::make('telegram_bot_token')
                                            ->label('Токен Telegram бота')
                                            ->placeholder('1234567890:ABCdefGHIjklMNOpqrsTUVwxyz')
                                            ->maxLength(255)
                                            ->helperText('Отримайте токен у @BotFather в Telegram. Створіть бота командою /newbot')
                                            ->password()
                                            ->revealable()
                                            ->rules([
                                                'nullable',
                                                'string',
                                                'max:255',
                                            ]),
                                            
                                        Forms\Components\TextInput::make('telegram_chat_id')
                                            ->label('Chat ID групи/чату')
                                            ->placeholder('280607236 або -1001234567890')
                                            ->maxLength(255)
                                            ->helperText(new \Illuminate\Support\HtmlString(
                                                '<div style="font-size: 12px; margin-top: 4px;">
                                                    <strong>Як отримати Chat ID групи:</strong><br>
                                                    1. Додайте бота в групу як адміністратора<br>
                                                    2. Надішліть в групу повідомлення<br>
                                                    3. Відкрийте: https://api.telegram.org/botВАШ_ТОКЕН/getUpdates<br>
                                                    4. Знайдіть "chat":{"id":-100...} - це ваш Chat ID<br>
                                                    <br>
                                                    <strong>Для особистого чату:</strong><br>
                                                    - Надішліть боту /start<br>
                                                    - Отримайте Chat ID у @userinfobot<br>
                                                    - Chat ID буде позитивним числом
                                                </div>'
                                            ))
                                            ->rules([
                                                'nullable',
                                                'string',
                                                'max:255',
                                            ]),
                                    ])
                                    ->columns(1),
                                    
                                Forms\Components\Section::make('Логотип')
                                    ->schema([
                                        Forms\Components\TextInput::make('logo_text')
                                            ->label('Текстовий логотип')
                                            ->placeholder('PROSTAR | RADUGAUA | SNІGOWEEK')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText('Текст, який відображається як логотип у хедері')
                                            ->rules([
                                                'required',
                                                'string',
                                                'max:255',
                                            ])
                                            ->validationMessages([
                                                'logo_text.required' => 'Текстовий логотип обов\'язковий',
                                            ]),
                                            
                                        Forms\Components\FileUpload::make('logo_image')
                                            ->label('Зображення логотипу')
                                            ->image()
                                            ->disk('public')
                                            ->directory('logos')
                                            ->visibility('public')
                                            ->maxSize(2048) // 2MB
                                            ->helperText('Завантажте зображення логотипу (опціонально). Якщо завантажено, воно відображатиметься замість текстового логотипу.')
                                            ->rules([
                                                'nullable',
                                                'image',
                                                'max:2048',
                                            ])
                                            ->validationMessages([
                                                'logo_image.image' => 'Файл має бути зображенням',
                                                'logo_image.max' => 'Розмір файлу не повинен перевищувати 2MB',
                                            ]),
                                    ])
                                    ->columns(2),
                            ]),
                            
                        Forms\Components\Tabs\Tab::make('Інтерфейс')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Forms\Components\Section::make('Налаштування відображення')
                                    ->schema([
                                        Forms\Components\Toggle::make('show_language_switcher')
                                            ->label('Показати перемикач мов')
                                            ->default(true)
                                            ->helperText('Показувати чи приховати перемикач мов у хедері'),
                                    ]),
                            ]),
                            
                        Forms\Components\Tabs\Tab::make('Погода')
                            ->icon('heroicon-o-cloud')
                            ->schema([
                                Forms\Components\Section::make('Налаштування погоди')
                                    ->description('Погода відображається тільки для Драгобрату на сьогоднішній день')
                                    ->schema([
                                        Forms\Components\TextInput::make('weatherapi_key')
                                            ->label('WeatherAPI.com API ключ')
                                            ->placeholder('Вставте ваш API ключ')
                                            ->helperText('Отримайте безкоштовний ключ на https://www.weatherapi.com/signup.aspx (1,000,000 запитів/місяць безкоштовно). Якщо не вказано, погода не відображатиметься.')
                                            ->maxLength(255)
                                            ->password()
                                            ->revealable()
                                            ->rules([
                                                'nullable',
                                                'string',
                                                'max:255',
                                            ])
                                            ->validationMessages([
                                                'weatherapi_key.max' => 'API ключ занадто довгий',
                                            ]),
                                    ]),
                                    
                                Forms\Components\Section::make('Статистика API')
                                    ->description('Інформація про використання WeatherAPI.com')
                                    ->schema([
                                        Forms\Components\TextInput::make('weather_requests_remaining')
                                            ->label('Залишилось запитів')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 0, ',', ' ') : 'Не визначено')
                                            ->helperText('Кількість залишкових запитів до API за місяць'),
                                            
                                        Forms\Components\TextInput::make('weather_last_updated')
                                            ->label('Останнє оновлення')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->formatStateUsing(function ($state, $record) {
                                                if (!$state && !$record) {
                                                    return 'Ніколи';
                                                }
                                                
                                                // Якщо є запис, отримуємо значення з нього
                                                if ($record instanceof Setting) {
                                                    $value = $record->weather_last_updated;
                                                    if ($value instanceof \Carbon\Carbon) {
                                                        return $value->setTimezone(config('app.timezone', 'Europe/Kiev'))->format('d.m.Y H:i:s');
                                                    }
                                                    if (is_string($value) && $value) {
                                                        return \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone', 'Europe/Kiev'))->format('d.m.Y H:i:s');
                                                    }
                                                }
                                                
                                                // Якщо це Carbon instance
                                                if ($state instanceof \Carbon\Carbon) {
                                                    return $state->setTimezone(config('app.timezone', 'Europe/Kiev'))->format('d.m.Y H:i:s');
                                                }
                                                
                                                // Якщо це рядок, конвертуємо в Carbon
                                                if (is_string($state) && $state) {
                                                    try {
                                                        return \Carbon\Carbon::parse($state)->setTimezone(config('app.timezone', 'Europe/Kiev'))->format('d.m.Y H:i:s');
                                                    } catch (\Exception $e) {
                                                        return $state;
                                                    }
                                                }
                                                
                                                // Якщо це DateTime, конвертуємо
                                                if ($state instanceof \DateTime) {
                                                    return \Carbon\Carbon::instance($state)->setTimezone(config('app.timezone', 'Europe/Kiev'))->format('d.m.Y H:i:s');
                                                }
                                                
                                                return 'Ніколи';
                                            })
                                            ->helperText('Час останнього успішного запиту до API'),
                                            
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('forceUpdateWeather')
                                                ->label('Оновити погоду зараз')
                                                ->icon('heroicon-o-arrow-path')
                                                ->color('success')
                                                ->requiresConfirmation()
                                                ->modalHeading('Примусове оновлення погоди')
                                                ->modalDescription('Це оновить дані погоди та статистику API. Продовжити?')
                                                ->modalSubmitActionLabel('Оновити')
                                                ->action('forceUpdateWeather'),
                                        ]),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(false),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\IconColumn::make('show_language_switcher')
                    ->label('Перемикач мов')
                    ->boolean(),
                Tables\Columns\TextColumn::make('default_weather_resort')
                    ->label('Курорт за замовчуванням')
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
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
            'index' => Pages\EditSettings::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        // Забороняємо створення нових записів
        return false;
    }
    
    public static function canDelete($record): bool
    {
        // Забороняємо видалення
        return false;
    }
}
