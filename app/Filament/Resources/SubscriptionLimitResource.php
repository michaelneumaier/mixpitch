<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionLimitResource\Pages;
use App\Filament\Resources\SubscriptionLimitResource\RelationManagers;
use App\Models\SubscriptionLimit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionLimitResource extends Resource
{
    protected static ?string $model = SubscriptionLimit::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Subscription Limits';
    
    protected static ?string $navigationGroup = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Identification')
                    ->schema([
                        Forms\Components\Select::make('plan_name')
                            ->label('Plan Name')
                            ->options([
                                'free' => 'Free',
                                'pro' => 'Pro',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('plan_tier')
                            ->label('Plan Tier')
                            ->options([
                                'basic' => 'Basic',
                                'artist' => 'Artist',
                                'engineer' => 'Engineer',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Project & Pitch Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_projects_owned')
                            ->label('Max Projects Owned')
                            ->numeric()
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Maximum number of projects a user can own'),
                            
                        Forms\Components\TextInput::make('max_active_pitches')
                            ->label('Max Active Pitches')
                            ->numeric()
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Maximum number of active pitches'),
                            
                        Forms\Components\TextInput::make('max_monthly_pitches')
                            ->label('Max Monthly Pitches')
                            ->numeric()
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Monthly pitch limit for specific tiers'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Storage & File Management')
                    ->schema([
                        Forms\Components\TextInput::make('storage_per_project_mb')
                            ->label('Storage per Project (MB) - Legacy')
                            ->numeric()
                            ->default(100)
                            ->helperText('Legacy field - kept for compatibility')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('storage_per_project_gb')
                            ->label('Storage per Project (GB)')
                            ->numeric()
                            ->step(0.1)
                            ->default(1.0)
                            ->required()
                            ->helperText('Current storage limit in GB'),
                            
                        Forms\Components\TextInput::make('file_retention_days')
                            ->label('File Retention (Days)')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->helperText('Days files are kept after project closure'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Business Features')
                    ->schema([
                        Forms\Components\TextInput::make('platform_commission_rate')
                            ->label('Platform Commission (%)')
                            ->numeric()
                            ->step(0.1)
                            ->default(10.0)
                            ->suffix('%')
                            ->required()
                            ->helperText('Commission rate charged by platform'),
                            
                        Forms\Components\TextInput::make('max_license_templates')
                            ->label('Max License Templates')
                            ->numeric()
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Maximum custom license templates'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Engagement Features')
                    ->schema([
                        Forms\Components\TextInput::make('monthly_visibility_boosts')
                            ->label('Monthly Visibility Boosts')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Number of visibility boosts per month'),
                            
                        Forms\Components\TextInput::make('reputation_multiplier')
                            ->label('Reputation Multiplier')
                            ->numeric()
                            ->step(0.01)
                            ->default(1.0)
                            ->required()
                            ->helperText('Multiplier for reputation calculations'),
                            
                        Forms\Components\TextInput::make('max_private_projects_monthly')
                            ->label('Max Private Projects/Month')
                            ->numeric()
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Monthly limit for private projects'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Access & Analytics')
                    ->schema([
                        Forms\Components\Toggle::make('has_client_portal')
                            ->label('Client Portal Access')
                            ->helperText('Access to dedicated client portal'),
                            
                        Forms\Components\Select::make('analytics_level')
                            ->label('Analytics Level')
                            ->options([
                                'basic' => 'Basic Analytics',
                                'track' => 'Track-level Analytics',
                                'client_earnings' => 'Client & Earnings Analytics'
                            ])
                            ->default('basic')
                            ->required(),
                            
                        Forms\Components\Toggle::make('priority_support')
                            ->label('Priority Support')
                            ->default(false),
                            
                        Forms\Components\Toggle::make('custom_portfolio')
                            ->label('Custom Portfolio')
                            ->default(false),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Challenge & Competition Features')
                    ->schema([
                        Forms\Components\TextInput::make('challenge_early_access_hours')
                            ->label('Challenge Early Access (Hours)')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Hours of early access to challenges'),
                            
                        Forms\Components\Toggle::make('has_judge_access')
                            ->label('Judge Access')
                            ->helperText('Can participate as judge in challenges'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Support Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('support_sla_hours')
                            ->label('Support SLA (Hours)')
                            ->numeric()
                            ->placeholder('No SLA limit')
                            ->helperText('Maximum response time for support'),
                            
                        Forms\Components\CheckboxList::make('support_channels')
                            ->label('Support Channels')
                            ->options([
                                'forum' => 'Community Forum',
                                'email' => 'Email Support',
                                'chat' => 'Live Chat'
                            ])
                            ->default(['forum'])
                            ->required(),
                            
                        Forms\Components\TextInput::make('user_badge')
                            ->label('User Badge')
                            ->maxLength(10)
                            ->placeholder('🔷 or 🔶')
                            ->helperText('Unicode emoji badge for users'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'pro' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('plan_tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'artist' => 'info',
                        'engineer' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('max_projects_owned')
                    ->label('Projects')
                    ->formatStateUsing(fn ($state) => $state ?? '∞'),
                    
                Tables\Columns\TextColumn::make('max_active_pitches')
                    ->label('Pitches')
                    ->formatStateUsing(fn ($state) => $state ?? '∞'),
                    
                Tables\Columns\TextColumn::make('storage_per_project_gb')
                    ->label('Storage')
                    ->formatStateUsing(fn ($state) => $state . ' GB')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('platform_commission_rate')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('monthly_visibility_boosts')
                    ->label('Boosts/mo')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('reputation_multiplier')
                    ->label('Rep. Multi.')
                    ->formatStateUsing(fn ($state) => $state . '×')
                    ->alignCenter(),
                    
                Tables\Columns\IconColumn::make('has_client_portal')
                    ->label('Portal')
                    ->boolean()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('analytics_level')
                    ->label('Analytics')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'track' => 'info',
                        'client_earnings' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('user_badge')
                    ->label('Badge')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('support_sla_hours')
                    ->label('SLA (hrs)')
                    ->formatStateUsing(fn ($state) => $state ?? 'No SLA')
                    ->alignCenter(),
                    
                Tables\Columns\IconColumn::make('has_judge_access')
                    ->label('Judge')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('file_retention_days')
                    ->label('Retention')
                    ->formatStateUsing(fn ($state) => $state . ' days')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_name')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'Pro',
                    ]),
                    
                Tables\Filters\SelectFilter::make('plan_tier')
                    ->options([
                        'basic' => 'Basic',
                        'artist' => 'Artist',
                        'engineer' => 'Engineer',
                    ]),
                    
                Tables\Filters\SelectFilter::make('analytics_level')
                    ->options([
                        'basic' => 'Basic Analytics',
                        'track' => 'Track-level Analytics',
                        'client_earnings' => 'Client & Earnings Analytics'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('plan_name')
            ->striped();
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
            'index' => Pages\ListSubscriptionLimits::route('/'),
            'create' => Pages\CreateSubscriptionLimit::route('/create'),
            'edit' => Pages\EditSubscriptionLimit::route('/{record}/edit'),
        ];
    }
}
