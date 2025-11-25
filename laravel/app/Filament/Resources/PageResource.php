<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Сторінки';
    
    protected static ?string $navigationGroup = 'Контент';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('PageTabs')
                    ->tabs([
                        // Вкладка 1: Основна інформація
                        Forms\Components\Tabs\Tab::make('Основна інформація')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Ідентифікація сторінки')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Назва сторінки')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Головна')
                                            ->helperText('Внутрішня назва сторінки для адмін-панелі'),
                                            
                                        Forms\Components\Toggle::make('edit_slug')
                                            ->label('Редагувати slug')
                                            ->helperText('Увімкніть, щоб дозволити редагування slug')
                                            ->default(false)
                                            ->dehydrated(false)
                                            ->live(),
                                            
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->helperText('Унікальний ідентифікатор сторінки (наприклад: home). Використовується для внутрішньої логіки.')
                                            ->rules(['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/'])
                                            ->validationMessages([
                                                'slug.regex' => 'Slug може містити тільки малі літери, цифри, дефіси та підкреслення',
                                            ])
                                            ->disabled(fn ($get) => !$get('edit_slug'))
                                            ->dehydrated(),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        // Вкладка 2: Hero секція
                        Forms\Components\Tabs\Tab::make('Hero секція')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Section::make('Заголовки')
                                    ->description('Основні заголовки hero-секції')
                                    ->schema([
                                        Forms\Components\TextInput::make('season')
                                            ->label('SEASON')
                                            ->placeholder('SEASON 2025-2026')
                                            ->maxLength(255)
                                            ->helperText('Текст сезону, що відображається над основним заголовком')
                                            ->columnSpan(1),
                                            
                                        Forms\Components\TextInput::make('h1')
                                            ->label('H1 заголовок')
                                            ->placeholder('ГІРСЬКОЛИЖНІ ТУРИ')
                                            ->maxLength(255)
                                            ->required()
                                            ->helperText('Основний заголовок сторінки (важливо для SEO)')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                                    
                                Forms\Components\Section::make('Опис')
                                    ->description('Текстовий опис у hero-секції')
                                    ->schema([
                                        Forms\Components\RichEditor::make('description')
                                            ->label('Опис')
                                            ->placeholder('Організація гірськолижних турів під ключ для новачків, професіоналів, та сімейного відпочинку')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'link',
                                                'bulletList',
                                                'orderedList',
                                            ])
                                            ->helperText('Опис компанії/послуг. Підтримує HTML форматування.')
                                            ->columnSpanFull(),
                                    ]),
                                    
                                Forms\Components\Section::make('Кнопка')
                                    ->description('Налаштування кнопки у hero-секції (ліва частина)')
                                    ->schema([
                                        Forms\Components\Group::make([
                                            Forms\Components\TextInput::make('button_text')
                                                ->label('Текст кнопки')
                                                ->placeholder('Обрати тур')
                                                ->maxLength(255)
                                                ->helperText('Текст на кнопці. Якщо не вказано, кнопка не відображатиметься.'),
                                                
                                            Forms\Components\TextInput::make('button_action')
                                                ->label('Дія кнопки (ID секції)')
                                                ->placeholder('#tours')
                                                ->maxLength(255)
                                                ->helperText('ID секції для скролу (наприклад: #tours). Кнопка робить плавний скрол до секції.')
                                                ->default('#tours'),
                                        ])
                                        ->columns(2),
                                    ]),
                            ]),
                        
                        // Вкладка 3: Видимість секцій
                        Forms\Components\Tabs\Tab::make('Видимість секцій')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Forms\Components\Section::make('Управління секціями після "Наступних турів"')
                                    ->description('Вмикайте або вимикайте відображення секцій на головній сторінці')
                                    ->schema([
                                        Forms\Components\Toggle::make('show_hotels_section')
                                            ->label('Показувати секцію "Рекомендовані готелі"')
                                            ->default(true)
                                            ->helperText('Секція з рекомендованими готелями Драгобрату'),
                                            
                                        Forms\Components\Toggle::make('show_activities_section')
                                            ->label('Показувати секцію "Чим зайнятися на курорті"')
                                            ->default(true)
                                            ->helperText('Секція з активностями та розвагами'),
                                            
                                        Forms\Components\Toggle::make('show_about_section_after_tours')
                                            ->label('Показувати секцію "Про нас" (після турів)')
                                            ->default(true)
                                            ->helperText('Друга секція "Про нас" після секції турів'),
                                            
                                        Forms\Components\Toggle::make('show_contact_section')
                                            ->label('Показувати секцію "Контакти та локації"')
                                            ->default(true)
                                            ->helperText('Секція з контактами та картою'),
                                    ])
                                    ->columns(1),
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
                    ->label('Назва')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('h1')
                    ->label('H1')
                    ->limit(50)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
