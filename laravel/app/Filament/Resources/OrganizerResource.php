<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizerResource\Pages;
use App\Filament\Resources\OrganizerResource\RelationManagers;
use App\Models\Organizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrganizerResource extends Resource
{
    protected static ?string $model = Organizer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Організатори';
    
    protected static ?string $navigationGroup = 'Контент';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Ім\'я організатора')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Наприклад: Тарас')
                    ->helperText('Введіть ім\'я організатора'),
                
                Forms\Components\TextInput::make('phone')
                    ->label('Номер телефону')
                    ->tel()
                    ->maxLength(255)
                    ->placeholder('+38(098) 12-12-011')
                    ->helperText('Номер телефону організатора'),
                
                Forms\Components\TextInput::make('telegram_username')
                    ->label('Нік в Telegram')
                    ->maxLength(255)
                    ->placeholder('username')
                    ->helperText('Нікнейм в Telegram (без @)')
                    ->prefix('@')
                    ->formatStateUsing(fn ($state) => $state ? ltrim($state, '@') : null)
                    ->dehydrateStateUsing(fn ($state) => $state ? ltrim($state, '@') : null),
                
                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок сортування')
                    ->numeric()
                    ->default(0)
                    ->helperText('Чим менше число, тим вище в списку'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ім\'я')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('telegram_username')
                    ->label('Telegram')
                    ->formatStateUsing(fn ($state) => $state ? '@' . $state : '-')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
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
            ->defaultSort('sort_order', 'asc');
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
            'index' => Pages\ListOrganizers::route('/'),
            'create' => Pages\CreateOrganizer::route('/create'),
            'edit' => Pages\EditOrganizer::route('/{record}/edit'),
        ];
    }
}
