<?php

namespace App\Filament\Pages;

use App\Models\UserInfo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Html;
use Illuminate\Support\Facades\Auth;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.edit-profile';
    protected static ?string $navigationLabel = 'Edit Profile';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::UserCircle;
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $avatar = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Section::make('Profile Information')
                            ->extraAttributes(['class' => 'h-full'])
                            ->schema([
                                FileUpload::make('avatar')
                                    ->label('Profile Picture')
                                    ->image()
                                    ->directory('avatars')
                                    ->disk('public')
                                    ->maxSize(5120)
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->avatar()
                                    ->imageAspectRatio('1:1')
                                    ->alignCenter(true)
                                    ->columnSpanFull(),
                                Grid::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->disabled()
                                            ->default(Auth::user()->email)
                                            ->dehydrated(false),
                                ]),
                            ]),
                        Section::make('Update Password')
                            ->extraAttributes(['class' => 'h-full'])
                            ->schema([
                                TextInput::make('password')
                                    ->label('New Password')
                                    ->password(),
                                TextInput::make('password_confirmation')
                                    ->label('Confirm New Password')
                                    ->password()
                            ]),
                    ])
                ->extraAttributes(['class' => 'items-stretch'])
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Update user name
        $user->update(['name' => $data['name'], 'avatar' => $data['avatar']]);

        // Update password if provided
        if (!empty($data['password'])) {
            if ($data['password'] !== $data['password_confirmation']) {
                Notification::make()
                    ->title('Password confirmation does not match!')
                    ->danger()
                    ->send();
                return;
            }
            $user->update(['password' => bcrypt($data['password'])]);
        }

        Notification::make()
            ->title('Profile updated successfully!')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Profile')
                ->action('save')
                ->icon('heroicon-o-check'),
        ];
    }
}