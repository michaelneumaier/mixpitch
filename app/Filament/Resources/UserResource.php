<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-m-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\TextInput::make('username')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->hiddenOn('create'),

                                Forms\Components\Toggle::make('email_valid')
                                    ->label('Email Valid')
                                    ->default(true)
                                    ->helperText('Addresses marked invalid have bounced or been reported as spam'),

                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->maxLength(255),

                                Forms\Components\Select::make('roles')
                                    ->multiple()
                                    ->relationship('roles', 'name')
                                    ->preload(),
                            ]),
                    ]),

                Forms\Components\Section::make('Profile Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('profile_photo_path')
                                    ->label('Profile Photo')
                                    ->directory('profile-photos')
                                    ->visibility('public')
                                    ->image()
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('300')
                                    ->imageResizeTargetHeight('300'),

                                Forms\Components\Textarea::make('bio')
                                    ->maxLength(1000)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('website')
                                    ->url()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('location')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('headline')
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('profile_completed')
                                    ->default(false),

                                Forms\Components\Toggle::make('username_locked')
                                    ->label('Lock Username')
                                    ->default(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Professional Details')
                    ->schema([
                        Forms\Components\TagsInput::make('skills')
                            ->splitKeys(['Tab', 'Enter', ','])
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('equipment')
                            ->splitKeys(['Tab', 'Enter', ','])
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('specialties')
                            ->splitKeys(['Tab', 'Enter', ','])
                            ->columnSpanFull(),

                        Forms\Components\Select::make('portfolio_layout')
                            ->options([
                                'grid' => 'Grid',
                                'list' => 'List',
                                'masonry' => 'Masonry',
                                'carousel' => 'Carousel',
                            ])
                            ->default('grid'),
                    ]),

                Forms\Components\Section::make('Social Media')
                    ->schema([
                        Forms\Components\Repeater::make('social_links')
                            ->schema([
                                Forms\Components\Select::make('platform')
                                    ->options([
                                        'twitter' => 'Twitter',
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'linkedin' => 'LinkedIn',
                                        'youtube' => 'YouTube',
                                        'soundcloud' => 'SoundCloud',
                                        'spotify' => 'Spotify',
                                        'bandcamp' => 'Bandcamp',
                                        'other' => 'Other',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Subscription')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('subscription_plan')
                                    ->label('Plan')
                                    ->options([
                                        'free' => 'Free',
                                        'pro' => 'Pro',
                                    ])
                                    ->default('free')
                                    ->required(),

                                Forms\Components\Select::make('subscription_tier')
                                    ->label('Tier')
                                    ->options([
                                        'basic' => 'Basic',
                                        'artist' => 'Artist',
                                        'engineer' => 'Engineer',
                                    ])
                                    ->default('basic')
                                    ->required(),

                                Forms\Components\DateTimePicker::make('plan_started_at')
                                    ->label('Plan Started'),

                                Forms\Components\TextInput::make('monthly_pitch_count')
                                    ->label('Monthly Pitch Count')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\DatePicker::make('monthly_pitch_reset_date')
                                    ->label('Monthly Reset Date'),
                            ]),
                    ]),

                Forms\Components\Section::make('Storage Management')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('current_storage_usage')
                                    ->label('Current Storage Usage')
                                    ->content(fn ($record) => $record ?
                                        number_format($record->getTotalStorageUsed() / (1024 ** 3), 2).' GB' :
                                        'N/A'),

                                Forms\Components\Placeholder::make('storage_limit')
                                    ->label('Storage Limit (from subscription)')
                                    ->content(fn ($record) => $record ?
                                        number_format($record->getStorageLimitGB(), 1).' GB' :
                                        'N/A'),

                                Forms\Components\TextInput::make('storage_limit_override_gb')
                                    ->label('Storage Limit Override (GB)')
                                    ->helperText('Leave empty to use subscription default')
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(0)
                                    ->maxValue(1000),

                                Forms\Components\Placeholder::make('storage_percentage')
                                    ->label('Storage Usage')
                                    ->content(fn ($record) => $record ?
                                        number_format($record->getStorageUsedPercentage(), 1).'%' :
                                        'N/A'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->state(fn (User $record): bool => $record->email_verified_at !== null),

                Tables\Columns\IconColumn::make('email_valid')
                    ->label('Valid Email')
                    ->boolean(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        'moderator' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('profile_completed')
                    ->boolean()
                    ->label('Profile')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription_plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'pro' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription_tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'artist' => 'info',
                        'engineer' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_storage_used')
                    ->label('Storage Used')
                    ->formatStateUsing(fn ($state) => number_format(($state ?? 0) / (1024 ** 3), 2).' GB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('storage_limit_override_gb')
                    ->label('Storage Override')
                    ->formatStateUsing(fn ($state) => $state ? $state.' GB' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Email Verified')
                    ->toggle(),

                Tables\Filters\Filter::make('unverified')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->label('Email Not Verified')
                    ->toggle(),

                Tables\Filters\Filter::make('valid_email')
                    ->query(fn (Builder $query): Builder => $query->where('email_valid', true))
                    ->label('Valid Email')
                    ->toggle(),

                Tables\Filters\Filter::make('profile_completed')
                    ->query(fn (Builder $query): Builder => $query->where('profile_completed', true))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'Pro',
                    ]),

                Tables\Filters\SelectFilter::make('subscription_tier')
                    ->options([
                        'basic' => 'Basic',
                        'artist' => 'Artist',
                        'engineer' => 'Engineer',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('verifyEmail')
                    ->label('Verify Email')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->email_verified_at === null)
                    ->action(function (User $record): void {
                        $record->email_verified_at = now();
                        $record->save();
                        Notification::make()
                            ->title('Email Verified')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('unverifyEmail')
                    ->label('Unverify Email')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->email_verified_at !== null)
                    ->action(function (User $record): void {
                        $record->email_verified_at = null;
                        $record->save();
                        Notification::make()
                            ->title('Email Unverified')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('verifyEmails')
                        ->label('Verify Emails')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->email_verified_at === null) {
                                    $record->email_verified_at = now();
                                    $record->save();
                                    $updated++;
                                }
                            }
                            Notification::make()
                                ->title("$updated emails verified")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('unverifyEmails')
                        ->label('Unverify Emails')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->email_verified_at !== null) {
                                    $record->email_verified_at = null;
                                    $record->save();
                                    $updated++;
                                }
                            }
                            Notification::make()
                                ->title("$updated emails unverified")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProjectsRelationManager::class,
            RelationManagers\PitchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'invoices' => Pages\UserInvoices::route('/{record}/invoices'),
            'payment' => Pages\UserPayment::route('/{record}/payment'),
        ];
    }

    public static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('invoices')
                ->label('Invoices')
                ->icon('heroicon-o-document-text')
                ->url(fn (User $record): string => static::getUrl('invoices', ['record' => $record]))
                ->color('info'),
            Tables\Actions\Action::make('payment')
                ->label('Process Payment')
                ->icon('heroicon-o-credit-card')
                ->url(fn (User $record): string => static::getUrl('payment', ['record' => $record]))
                ->color('success'),
        ];
    }
}
