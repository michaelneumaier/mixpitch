# 15. Custom Client Portals Implementation Plan

## Feature Overview

Custom Client Portals allow large studios to create branded portals for their clients with customizable themes, logos, colors, and custom domains. Each client portal provides a tailored experience where clients only see projects and content associated with their specific brand/department.

### Core Functionality
- **Brand Management**: Create and manage multiple portal brands with unique themes
- **Custom Theming**: Configure logos, color schemes, and visual identity per portal
- **Domain Management**: Support custom domains with DNS validation
- **Client Isolation**: Clients only see content associated with their brand
- **Portal Analytics**: Track usage and engagement per portal
- **Responsive Design**: Mobile-optimized portal experience

## Database Schema

### Portal Brands Table
```sql
CREATE TABLE portal_brands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    logo_url VARCHAR(1000) NULL,
    favicon_url VARCHAR(1000) NULL,
    primary_color VARCHAR(7) NOT NULL DEFAULT '#3B82F6',
    secondary_color VARCHAR(7) NOT NULL DEFAULT '#1F2937',
    accent_color VARCHAR(7) NOT NULL DEFAULT '#10B981',
    background_color VARCHAR(7) NOT NULL DEFAULT '#FFFFFF',
    text_color VARCHAR(7) NOT NULL DEFAULT '#111827',
    custom_domain VARCHAR(255) NULL,
    domain_verified_at TIMESTAMP NULL,
    custom_css TEXT NULL,
    welcome_message TEXT NULL,
    contact_email VARCHAR(255) NULL,
    contact_phone VARCHAR(255) NULL,
    footer_text TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    plan_type ENUM('basic', 'professional', 'enterprise') NOT NULL DEFAULT 'basic',
    max_projects INT NOT NULL DEFAULT 10,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_portal_brands_user_id (user_id),
    INDEX idx_portal_brands_slug (slug),
    INDEX idx_portal_brands_domain (custom_domain)
);
```

### Portal Analytics Table
```sql
CREATE TABLE portal_analytics (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    portal_brand_id BIGINT UNSIGNED NOT NULL,
    client_email VARCHAR(255) NOT NULL,
    event_type ENUM('visit', 'project_view', 'file_download', 'comment_posted', 'approval_given') NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (portal_brand_id) REFERENCES portal_brands(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_portal_analytics_brand_id (portal_brand_id),
    INDEX idx_portal_analytics_client_email (client_email),
    INDEX idx_portal_analytics_event_type (event_type),
    INDEX idx_portal_analytics_occurred_at (occurred_at)
);
```

### Update Projects Table
```sql
ALTER TABLE projects 
ADD COLUMN portal_brand_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (portal_brand_id) REFERENCES portal_brands(id) ON DELETE SET NULL,
ADD INDEX idx_projects_portal_brand_id (portal_brand_id);
```

## Service Architecture

### PortalBrandService
```php
<?php

namespace App\Services;

use App\Models\PortalBrand;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class PortalBrandService
{
    public function createPortalBrand(User $user, array $data): PortalBrand
    {
        $this->validatePortalLimits($user);
        
        $portalBrand = PortalBrand::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => $this->generateUniqueSlug($data['name']),
            'primary_color' => $data['primary_color'] ?? '#3B82F6',
            'secondary_color' => $data['secondary_color'] ?? '#1F2937',
            'accent_color' => $data['accent_color'] ?? '#10B981',
            'background_color' => $data['background_color'] ?? '#FFFFFF',
            'text_color' => $data['text_color'] ?? '#111827',
            'welcome_message' => $data['welcome_message'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'footer_text' => $data['footer_text'] ?? null,
            'plan_type' => $user->subscription_plan ?? 'basic',
            'max_projects' => $this->getMaxProjectsForPlan($user->subscription_plan ?? 'basic'),
        ]);
        
        if (isset($data['logo'])) {
            $this->uploadLogo($portalBrand, $data['logo']);
        }
        
        if (isset($data['favicon'])) {
            $this->uploadFavicon($portalBrand, $data['favicon']);
        }
        
        return $portalBrand;
    }
    
    public function updatePortalBrand(PortalBrand $portalBrand, array $data): PortalBrand
    {
        $updateData = array_intersect_key($data, array_flip([
            'name', 'primary_color', 'secondary_color', 'accent_color',
            'background_color', 'text_color', 'welcome_message',
            'contact_email', 'contact_phone', 'footer_text', 'custom_css'
        ]));
        
        if (isset($data['name']) && $data['name'] !== $portalBrand->name) {
            $updateData['slug'] = $this->generateUniqueSlug($data['name'], $portalBrand->id);
        }
        
        $portalBrand->update($updateData);
        
        if (isset($data['logo'])) {
            $this->uploadLogo($portalBrand, $data['logo']);
        }
        
        if (isset($data['favicon'])) {
            $this->uploadFavicon($portalBrand, $data['favicon']);
        }
        
        return $portalBrand->fresh();
    }
    
    public function setCustomDomain(PortalBrand $portalBrand, string $domain): bool
    {
        $domain = $this->sanitizeDomain($domain);
        
        if (!$this->validateDomainFormat($domain)) {
            throw new \InvalidArgumentException('Invalid domain format');
        }
        
        if ($this->isDomainTaken($domain, $portalBrand->id)) {
            throw new \InvalidArgumentException('Domain already in use');
        }
        
        $portalBrand->update([
            'custom_domain' => $domain,
            'domain_verified_at' => null,
        ]);
        
        return $this->verifyDomainOwnership($portalBrand);
    }
    
    public function verifyDomainOwnership(PortalBrand $portalBrand): bool
    {
        if (!$portalBrand->custom_domain) {
            return false;
        }
        
        try {
            // Check for required DNS records
            $txtRecord = dns_get_record($portalBrand->custom_domain, DNS_TXT);
            $expectedValue = "mixpitch-verification=" . $portalBrand->slug;
            
            foreach ($txtRecord as $record) {
                if (isset($record['txt']) && $record['txt'] === $expectedValue) {
                    $portalBrand->update(['domain_verified_at' => now()]);
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('Domain verification failed', [
                'portal_brand_id' => $portalBrand->id,
                'domain' => $portalBrand->custom_domain,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function generatePortalUrl(PortalBrand $portalBrand): string
    {
        if ($portalBrand->custom_domain && $portalBrand->domain_verified_at) {
            return 'https://' . $portalBrand->custom_domain;
        }
        
        return route('portal.show', ['slug' => $portalBrand->slug]);
    }
    
    public function getPortalTheme(PortalBrand $portalBrand): array
    {
        return [
            'colors' => [
                'primary' => $portalBrand->primary_color,
                'secondary' => $portalBrand->secondary_color,
                'accent' => $portalBrand->accent_color,
                'background' => $portalBrand->background_color,
                'text' => $portalBrand->text_color,
            ],
            'logo_url' => $portalBrand->logo_url,
            'favicon_url' => $portalBrand->favicon_url,
            'custom_css' => $portalBrand->custom_css,
        ];
    }
    
    public function getPortalProjects(PortalBrand $portalBrand, ?string $clientEmail = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Project::where('portal_brand_id', $portalBrand->id)
            ->with(['user', 'projectFiles', 'approvedPitch.pitchFiles'])
            ->where('workflow_type', 'client_management');
        
        if ($clientEmail) {
            $query->where('client_email', $clientEmail);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
    
    protected function uploadLogo(PortalBrand $portalBrand, $logoFile): void
    {
        $image = Image::make($logoFile)->fit(300, 100);
        $filename = "portal-logos/{$portalBrand->slug}-logo." . $logoFile->getClientOriginalExtension();
        
        Storage::disk('s3')->put($filename, $image->encode());
        
        $portalBrand->update(['logo_url' => Storage::disk('s3')->url($filename)]);
    }
    
    protected function uploadFavicon(PortalBrand $portalBrand, $faviconFile): void
    {
        $image = Image::make($faviconFile)->fit(32, 32);
        $filename = "portal-favicons/{$portalBrand->slug}-favicon.ico";
        
        Storage::disk('s3')->put($filename, $image->encode('ico'));
        
        $portalBrand->update(['favicon_url' => Storage::disk('s3')->url($filename)]);
    }
    
    protected function validatePortalLimits(User $user): void
    {
        $currentCount = PortalBrand::where('user_id', $user->id)->count();
        $maxBrands = $this->getMaxBrandsForPlan($user->subscription_plan ?? 'basic');
        
        if ($currentCount >= $maxBrands) {
            throw new \Exception("Plan limit reached. Maximum {$maxBrands} portal brands allowed.");
        }
    }
    
    protected function getMaxBrandsForPlan(string $plan): int
    {
        return match($plan) {
            'basic' => 1,
            'professional' => 3,
            'enterprise' => 10,
            default => 1,
        };
    }
    
    protected function getMaxProjectsForPlan(string $plan): int
    {
        return match($plan) {
            'basic' => 10,
            'professional' => 50,
            'enterprise' => 200,
            default => 10,
        };
    }
    
    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = PortalBrand::where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    protected function sanitizeDomain(string $domain): string
    {
        return strtolower(trim($domain, '/'));
    }
    
    protected function validateDomainFormat(string $domain): bool
    {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }
    
    protected function isDomainTaken(string $domain, ?int $excludeId = null): bool
    {
        $query = PortalBrand::where('custom_domain', $domain);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
```

### PortalAnalyticsService
```php
<?php

namespace App\Services;

use App\Models\PortalBrand;
use App\Models\PortalAnalytics;
use App\Models\Project;
use Illuminate\Support\Facades\Request;

class PortalAnalyticsService
{
    public function trackEvent(
        PortalBrand $portalBrand,
        string $clientEmail,
        string $eventType,
        ?Project $project = null,
        ?array $metadata = null
    ): PortalAnalytics {
        return PortalAnalytics::create([
            'portal_brand_id' => $portalBrand->id,
            'client_email' => $clientEmail,
            'event_type' => $eventType,
            'project_id' => $project?->id,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'occurred_at' => now(),
        ]);
    }
    
    public function getPortalStats(PortalBrand $portalBrand, ?string $period = '30d'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $analytics = PortalAnalytics::where('portal_brand_id', $portalBrand->id)
            ->where('occurred_at', '>=', $startDate)
            ->get();
        
        return [
            'total_visits' => $analytics->where('event_type', 'visit')->count(),
            'unique_visitors' => $analytics->where('event_type', 'visit')->unique('client_email')->count(),
            'project_views' => $analytics->where('event_type', 'project_view')->count(),
            'file_downloads' => $analytics->where('event_type', 'file_download')->count(),
            'comments_posted' => $analytics->where('event_type', 'comment_posted')->count(),
            'approvals_given' => $analytics->where('event_type', 'approval_given')->count(),
            'daily_breakdown' => $this->getDailyBreakdown($analytics),
            'most_active_clients' => $this->getMostActiveClients($analytics),
            'popular_projects' => $this->getPopularProjects($analytics),
        ];
    }
    
    protected function getPeriodStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };
    }
    
    protected function getDailyBreakdown($analytics): array
    {
        return $analytics->groupBy(function($item) {
            return $item->occurred_at->format('Y-m-d');
        })->map(function($dayEvents) {
            return [
                'visits' => $dayEvents->where('event_type', 'visit')->count(),
                'project_views' => $dayEvents->where('event_type', 'project_view')->count(),
                'downloads' => $dayEvents->where('event_type', 'file_download')->count(),
            ];
        })->toArray();
    }
    
    protected function getMostActiveClients($analytics): array
    {
        return $analytics->groupBy('client_email')
            ->map(function($clientEvents) {
                return [
                    'total_events' => $clientEvents->count(),
                    'visits' => $clientEvents->where('event_type', 'visit')->count(),
                    'last_activity' => $clientEvents->max('occurred_at'),
                ];
            })
            ->sortByDesc('total_events')
            ->take(10)
            ->toArray();
    }
    
    protected function getPopularProjects($analytics): array
    {
        return $analytics->whereNotNull('project_id')
            ->groupBy('project_id')
            ->map(function($projectEvents) {
                return [
                    'views' => $projectEvents->where('event_type', 'project_view')->count(),
                    'downloads' => $projectEvents->where('event_type', 'file_download')->count(),
                    'comments' => $projectEvents->where('event_type', 'comment_posted')->count(),
                ];
            })
            ->sortByDesc('views')
            ->take(10)
            ->toArray();
    }
}
```

## Models

### PortalBrand Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalBrand extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'text_color',
        'custom_domain',
        'domain_verified_at',
        'custom_css',
        'welcome_message',
        'contact_email',
        'contact_phone',
        'footer_text',
        'is_active',
        'plan_type',
        'max_projects',
    ];
    
    protected $casts = [
        'domain_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
    
    public function analytics(): HasMany
    {
        return $this->hasMany(PortalAnalytics::class);
    }
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    public function isDomainVerified(): bool
    {
        return !is_null($this->domain_verified_at);
    }
    
    public function getThemeColors(): array
    {
        return [
            'primary' => $this->primary_color,
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
            'background' => $this->background_color,
            'text' => $this->text_color,
        ];
    }
    
    public function canAddProjects(): bool
    {
        return $this->projects()->count() < $this->max_projects;
    }
}
```

### PortalAnalytics Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalAnalytics extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'portal_brand_id',
        'client_email',
        'event_type',
        'project_id',
        'metadata',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];
    
    public $timestamps = false;
    
    public function portalBrand(): BelongsTo
    {
        return $this->belongsTo(PortalBrand::class);
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

## UI Implementation

### Portal Management Livewire Component
```php
<?php

namespace App\Livewire\Portal;

use App\Models\PortalBrand;
use App\Services\PortalBrandService;
use App\Services\PortalAnalyticsService;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManagePortalBrands extends Component
{
    use WithFileUploads;
    
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showAnalyticsModal = false;
    public $editingPortal = null;
    public $analyticsPortal = null;
    
    // Form fields
    public $name = '';
    public $primaryColor = '#3B82F6';
    public $secondaryColor = '#1F2937';
    public $accentColor = '#10B981';
    public $backgroundColor = '#FFFFFF';
    public $textColor = '#111827';
    public $welcomeMessage = '';
    public $contactEmail = '';
    public $contactPhone = '';
    public $footerText = '';
    public $customCss = '';
    public $customDomain = '';
    public $logo = null;
    public $favicon = null;
    
    public $analytics = [];
    public $analyticsPeriod = '30d';
    
    protected $rules = [
        'name' => 'required|string|max:255',
        'primaryColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'secondaryColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'accentColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'backgroundColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'textColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'welcomeMessage' => 'nullable|string|max:500',
        'contactEmail' => 'nullable|email|max:255',
        'contactPhone' => 'nullable|string|max:255',
        'footerText' => 'nullable|string|max:500',
        'customCss' => 'nullable|string|max:10000',
        'customDomain' => 'nullable|string|max:255',
        'logo' => 'nullable|image|max:2048',
        'favicon' => 'nullable|image|max:512',
    ];
    
    public function createPortal()
    {
        $this->validate();
        
        try {
            $portalBrandService = app(PortalBrandService::class);
            
            $data = [
                'name' => $this->name,
                'primary_color' => $this->primaryColor,
                'secondary_color' => $this->secondaryColor,
                'accent_color' => $this->accentColor,
                'background_color' => $this->backgroundColor,
                'text_color' => $this->textColor,
                'welcome_message' => $this->welcomeMessage,
                'contact_email' => $this->contactEmail,
                'contact_phone' => $this->contactPhone,
                'footer_text' => $this->footerText,
                'custom_css' => $this->customCss,
            ];
            
            if ($this->logo) {
                $data['logo'] = $this->logo;
            }
            
            if ($this->favicon) {
                $data['favicon'] = $this->favicon;
            }
            
            $portalBrand = $portalBrandService->createPortalBrand(auth()->user(), $data);
            
            if ($this->customDomain) {
                $portalBrandService->setCustomDomain($portalBrand, $this->customDomain);
            }
            
            $this->resetForm();
            $this->showCreateModal = false;
            
            $this->dispatch('portal-created');
            session()->flash('success', 'Portal brand created successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function editPortal(PortalBrand $portal)
    {
        $this->editingPortal = $portal;
        $this->name = $portal->name;
        $this->primaryColor = $portal->primary_color;
        $this->secondaryColor = $portal->secondary_color;
        $this->accentColor = $portal->accent_color;
        $this->backgroundColor = $portal->background_color;
        $this->textColor = $portal->text_color;
        $this->welcomeMessage = $portal->welcome_message ?? '';
        $this->contactEmail = $portal->contact_email ?? '';
        $this->contactPhone = $portal->contact_phone ?? '';
        $this->footerText = $portal->footer_text ?? '';
        $this->customCss = $portal->custom_css ?? '';
        $this->customDomain = $portal->custom_domain ?? '';
        
        $this->showEditModal = true;
    }
    
    public function updatePortal()
    {
        $this->validate();
        
        try {
            $portalBrandService = app(PortalBrandService::class);
            
            $data = [
                'name' => $this->name,
                'primary_color' => $this->primaryColor,
                'secondary_color' => $this->secondaryColor,
                'accent_color' => $this->accentColor,
                'background_color' => $this->backgroundColor,
                'text_color' => $this->textColor,
                'welcome_message' => $this->welcomeMessage,
                'contact_email' => $this->contactEmail,
                'contact_phone' => $this->contactPhone,
                'footer_text' => $this->footerText,
                'custom_css' => $this->customCss,
            ];
            
            if ($this->logo) {
                $data['logo'] = $this->logo;
            }
            
            if ($this->favicon) {
                $data['favicon'] = $this->favicon;
            }
            
            $portalBrandService->updatePortalBrand($this->editingPortal, $data);
            
            if ($this->customDomain !== $this->editingPortal->custom_domain) {
                if ($this->customDomain) {
                    $portalBrandService->setCustomDomain($this->editingPortal, $this->customDomain);
                } else {
                    $this->editingPortal->update(['custom_domain' => null, 'domain_verified_at' => null]);
                }
            }
            
            $this->resetForm();
            $this->showEditModal = false;
            $this->editingPortal = null;
            
            session()->flash('success', 'Portal brand updated successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    
    public function showAnalytics(PortalBrand $portal)
    {
        $this->analyticsPortal = $portal;
        $this->loadAnalytics();
        $this->showAnalyticsModal = true;
    }
    
    public function updatedAnalyticsPeriod()
    {
        $this->loadAnalytics();
    }
    
    protected function loadAnalytics()
    {
        if ($this->analyticsPortal) {
            $analyticsService = app(PortalAnalyticsService::class);
            $this->analytics = $analyticsService->getPortalStats($this->analyticsPortal, $this->analyticsPeriod);
        }
    }
    
    public function verifyDomain(PortalBrand $portal)
    {
        $portalBrandService = app(PortalBrandService::class);
        
        if ($portalBrandService->verifyDomainOwnership($portal)) {
            session()->flash('success', 'Domain verified successfully!');
        } else {
            session()->flash('error', 'Domain verification failed. Please check your DNS settings.');
        }
    }
    
    public function deletePortal(PortalBrand $portal)
    {
        try {
            $portal->delete();
            session()->flash('success', 'Portal brand deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting portal brand: ' . $e->getMessage());
        }
    }
    
    protected function resetForm()
    {
        $this->name = '';
        $this->primaryColor = '#3B82F6';
        $this->secondaryColor = '#1F2937';
        $this->accentColor = '#10B981';
        $this->backgroundColor = '#FFFFFF';
        $this->textColor = '#111827';
        $this->welcomeMessage = '';
        $this->contactEmail = '';
        $this->contactPhone = '';
        $this->footerText = '';
        $this->customCss = '';
        $this->customDomain = '';
        $this->logo = null;
        $this->favicon = null;
    }
    
    public function render()
    {
        $portals = PortalBrand::where('user_id', auth()->id())
            ->withCount('projects')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('livewire.portal.manage-portal-brands', compact('portals'));
    }
}
```

### Portal Management Blade Template
```blade
{{-- resources/views/livewire/portal/manage-portal-brands.blade.php --}}
<div class="space-y-6">
    <flux:heading size="lg">Portal Brands</flux:heading>
    
    <div class="flex justify-between items-center">
        <flux:text>
            Create branded client portals with custom themes and domains for your clients.
        </flux:text>
        
        <flux:button wire:click="$set('showCreateModal', true)" variant="primary">
            <flux:icon name="plus" class="size-4" />
            Create Portal
        </flux:button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($portals as $portal)
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $portal->name }}</h3>
                        
                        <div class="flex items-center gap-2">
                            @if($portal->isDomainVerified())
                                <flux:badge color="green" size="sm">
                                    <flux:icon name="check-circle" class="size-3" />
                                    Verified
                                </flux:badge>
                            @elseif($portal->custom_domain)
                                <flux:badge color="yellow" size="sm">
                                    <flux:icon name="clock" class="size-3" />
                                    Pending
                                </flux:badge>
                            @endif
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="size-4 rounded" style="background-color: {{ $portal->primary_color }}"></span>
                            <flux:text size="sm" class="text-gray-600">{{ $portal->primary_color }}</flux:text>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <strong>{{ $portal->projects_count }}</strong> projects
                        </div>
                        
                        @if($portal->custom_domain)
                            <div class="text-sm">
                                <flux:text class="text-gray-600">Domain:</flux:text>
                                <flux:text class="font-mono">{{ $portal->custom_domain }}</flux:text>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex gap-2">
                        <flux:button 
                            wire:click="editPortal({{ $portal->id }})" 
                            size="sm" 
                            variant="ghost"
                        >
                            <flux:icon name="pencil" class="size-4" />
                            Edit
                        </flux:button>
                        
                        <flux:button 
                            wire:click="showAnalytics({{ $portal->id }})" 
                            size="sm" 
                            variant="ghost"
                        >
                            <flux:icon name="chart-bar" class="size-4" />
                            Analytics
                        </flux:button>
                        
                        <flux:dropdown>
                            <flux:button size="sm" variant="ghost">
                                <flux:icon name="ellipsis-horizontal" class="size-4" />
                            </flux:button>
                            
                            <flux:dropdown.menu>
                                <flux:dropdown.item wire:click="verifyDomain({{ $portal->id }})" :disabled="!$portal->custom_domain">
                                    <flux:icon name="shield-check" class="size-4" />
                                    Verify Domain
                                </flux:dropdown.item>
                                
                                <flux:dropdown.item 
                                    wire:click="deletePortal({{ $portal->id }})"
                                    wire:confirm="Are you sure you want to delete this portal brand?"
                                    variant="danger"
                                >
                                    <flux:icon name="trash" class="size-4" />
                                    Delete
                                </flux:dropdown.item>
                            </flux:dropdown.menu>
                        </flux:dropdown>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <flux:callout>
                    <flux:callout.text>
                        No portal brands created yet. Create your first portal to get started.
                    </flux:callout.text>
                </flux:callout>
            </div>
        @endforelse
    </div>
    
    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showCreateModal" name="create-portal">
        <flux:modal.header>
            <flux:heading size="lg">Create Portal Brand</flux:heading>
        </flux:modal.header>
        
        <form wire:submit="createPortal" class="space-y-6">
            <flux:modal.body class="space-y-6">
                {{-- Basic Information --}}
                <div class="space-y-4">
                    <flux:heading size="sm">Basic Information</flux:heading>
                    
                    <flux:field>
                        <flux:label>Portal Name</flux:label>
                        <flux:input wire:model="name" placeholder="Enter portal name" />
                        <flux:error name="name" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Welcome Message</flux:label>
                        <flux:textarea wire:model="welcomeMessage" placeholder="Welcome message for clients" rows="3" />
                        <flux:error name="welcomeMessage" />
                    </flux:field>
                </div>
                
                {{-- Brand Assets --}}
                <div class="space-y-4">
                    <flux:heading size="sm">Brand Assets</flux:heading>
                    
                    <flux:field>
                        <flux:label>Logo</flux:label>
                        <input type="file" wire:model="logo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        <flux:error name="logo" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Favicon</flux:label>
                        <input type="file" wire:model="favicon" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        <flux:error name="favicon" />
                    </flux:field>
                </div>
                
                {{-- Color Theme --}}
                <div class="space-y-4">
                    <flux:heading size="sm">Color Theme</flux:heading>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Primary Color</flux:label>
                            <input type="color" wire:model="primaryColor" class="h-10 w-full rounded-md border border-gray-300" />
                            <flux:error name="primaryColor" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Secondary Color</flux:label>
                            <input type="color" wire:model="secondaryColor" class="h-10 w-full rounded-md border border-gray-300" />
                            <flux:error name="secondaryColor" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Accent Color</flux:label>
                            <input type="color" wire:model="accentColor" class="h-10 w-full rounded-md border border-gray-300" />
                            <flux:error name="accentColor" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Text Color</flux:label>
                            <input type="color" wire:model="textColor" class="h-10 w-full rounded-md border border-gray-300" />
                            <flux:error name="textColor" />
                        </flux:field>
                    </div>
                </div>
                
                {{-- Contact Information --}}
                <div class="space-y-4">
                    <flux:heading size="sm">Contact Information</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Contact Email</flux:label>
                            <flux:input wire:model="contactEmail" type="email" placeholder="support@example.com" />
                            <flux:error name="contactEmail" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Contact Phone</flux:label>
                            <flux:input wire:model="contactPhone" placeholder="+1 (555) 123-4567" />
                            <flux:error name="contactPhone" />
                        </flux:field>
                    </div>
                    
                    <flux:field>
                        <flux:label>Footer Text</flux:label>
                        <flux:textarea wire:model="footerText" placeholder="Footer text for the portal" rows="2" />
                        <flux:error name="footerText" />
                    </flux:field>
                </div>
                
                {{-- Advanced --}}
                <div class="space-y-4">
                    <flux:heading size="sm">Advanced Settings</flux:heading>
                    
                    <flux:field>
                        <flux:label>Custom Domain</flux:label>
                        <flux:input wire:model="customDomain" placeholder="portal.yourdomain.com" />
                        <flux:text size="sm" class="text-gray-600">
                            You'll need to add DNS records to verify domain ownership.
                        </flux:text>
                        <flux:error name="customDomain" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Custom CSS</flux:label>
                        <flux:textarea wire:model="customCss" placeholder="/* Additional CSS styles */" rows="4" class="font-mono text-sm" />
                        <flux:error name="customCss" />
                    </flux:field>
                </div>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button type="button" wire:click="$set('showCreateModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                
                <flux:button type="submit" variant="primary">
                    Create Portal
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>
    
    {{-- Edit Modal (similar structure but with update method) --}}
    <flux:modal wire:model="showEditModal" name="edit-portal">
        <flux:modal.header>
            <flux:heading size="lg">Edit Portal Brand</flux:heading>
        </flux:modal.header>
        
        {{-- Same form structure as create but wire:submit="updatePortal" --}}
        <form wire:submit="updatePortal" class="space-y-6">
            {{-- Include same form fields as create modal --}}
            {{-- ... (same form content) ... --}}
            
            <flux:modal.footer>
                <flux:button type="button" wire:click="$set('showEditModal', false)" variant="ghost">
                    Cancel
                </flux:button>
                
                <flux:button type="submit" variant="primary">
                    Update Portal
                </flux:button>
            </flux:modal.footer>
        </form>
    </flux:modal>
    
    {{-- Analytics Modal --}}
    <flux:modal wire:model="showAnalyticsModal" name="portal-analytics" size="2xl">
        <flux:modal.header>
            <flux:heading size="lg">Portal Analytics</flux:heading>
        </flux:modal.header>
        
        <flux:modal.body>
            @if($analyticsPortal)
                <div class="space-y-6">
                    <flux:field>
                        <flux:label>Time Period</flux:label>
                        <flux:select wire:model.live="analyticsPeriod">
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                            <option value="90d">Last 90 days</option>
                            <option value="1y">Last year</option>
                        </flux:select>
                    </flux:field>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $analytics['total_visits'] ?? 0 }}</div>
                            <div class="text-sm text-blue-600">Total Visits</div>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $analytics['unique_visitors'] ?? 0 }}</div>
                            <div class="text-sm text-green-600">Unique Visitors</div>
                        </div>
                        
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ $analytics['project_views'] ?? 0 }}</div>
                            <div class="text-sm text-purple-600">Project Views</div>
                        </div>
                        
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-orange-600">{{ $analytics['file_downloads'] ?? 0 }}</div>
                            <div class="text-sm text-orange-600">Downloads</div>
                        </div>
                    </div>
                </div>
            @endif
        </flux:modal.body>
    </flux:modal>
</div>
```

### Client Portal View Component
```php
<?php

namespace App\Livewire\Portal;

use App\Models\PortalBrand;
use App\Models\Project;
use App\Services\PortalAnalyticsService;
use Livewire\Component;

class ClientPortalView extends Component
{
    public PortalBrand $portalBrand;
    public ?string $clientEmail = null;
    public ?string $token = null;
    
    public function mount(PortalBrand $portalBrand, ?string $token = null)
    {
        $this->portalBrand = $portalBrand;
        $this->token = $token;
        
        if ($token) {
            // Validate and extract client email from token
            $this->clientEmail = $this->validateClientToken($token);
        }
        
        // Track visit
        if ($this->clientEmail) {
            app(PortalAnalyticsService::class)->trackEvent(
                $this->portalBrand,
                $this->clientEmail,
                'visit'
            );
        }
    }
    
    public function viewProject(Project $project)
    {
        if ($this->clientEmail) {
            app(PortalAnalyticsService::class)->trackEvent(
                $this->portalBrand,
                $this->clientEmail,
                'project_view',
                $project
            );
        }
        
        return redirect()->route('portal.project', [
            'portal' => $this->portalBrand->slug,
            'project' => $project->id,
            'token' => $this->token
        ]);
    }
    
    protected function validateClientToken(string $token): ?string
    {
        // Implement token validation logic
        // This should verify the JWT token and extract client email
        try {
            $payload = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(config('app.key'), 'HS256'));
            return $payload->client_email ?? null;
        } catch (\Exception $e) {
            abort(403, 'Invalid or expired token');
        }
    }
    
    public function render()
    {
        $projects = collect();
        
        if ($this->clientEmail) {
            $projects = Project::where('portal_brand_id', $this->portalBrand->id)
                ->where('client_email', $this->clientEmail)
                ->where('workflow_type', 'client_management')
                ->with(['user', 'projectFiles', 'approvedPitch.pitchFiles'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return view('livewire.portal.client-portal-view', compact('projects'))
            ->layout('components.layouts.portal', [
                'portalBrand' => $this->portalBrand
            ]);
    }
}
```

### Portal Layout Component
```blade
{{-- resources/views/components/layouts/portal.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      style="--primary-color: {{ $portalBrand->primary_color }}; --secondary-color: {{ $portalBrand->secondary_color }}; --accent-color: {{ $portalBrand->accent_color }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $portalBrand->name }} - Client Portal</title>
    
    @if($portalBrand->favicon_url)
        <link rel="icon" href="{{ $portalBrand->favicon_url }}">
    @endif
    
    <style>
        :root {
            --primary: {{ $portalBrand->primary_color }};
            --secondary: {{ $portalBrand->secondary_color }};
            --accent: {{ $portalBrand->accent_color }};
            --background: {{ $portalBrand->background_color }};
            --text: {{ $portalBrand->text_color }};
        }
        
        .portal-primary { background-color: var(--primary); }
        .portal-text-primary { color: var(--primary); }
        .portal-border-primary { border-color: var(--primary); }
        .portal-secondary { background-color: var(--secondary); }
        .portal-text-secondary { color: var(--secondary); }
        .portal-accent { background-color: var(--accent); }
        .portal-text-accent { color: var(--accent); }
        .portal-background { background-color: var(--background); }
        .portal-text { color: var(--text); }
        
        {{ $portalBrand->custom_css }}
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-background portal-text">
    <div class="min-h-screen">
        {{-- Header --}}
        <header class="portal-primary shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        @if($portalBrand->logo_url)
                            <img src="{{ $portalBrand->logo_url }}" alt="{{ $portalBrand->name }}" class="h-8 w-auto">
                        @else
                            <h1 class="text-xl font-bold text-white">{{ $portalBrand->name }}</h1>
                        @endif
                    </div>
                    
                    <div class="text-white text-sm">
                        Client Portal
                    </div>
                </div>
            </div>
        </header>
        
        {{-- Main Content --}}
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }}
        </main>
        
        {{-- Footer --}}
        @if($portalBrand->footer_text || $portalBrand->contact_email)
            <footer class="border-t border-gray-200 mt-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="text-center space-y-4">
                        @if($portalBrand->footer_text)
                            <p class="text-gray-600">{{ $portalBrand->footer_text }}</p>
                        @endif
                        
                        @if($portalBrand->contact_email || $portalBrand->contact_phone)
                            <div class="flex justify-center gap-6 text-sm text-gray-500">
                                @if($portalBrand->contact_email)
                                    <a href="mailto:{{ $portalBrand->contact_email }}" class="portal-text-primary hover:underline">
                                        {{ $portalBrand->contact_email }}
                                    </a>
                                @endif
                                
                                @if($portalBrand->contact_phone)
                                    <a href="tel:{{ $portalBrand->contact_phone }}" class="portal-text-primary hover:underline">
                                        {{ $portalBrand->contact_phone }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </footer>
        @endif
    </div>
</body>
</html>
```

## API Endpoints

### Portal API Controller
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalBrand;
use App\Services\PortalBrandService;
use App\Services\PortalAnalyticsService;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __construct(
        protected PortalBrandService $portalBrandService,
        protected PortalAnalyticsService $portalAnalyticsService
    ) {}
    
    public function index(Request $request)
    {
        $portals = PortalBrand::where('user_id', $request->user()->id)
            ->withCount('projects')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'portals' => $portals->map(function ($portal) {
                return [
                    'id' => $portal->id,
                    'name' => $portal->name,
                    'slug' => $portal->slug,
                    'url' => $this->portalBrandService->generatePortalUrl($portal),
                    'theme' => $this->portalBrandService->getPortalTheme($portal),
                    'projects_count' => $portal->projects_count,
                    'is_domain_verified' => $portal->isDomainVerified(),
                    'created_at' => $portal->created_at,
                ];
            })
        ]);
    }
    
    public function show(PortalBrand $portal)
    {
        $this->authorize('view', $portal);
        
        return response()->json([
            'portal' => [
                'id' => $portal->id,
                'name' => $portal->name,
                'slug' => $portal->slug,
                'url' => $this->portalBrandService->generatePortalUrl($portal),
                'theme' => $this->portalBrandService->getPortalTheme($portal),
                'custom_domain' => $portal->custom_domain,
                'is_domain_verified' => $portal->isDomainVerified(),
                'welcome_message' => $portal->welcome_message,
                'contact_email' => $portal->contact_email,
                'contact_phone' => $portal->contact_phone,
                'footer_text' => $portal->footer_text,
                'created_at' => $portal->created_at,
            ]
        ]);
    }
    
    public function analytics(PortalBrand $portal, Request $request)
    {
        $this->authorize('view', $portal);
        
        $period = $request->get('period', '30d');
        $analytics = $this->portalAnalyticsService->getPortalStats($portal, $period);
        
        return response()->json(['analytics' => $analytics]);
    }
    
    public function generateClientToken(PortalBrand $portal, Request $request)
    {
        $this->authorize('view', $portal);
        
        $request->validate([
            'client_email' => 'required|email',
            'expires_in_days' => 'integer|min:1|max:365',
        ]);
        
        $payload = [
            'portal_brand_id' => $portal->id,
            'client_email' => $request->client_email,
            'iat' => time(),
            'exp' => time() + ($request->get('expires_in_days', 7) * 24 * 60 * 60),
        ];
        
        $token = \Firebase\JWT\JWT::encode($payload, config('app.key'), 'HS256');
        
        $portalUrl = $this->portalBrandService->generatePortalUrl($portal);
        $clientUrl = $portalUrl . '?token=' . $token;
        
        return response()->json([
            'token' => $token,
            'url' => $clientUrl,
            'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
        ]);
    }
}
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PortalBrand;
use App\Models\Project;
use App\Services\PortalBrandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\TestCase;

class CustomClientPortalsTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected PortalBrandService $portalBrandService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->portalBrandService = app(PortalBrandService::class);
        Storage::fake('s3');
    }
    
    public function test_user_can_create_portal_brand()
    {
        $portalData = [
            'name' => 'Test Studio Portal',
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'welcome_message' => 'Welcome to our portal',
            'contact_email' => 'contact@teststudio.com',
        ];
        
        $portal = $this->portalBrandService->createPortalBrand($this->user, $portalData);
        
        expect($portal->name)->toBe('Test Studio Portal');
        expect($portal->primary_color)->toBe('#FF0000');
        expect($portal->slug)->toBe('test-studio-portal');
        expect($portal->user_id)->toBe($this->user->id);
        
        $this->assertDatabaseHas('portal_brands', [
            'user_id' => $this->user->id,
            'name' => 'Test Studio Portal',
            'slug' => 'test-studio-portal',
        ]);
    }
    
    public function test_portal_brand_creation_with_logo_upload()
    {
        $logo = UploadedFile::fake()->image('logo.png', 300, 100);
        
        $portalData = [
            'name' => 'Test Studio',
            'logo' => $logo,
        ];
        
        $portal = $this->portalBrandService->createPortalBrand($this->user, $portalData);
        
        expect($portal->logo_url)->not->toBeNull();
        Storage::disk('s3')->assertExists("portal-logos/{$portal->slug}-logo.png");
    }
    
    public function test_custom_domain_validation_and_verification()
    {
        $portal = PortalBrand::factory()->create(['user_id' => $this->user->id]);
        
        $result = $this->portalBrandService->setCustomDomain($portal, 'portal.example.com');
        
        expect($portal->fresh()->custom_domain)->toBe('portal.example.com');
        expect($portal->fresh()->domain_verified_at)->toBeNull();
        expect($result)->toBeFalse(); // Domain verification will fail in test
    }
    
    public function test_portal_theme_generation()
    {
        $portal = PortalBrand::factory()->create([
            'user_id' => $this->user->id,
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'accent_color' => '#0000FF',
        ]);
        
        $theme = $this->portalBrandService->getPortalTheme($portal);
        
        expect($theme['colors']['primary'])->toBe('#FF0000');
        expect($theme['colors']['secondary'])->toBe('#00FF00');
        expect($theme['colors']['accent'])->toBe('#0000FF');
    }
    
    public function test_portal_projects_filtering_by_client_email()
    {
        $portal = PortalBrand::factory()->create(['user_id' => $this->user->id]);
        
        $clientProject = Project::factory()->create([
            'portal_brand_id' => $portal->id,
            'client_email' => 'client@example.com',
            'workflow_type' => 'client_management',
        ]);
        
        $otherProject = Project::factory()->create([
            'portal_brand_id' => $portal->id,
            'client_email' => 'other@example.com',
            'workflow_type' => 'client_management',
        ]);
        
        $projects = $this->portalBrandService->getPortalProjects($portal, 'client@example.com');
        
        expect($projects)->toHaveCount(1);
        expect($projects->first()->id)->toBe($clientProject->id);
    }
    
    public function test_portal_url_generation()
    {
        // Test with default subdomain
        $portal = PortalBrand::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'test-portal',
        ]);
        
        $url = $this->portalBrandService->generatePortalUrl($portal);
        expect($url)->toContain('test-portal');
        
        // Test with verified custom domain
        $portal->update([
            'custom_domain' => 'portal.example.com',
            'domain_verified_at' => now(),
        ]);
        
        $url = $this->portalBrandService->generatePortalUrl($portal);
        expect($url)->toBe('https://portal.example.com');
    }
    
    public function test_plan_limits_enforcement()
    {
        $basicUser = User::factory()->create(['subscription_plan' => 'basic']);
        
        // Create maximum allowed portals for basic plan
        PortalBrand::factory()->create(['user_id' => $basicUser->id]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plan limit reached');
        
        $this->portalBrandService->createPortalBrand($basicUser, ['name' => 'Second Portal']);
    }
    
    public function test_livewire_portal_management_component()
    {
        $this->actingAs($this->user);
        
        Livewire::test(\App\Livewire\Portal\ManagePortalBrands::class)
            ->set('name', 'Test Portal')
            ->set('primaryColor', '#FF0000')
            ->set('contactEmail', 'test@example.com')
            ->call('createPortal')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);
        
        $this->assertDatabaseHas('portal_brands', [
            'user_id' => $this->user->id,
            'name' => 'Test Portal',
            'primary_color' => '#FF0000',
        ]);
    }
    
    public function test_client_portal_view_with_token_authentication()
    {
        $portal = PortalBrand::factory()->create(['user_id' => $this->user->id]);
        
        $project = Project::factory()->create([
            'portal_brand_id' => $portal->id,
            'client_email' => 'client@example.com',
            'workflow_type' => 'client_management',
        ]);
        
        // Generate client token
        $payload = [
            'portal_brand_id' => $portal->id,
            'client_email' => 'client@example.com',
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
        ];
        
        $token = \Firebase\JWT\JWT::encode($payload, config('app.key'), 'HS256');
        
        $response = $this->get(route('portal.show', [
            'portal' => $portal->slug,
            'token' => $token
        ]));
        
        $response->assertStatus(200);
        $response->assertSee($portal->name);
        $response->assertSee($project->name);
    }
    
    public function test_portal_analytics_tracking()
    {
        $portal = PortalBrand::factory()->create(['user_id' => $this->user->id]);
        $analyticsService = app(\App\Services\PortalAnalyticsService::class);
        
        $analyticsService->trackEvent(
            $portal,
            'client@example.com',
            'visit'
        );
        
        $analyticsService->trackEvent(
            $portal,
            'client@example.com',
            'project_view',
            Project::factory()->create()
        );
        
        $stats = $analyticsService->getPortalStats($portal, '7d');
        
        expect($stats['total_visits'])->toBe(1);
        expect($stats['project_views'])->toBe(1);
        expect($stats['unique_visitors'])->toBe(1);
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\PortalBrand;
use App\Services\PortalBrandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalBrandServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected PortalBrandService $service;
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(PortalBrandService::class);
        $this->user = User::factory()->create();
    }
    
    public function test_generates_unique_slug_from_name()
    {
        $method = new \ReflectionMethod($this->service, 'generateUniqueSlug');
        $method->setAccessible(true);
        
        $slug = $method->invoke($this->service, 'Test Portal Name');
        expect($slug)->toBe('test-portal-name');
        
        // Create portal with same slug
        PortalBrand::factory()->create(['slug' => 'test-portal-name']);
        
        $slug2 = $method->invoke($this->service, 'Test Portal Name');
        expect($slug2)->toBe('test-portal-name-1');
    }
    
    public function test_validates_domain_format()
    {
        $method = new \ReflectionMethod($this->service, 'validateDomainFormat');
        $method->setAccessible(true);
        
        expect($method->invoke($this->service, 'example.com'))->toBeTrue();
        expect($method->invoke($this->service, 'sub.example.com'))->toBeTrue();
        expect($method->invoke($this->service, 'invalid domain'))->toBeFalse();
        expect($method->invoke($this->service, 'http://example.com'))->toBeFalse();
    }
    
    public function test_sanitizes_domain_input()
    {
        $method = new \ReflectionMethod($this->service, 'sanitizeDomain');
        $method->setAccessible(true);
        
        expect($method->invoke($this->service, 'EXAMPLE.COM/'))->toBe('example.com');
        expect($method->invoke($this->service, '  subdomain.example.com  '))->toBe('subdomain.example.com');
    }
    
    public function test_determines_max_brands_for_plan()
    {
        $method = new \ReflectionMethod($this->service, 'getMaxBrandsForPlan');
        $method->setAccessible(true);
        
        expect($method->invoke($this->service, 'basic'))->toBe(1);
        expect($method->invoke($this->service, 'professional'))->toBe(3);
        expect($method->invoke($this->service, 'enterprise'))->toBe(10);
        expect($method->invoke($this->service, 'unknown'))->toBe(1);
    }
    
    public function test_determines_max_projects_for_plan()
    {
        $method = new \ReflectionMethod($this->service, 'getMaxProjectsForPlan');
        $method->setAccessible(true);
        
        expect($method->invoke($this->service, 'basic'))->toBe(10);
        expect($method->invoke($this->service, 'professional'))->toBe(50);
        expect($method->invoke($this->service, 'enterprise'))->toBe(200);
        expect($method->invoke($this->service, 'unknown'))->toBe(10);
    }
}
```

## Implementation Steps

### Phase 1: Core Infrastructure (Week 1-2)
1. **Database Schema Setup**
   - Create portal_brands migration with all required fields
   - Create portal_analytics migration for tracking
   - Update projects table to include portal_brand_id
   - Run migrations and test data integrity

2. **Service Layer Development**
   - Implement PortalBrandService with CRUD operations
   - Add domain validation and verification logic
   - Implement file upload handling for logos/favicons
   - Create PortalAnalyticsService for event tracking

3. **Model Implementation**
   - Create PortalBrand model with relationships
   - Create PortalAnalytics model
   - Update Project model to include portal relationship
   - Add proper model factories for testing

### Phase 2: Portal Management UI (Week 3-4)
1. **Livewire Components**
   - Create ManagePortalBrands component for CRUD operations
   - Implement color picker and theme preview
   - Add file upload functionality for branding assets
   - Create analytics dashboard modal

2. **Blade Templates**
   - Design portal management interface with Flux UI
   - Create responsive grid layout for portal cards
   - Implement modals for create/edit operations
   - Add analytics visualization components

3. **Navigation Integration**
   - Add portal management to main navigation
   - Create portal management dashboard section
   - Add quick actions for portal creation

### Phase 3: Client Portal Views (Week 5-6)
1. **Portal Layout System**
   - Create dynamic portal layout with theme injection
   - Implement CSS variable system for colors
   - Add custom CSS support for advanced theming
   - Create responsive mobile-optimized layout

2. **Client Portal Components**
   - Build ClientPortalView Livewire component
   - Implement token-based authentication
   - Create project listing with filtering
   - Add client interaction tracking

3. **Route Configuration**
   - Set up portal routes with slug-based routing
   - Implement custom domain handling
   - Add middleware for portal authentication
   - Configure subdomain routing fallback

### Phase 4: Analytics & Security (Week 7-8)
1. **Analytics Implementation**
   - Implement event tracking across portal interactions
   - Create analytics aggregation system
   - Build analytics dashboard with charts
   - Add export functionality for analytics data

2. **Security & Performance**
   - Implement JWT token validation
   - Add rate limiting for portal access
   - Create domain verification system
   - Implement caching for portal themes

3. **Testing & Documentation**
   - Write comprehensive feature tests
   - Create unit tests for service classes
   - Add portal management documentation
   - Test cross-browser compatibility

## Security Considerations

### Domain Verification
- **DNS Validation**: Require TXT record verification for custom domains
- **SSL Enforcement**: Automatically redirect HTTP to HTTPS for custom domains
- **Domain Ownership**: Prevent domain hijacking with proper verification flows

### Token Security
- **JWT Implementation**: Use signed tokens with expiration for client access
- **Token Rotation**: Implement automatic token refresh for long-term access
- **Access Logging**: Track all portal access attempts for security monitoring

### Data Isolation
- **Client Separation**: Ensure clients only see their own project data
- **Portal Isolation**: Prevent cross-portal data leakage
- **Permission Validation**: Verify portal ownership before any operations

### File Security
- **Upload Validation**: Strict validation for logo and favicon uploads
- **File Size Limits**: Enforce reasonable file size limits for branding assets
- **Content Scanning**: Scan uploaded files for malicious content

This implementation provides a comprehensive custom client portal system that allows studios to create branded experiences for their clients while maintaining security, performance, and scalability.