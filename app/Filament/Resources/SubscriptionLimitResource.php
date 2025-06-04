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
                    
                Forms\Components\TextInput::make('max_projects_owned')
                    ->label('Max Projects Owned')
                    ->numeric()
                    ->placeholder('Leave empty for unlimited'),
                    
                Forms\Components\TextInput::make('max_active_pitches')
                    ->label('Max Active Pitches')
                    ->numeric()
                    ->placeholder('Leave empty for unlimited'),
                    
                Forms\Components\TextInput::make('max_monthly_pitches')
                    ->label('Max Monthly Pitches')
                    ->numeric()
                    ->placeholder('Leave empty for unlimited'),
                    
                Forms\Components\TextInput::make('storage_per_project_mb')
                    ->label('Storage per Project (MB)')
                    ->numeric()
                    ->default(100)
                    ->required(),
                    
                Forms\Components\Toggle::make('priority_support')
                    ->label('Priority Support')
                    ->default(false),
                    
                Forms\Components\Toggle::make('custom_portfolio')
                    ->label('Custom Portfolio')
                    ->default(false),
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
                    }),
                    
                Tables\Columns\TextColumn::make('plan_tier')
                    ->label('Tier')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('max_projects_owned')
                    ->label('Max Projects')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited'),
                    
                Tables\Columns\TextColumn::make('max_active_pitches')
                    ->label('Max Active Pitches')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited'),
                    
                Tables\Columns\TextColumn::make('max_monthly_pitches')
                    ->label('Max Monthly Pitches')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited'),
                    
                Tables\Columns\TextColumn::make('storage_per_project_mb')
                    ->label('Storage (MB)')
                    ->suffix(' MB'),
                    
                Tables\Columns\IconColumn::make('priority_support')
                    ->label('Priority Support')
                    ->boolean(),
                    
                Tables\Columns\IconColumn::make('custom_portfolio')
                    ->label('Custom Portfolio')
                    ->boolean(),
                    
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
            'index' => Pages\ListSubscriptionLimits::route('/'),
            'create' => Pages\CreateSubscriptionLimit::route('/create'),
            'edit' => Pages\EditSubscriptionLimit::route('/{record}/edit'),
        ];
    }
}
