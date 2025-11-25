<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationLabel = 'Профіль';
    
    protected static ?string $title = 'Мій профіль';
    
    protected static string $view = 'filament.pages.profile';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill(auth()->user()->toArray());
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Персональна інформація')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Ім\'я')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('name'),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->autocomplete('email'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Зміна пароля')
                    ->description('Залиште порожнім, якщо не хочете змінювати пароль')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Поточний пароль')
                            ->password()
                            ->required(fn ($get) => !empty($get('password')))
                            ->currentPassword()
                            ->dehydrated(false),
                            
                        Forms\Components\TextInput::make('password')
                            ->label('Новий пароль')
                            ->password()
                            ->minLength(8)
                            ->rules([Password::defaults()])
                            ->dehydrated(false)
                            ->revealable(),
                            
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Підтвердження нового пароля')
                            ->password()
                            ->same('password')
                            ->required(fn ($get) => !empty($get('password')))
                            ->dehydrated(false)
                            ->revealable(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->model(auth()->user());
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        $user = auth()->user();
        
        // Оновити ім'я та email
        $user->name = $data['name'];
        $user->email = $data['email'];
        
        // Оновити пароль, якщо вказано
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        
        $user->save();
        
        Notification::make()
            ->title('Профіль успішно оновлено')
            ->success()
            ->send();
    }
    
    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Зберегти зміни')
                ->submit('save'),
        ];
    }
}
