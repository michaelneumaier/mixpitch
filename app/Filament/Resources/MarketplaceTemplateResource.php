<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketplaceTemplateResource\Pages;
use App\Models\LicenseTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarketplaceTemplateResource extends Resource
{
    protected static ?string $model = LicenseTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationLabel = 'Marketplace Templates';
    
    protected static ?string $modelLabel = 'Template';
    
    protected static ?string $pluralModelLabel = 'Marketplace Templates';
    
    protected static ?string $navigationGroup = 'Content Management';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->disabled(),
                        
                        Forms\Components\Select::make('category')
                            ->options([
                                'music' => 'Music',
                                'sound-design' => 'Sound Design',
                                'mixing' => 'Mixing',
                                'mastering' => 'Mastering',
                                'general' => 'General',
                            ])
                            ->disabled(),
                        
                        Forms\Components\Select::make('use_case')
                            ->options([
                                'collaboration' => 'Collaboration',
                                'sync' => 'Sync',
                                'samples' => 'Samples',
                                'remix' => 'Remix',
                                'commercial' => 'Commercial',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Marketplace Information')
                    ->schema([
                        Forms\Components\TextInput::make('marketplace_title')
                            ->label('Marketplace Title')
                            ->required()
                            ->maxLength(150),
                        
                        Forms\Components\Textarea::make('marketplace_description')
                            ->label('Marketplace Description')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                        
                        Forms\Components\Textarea::make('submission_notes')
                            ->label('Submitter Notes')
                            ->disabled()
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Review')
                    ->schema([
                        Forms\Components\Select::make('approval_status')
                            ->options([
                                'pending' => 'Pending Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->live(),
                        
                        Forms\Components\Toggle::make('marketplace_featured')
                            ->label('Featured Template')
                            ->visible(fn (Forms\Get $get) => $get('approval_status') === 'approved'),
                        
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->visible(fn (Forms\Get $get) => $get('approval_status') === 'rejected')
                            ->required(fn (Forms\Get $get) => $get('approval_status') === 'rejected')
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Content Preview')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('License Content')
                            ->disabled()
                            ->rows(10),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('marketplace_title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                Tables\Columns\BadgeColumn::make('approval_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                Tables\Columns\IconColumn::make('marketplace_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('fork_count')
                    ->label('Forks')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('view_count')
                    ->label('Views')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('submitted_for_approval_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'music' => 'Music',
                        'sound-design' => 'Sound Design',
                        'mixing' => 'Mixing',
                        'mastering' => 'Mastering',
                        'general' => 'General',
                    ]),
                
                Tables\Filters\TernaryFilter::make('marketplace_featured')
                    ->label('Featured Templates'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Model $record) => $record->approval_status === 'pending')
                    ->action(function (Model $record) {
                        try {
                            \Log::info('Approving template', ['id' => $record->id, 'current_status' => $record->approval_status]);
                            
                            $updated = $record->update([
                                'approval_status' => 'approved',
                                'is_public' => true,
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'rejection_reason' => null,
                            ]);
                            
                            \Log::info('Update result', ['updated' => $updated, 'new_status' => $record->fresh()->approval_status]);
                            
                            Notification::make()
                                ->title('Template Approved')
                                ->body("Template '{$record->marketplace_title}' has been approved for marketplace.")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            \Log::error('Approval failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                            
                            Notification::make()
                                ->title('Approval Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Model $record) => $record->approval_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3)
                            ->placeholder('Please provide a clear reason for rejection...'),
                    ])
                    ->action(function (array $data, Model $record) {
                        $record->update([
                            'approval_status' => 'rejected',
                            'is_public' => false,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        
                        Notification::make()
                            ->title('Template Rejected')
                            ->body("Template '{$record->marketplace_title}' has been rejected.")
                            ->warning()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('feature')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Model $record) => $record->approval_status === 'approved' && !$record->marketplace_featured)
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        $record->update(['marketplace_featured' => true]);
                        
                        Notification::make()
                            ->title('Template Featured')
                            ->body("Template '{$record->marketplace_title}' is now featured.")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('unfeature')
                    ->icon('heroicon-o-star')
                    ->color('gray')
                    ->visible(fn (Model $record) => $record->marketplace_featured)
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        $record->update(['marketplace_featured' => false]);
                        
                        Notification::make()
                            ->title('Template Unfeatured')
                            ->body("Template '{$record->marketplace_title}' is no longer featured.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->approval_status === 'pending') {
                                    $record->update([
                                        'approval_status' => 'approved',
                                        'is_public' => true,
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                        'rejection_reason' => null,
                                    ]);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title('Templates Approved')
                                ->body("{$count} template(s) approved for marketplace.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('submitted_for_approval_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('submitted_for_approval_at')
            ->with(['user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketplaceTemplates::route('/'),
            'view' => Pages\ViewMarketplaceTemplate::route('/{record}'),
            'edit' => Pages\EditMarketplaceTemplate::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNotNull('submitted_for_approval_at')
            ->where('approval_status', 'pending')
            ->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
