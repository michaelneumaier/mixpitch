<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class Settings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-m-cog';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => config('app.name'),
            'site_description' => 'MixPitch - Music Collaboration Platform',
            'maintenance_mode' => false,
            'max_upload_size' => '50',
            'default_user_role' => 'user',
            'registration_enabled' => true,
            'contact_email' => 'admin@example.com',
            'social_links' => [
                'twitter' => 'https://twitter.com/mixpitch',
                'instagram' => 'https://instagram.com/mixpitch',
                'facebook' => 'https://facebook.com/mixpitch',
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General Settings')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->required(),

                        Textarea::make('site_description')
                            ->label('Site Description')
                            ->rows(3),

                        Toggle::make('maintenance_mode')
                            ->label('Maintenance Mode')
                            ->helperText('When enabled, the site will show a maintenance page to visitors.'),

                        FileUpload::make('site_logo')
                            ->label('Site Logo')
                            ->image()
                            ->directory('site-assets')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('50'),
                    ]),

                Section::make('Users & Registration')
                    ->schema([
                        Toggle::make('registration_enabled')
                            ->label('Enable Registration')
                            ->helperText('Allow new users to register on the site.'),

                        Select::make('default_user_role')
                            ->label('Default Role for New Users')
                            ->options([
                                'user' => 'Regular User',
                                'contributor' => 'Contributor',
                                'premium' => 'Premium User',
                            ]),
                    ]),

                Section::make('Content Settings')
                    ->schema([
                        TextInput::make('max_upload_size')
                            ->label('Maximum Upload Size (MB)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email(),

                        Forms\Components\Repeater::make('social_links')
                            ->schema([
                                Select::make('platform')
                                    ->options([
                                        'twitter' => 'Twitter',
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'youtube' => 'YouTube',
                                        'linkedin' => 'LinkedIn',
                                    ])
                                    ->required(),

                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required(),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        // Process and save settings (would typically save to DB or settings file)
        // For demo purposes we're just displaying a success notification
        
        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
} 