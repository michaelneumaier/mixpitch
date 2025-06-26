<?php

namespace App\Filament\Pages;

use App\Models\PayoutHoldSetting;
use App\Services\PayoutHoldService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PayoutHoldSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Payout Hold Settings';
    protected static ?string $title = 'Payout Hold Period Management';
    protected static ?string $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.payout-hold-settings';

    public ?array $data = [];
    
    // Form properties
    public bool $enabled = false;
    public int $default_days = 3;
    public int $standard_days = 1;
    public int $contest_days = 0;
    public int $client_management_days = 0;
    public bool $business_days_only = true;
    public string $processing_time = '09:00';
    public int $minimum_hold_hours = 0;
    public bool $allow_admin_bypass = true;
    public bool $require_bypass_reason = false;
    public bool $log_bypasses = true;
    
    protected ?PayoutHoldService $holdService = null;

    public function mount(): void
    {
        $this->holdService = app(PayoutHoldService::class);
        
        try {
            $settings = PayoutHoldSetting::current();
            
            // Set properties directly
            $this->enabled = $settings->enabled;
            $this->default_days = $settings->default_days;
            $this->standard_days = $settings->workflow_days['standard'] ?? 1;
            $this->contest_days = $settings->workflow_days['contest'] ?? 0;
            $this->client_management_days = $settings->workflow_days['client_management'] ?? 0;
            $this->business_days_only = $settings->business_days_only;
            $this->processing_time = $settings->processing_time->format('H:i');
            $this->minimum_hold_hours = $settings->minimum_hold_hours;
            $this->allow_admin_bypass = $settings->allow_admin_bypass;
            $this->require_bypass_reason = $settings->require_bypass_reason;
            $this->log_bypasses = $settings->log_bypasses;
            
            \Log::info('Loaded settings successfully');
            
        } catch (\Exception $e) {
            \Log::error('Mount error: ' . $e->getMessage());
            // Default values are already set in property declarations
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Hold Period Configuration')
                    ->description('Configure payout hold periods for different project types')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('enabled')
                                    ->label('Enable Hold Periods')
                                    ->helperText('Master switch to enable/disable all hold periods')
                                    ->live(),
                                
                                TextInput::make('default_days')
                                    ->label('Default Hold Days')
                                    ->helperText('Fallback hold period for unknown workflow types')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->disabled(fn () => !$this->enabled),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('standard_days')
                                    ->label('Standard Projects (Days)')
                                    ->helperText('Hold period for regular project pitches')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->disabled(fn () => !$this->enabled),

                                TextInput::make('contest_days')
                                    ->label('Contest Projects (Days)')
                                    ->helperText('Hold period for contest prize payouts')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->disabled(fn () => !$this->enabled),

                                TextInput::make('client_management_days')
                                    ->label('Client Management (Days)')
                                    ->helperText('Hold period for client portal projects')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->disabled(fn () => !$this->enabled),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('business_days_only')
                                    ->label('Business Days Only')
                                    ->helperText('Exclude weekends from hold period calculations')
                                    ->disabled(fn () => !$this->enabled),

                                TimePicker::make('processing_time')
                                    ->label('Daily Processing Time')
                                    ->helperText('Time when payouts are processed daily')
                                    ->seconds(false)
                                    ->disabled(fn () => !$this->enabled),
                            ]),

                        TextInput::make('minimum_hold_hours')
                            ->label('Minimum Hold Hours')
                            ->helperText('Minimum delay even when hold periods are disabled (for safety)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(168)
                            ->suffix('hours'),
                    ]),

                Section::make('Admin Override Settings')
                    ->description('Configure administrative bypass capabilities')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('allow_admin_bypass')
                                    ->label('Allow Admin Bypass')
                                    ->helperText('Permit administrators to override hold periods')
                                    ->live(),

                                Toggle::make('require_bypass_reason')
                                    ->label('Require Bypass Reason')
                                    ->helperText('Mandate justification for hold period bypasses')
                                    ->disabled(fn () => !$this->allow_admin_bypass),

                                Toggle::make('log_bypasses')
                                    ->label('Log Bypass Actions')
                                    ->helperText('Record all bypass actions for audit purposes')
                                    ->disabled(fn () => !$this->allow_admin_bypass),
                            ]),
                    ]),

                Section::make('Current Configuration Preview')
                    ->description('Preview how current settings affect different workflow types')
                    ->schema([
                        Placeholder::make('preview')
                            ->content(fn () => $this->generatePreviewContent(null)),
                    ]),
            ]);
    }

    protected function generatePreviewContent($get): string
    {
        // Use component properties instead of $get
        if (!$this->enabled) {
            return "**Hold periods are DISABLED**\n\n" . 
                   ($this->minimum_hold_hours > 0 ? "Minimum safety delay: {$this->minimum_hold_hours} hours" : "Immediate payout processing");
        }

        $dayType = $this->business_days_only ? 'business days' : 'calendar days';

        return "**Active Hold Period Configuration:**\n\n" .
               "• **Standard Projects**: " . ($this->standard_days > 0 ? "{$this->standard_days} {$dayType}" : "Immediate") . "\n" .
               "• **Contest Projects**: " . ($this->contest_days > 0 ? "{$this->contest_days} {$dayType}" : "Immediate") . "\n" .
               "• **Client Management**: " . ($this->client_management_days > 0 ? "{$this->client_management_days} {$dayType}" : "Immediate") . "\n\n" .
               "**Processing Time**: {$this->processing_time} daily\n" .
               "**Day Calculation**: " . ucfirst($dayType);
    }

    public function save(): void
    {
        try {
            // Validate the form first
            $this->form->validate();
            
            // Prepare workflow days array using component properties
            $workflowDays = [
                'standard' => $this->standard_days,
                'contest' => $this->contest_days,
                'client_management' => $this->client_management_days,
            ];

            // Prepare update data using component properties
            $updateData = [
                'enabled' => $this->enabled,
                'default_days' => $this->default_days,
                'workflow_days' => $workflowDays,
                'business_days_only' => $this->business_days_only,
                'processing_time' => $this->processing_time,
                'minimum_hold_hours' => $this->minimum_hold_hours,
                'allow_admin_bypass' => $this->allow_admin_bypass,
                'require_bypass_reason' => $this->require_bypass_reason,
                'log_bypasses' => $this->log_bypasses,
            ];
            
            \Log::info('Save data:', $updateData);

            // Update settings using the service
            if (!$this->holdService) {
                $this->holdService = app(PayoutHoldService::class);
            }
            $this->holdService->updateSettings($updateData, Auth::user());

            // Clear any cached data
            Cache::forget('payout_hold_settings');

            Notification::make()
                ->title('Settings Saved Successfully')
                ->body('Payout hold period settings have been updated.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('PayoutHoldSettings save error: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data ?? null,
            ]);
            
            Notification::make()
                ->title('Save Failed')
                ->body('Error saving settings: ' . $e->getMessage())
                ->danger()
                ->send();

            // Don't throw Halt, just return
            return;
        }
    }



    public static function getNavigationBadge(): ?string
    {
        try {
            $settings = PayoutHoldSetting::current();
            return $settings->enabled ? 'Active' : 'Disabled';
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        try {
            $settings = PayoutHoldSetting::current();
            return $settings->enabled ? 'success' : 'warning';
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function canAccess(): bool
    {
        // Allow access to authenticated users who can access the admin panel
        return Auth::check();
    }
} 