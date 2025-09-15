# Team Roles & Permissions Matrix Implementation Plan

## Feature Overview

The Team Roles & Permissions Matrix provides large studios and production companies with granular access control for their projects and team members. This feature enables sophisticated team management with role-based permissions, ensuring proper workflow security and clear responsibility hierarchies.

### Core Functionality
- **Role-Based Access Control**: Predefined roles with specific permission sets
- **Granular Permissions**: Fine-tuned control over project actions and resources
- **Team Management**: Invite and manage team members across multiple projects
- **Permission Inheritance**: Hierarchical permissions with override capabilities
- **Audit Trail**: Complete logging of permission changes and access attempts
- **Multi-Project Support**: Consistent role management across studio projects

## Technical Architecture

### Database Schema

```sql
-- Team and organization management
CREATE TABLE teams (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    owner_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    team_type ENUM('studio', 'label', 'collective', 'agency') DEFAULT 'studio',
    settings JSON DEFAULT '{}',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner_active (owner_id, is_active),
    INDEX idx_team_type (team_type)
);

-- Team membership and basic roles
CREATE TABLE team_members (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    invited_by_id BIGINT UNSIGNED NULL,
    role ENUM('owner', 'admin', 'manager', 'engineer', 'assistant', 'client', 'reviewer') NOT NULL,
    status ENUM('pending', 'active', 'suspended', 'removed') DEFAULT 'pending',
    invited_at TIMESTAMP NULL,
    joined_at TIMESTAMP NULL,
    last_active_at TIMESTAMP NULL,
    metadata JSON DEFAULT '{}',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_team_member (team_id, user_id),
    INDEX idx_user_teams (user_id, status),
    INDEX idx_team_role (team_id, role, status)
);

-- Permission definitions and capabilities
CREATE TABLE permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category ENUM('project', 'team', 'file', 'comment', 'admin') NOT NULL,
    resource_type VARCHAR(50) NULL,
    is_system BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_category (category),
    INDEX idx_resource_type (resource_type)
);

-- Role definitions with permission sets
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    role_type ENUM('system', 'custom') DEFAULT 'custom',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_team_roles (team_id, is_active),
    INDEX idx_system_roles (role_type, is_active),
    UNIQUE KEY unique_team_role_name (team_id, name)
);

-- Role-permission associations
CREATE TABLE role_permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    granted BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role_permissions (role_id, granted)
);

-- Project-specific role assignments
CREATE TABLE project_team_members (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_by_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'suspended', 'removed') DEFAULT 'active',
    permissions_override JSON DEFAULT '{}',
    assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    removed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_user (project_id, user_id),
    INDEX idx_project_members (project_id, status),
    INDEX idx_user_projects (user_id, status)
);

-- Permission audit trail
CREATE TABLE permission_audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NULL,
    project_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    action_by_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('permission_granted', 'permission_revoked', 'role_assigned', 'role_removed', 'access_denied', 'access_granted') NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id BIGINT UNSIGNED NULL,
    permission_name VARCHAR(100) NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    metadata JSON DEFAULT '{}',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_team_audit (team_id, created_at),
    INDEX idx_project_audit (project_id, created_at),
    INDEX idx_user_audit (user_id, action_type, created_at)
);

-- Permission caching for performance
CREATE TABLE user_permission_cache (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    project_id BIGINT UNSIGNED NULL,
    permission_name VARCHAR(100) NOT NULL,
    granted BOOLEAN NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission_cache (user_id, team_id, project_id, permission_name),
    INDEX idx_expires (expires_at),
    INDEX idx_user_permissions (user_id, granted)
);
```

### Service Architecture

#### TeamPermissionService
```php
<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\ProjectTeamMember;
use App\Models\Role;
use App\Models\Permission;
use App\Models\PermissionAuditLog;
use App\Models\UserPermissionCache;
use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TeamPermissionService
{
    public function createTeam(User $owner, string $name, string $type = 'studio', array $options = []): Team
    {
        $team = Team::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'team_type' => $type,
            'description' => $options['description'] ?? null,
            'settings' => $options['settings'] ?? [],
            'is_active' => true
        ]);

        // Add owner as team member with owner role
        $this->addTeamMember($team, $owner, 'owner', $owner);

        // Create default roles for the team
        $this->createDefaultRoles($team);

        return $team;
    }

    public function addTeamMember(Team $team, User $user, string $role, User $invitedBy): TeamMember
    {
        // Validate role exists
        if (!in_array($role, ['owner', 'admin', 'manager', 'engineer', 'assistant', 'client', 'reviewer'])) {
            throw new \InvalidArgumentException("Invalid role: {$role}");
        }

        // Check if user is already a member
        $existingMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            throw new \InvalidArgumentException('User is already a team member');
        }

        $member = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'invited_by_id' => $invitedBy->id,
            'role' => $role,
            'status' => 'active',
            'invited_at' => now(),
            'joined_at' => now()
        ]);

        // Clear permission cache for user
        $this->clearUserPermissionCache($user->id, $team->id);

        // Log the action
        $this->logPermissionAction($team, null, $user, $invitedBy, 'role_assigned', 'team_member', $member->id, [
            'role' => $role
        ]);

        return $member;
    }

    public function assignProjectRole(Project $project, User $user, Role $role, User $assignedBy): ProjectTeamMember
    {
        // Validate user has team access
        if (!$this->userCanAccessProject($user, $project)) {
            throw new \UnauthorizedException('User does not have access to this project');
        }

        $assignment = ProjectTeamMember::updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $user->id
            ],
            [
                'role_id' => $role->id,
                'assigned_by_id' => $assignedBy->id,
                'status' => 'active',
                'assigned_at' => now()
            ]
        );

        // Clear permission cache
        $this->clearUserPermissionCache($user->id, null, $project->id);

        // Log the action
        $this->logPermissionAction(null, $project, $user, $assignedBy, 'role_assigned', 'project_member', $assignment->id, [
            'role_id' => $role->id,
            'role_name' => $role->display_name
        ]);

        return $assignment;
    }

    public function hasPermission(User $user, string $permission, ?Project $project = null, ?Team $team = null): bool
    {
        // Check cache first
        $cacheKey = $this->getPermissionCacheKey($user->id, $permission, $project?->id, $team?->id);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $hasPermission = $this->evaluatePermission($user, $permission, $project, $team);

        // Cache the result
        Cache::put($cacheKey, $hasPermission, now()->addMinutes(15));

        return $hasPermission;
    }

    private function evaluatePermission(User $user, string $permission, ?Project $project, ?Team $team): bool
    {
        // System admin override
        if ($user->hasRole('admin')) {
            return true;
        }

        // Project-specific permissions
        if ($project) {
            return $this->evaluateProjectPermission($user, $permission, $project);
        }

        // Team-specific permissions
        if ($team) {
            return $this->evaluateTeamPermission($user, $permission, $team);
        }

        // Global permissions
        return $this->evaluateGlobalPermission($user, $permission);
    }

    private function evaluateProjectPermission(User $user, string $permission, Project $project): bool
    {
        // Check if user is project owner
        if ($project->user_id === $user->id) {
            return true;
        }

        // Check project team member role
        $projectMember = ProjectTeamMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with('role.permissions')
            ->first();

        if ($projectMember) {
            // Check permission overrides first
            $overrides = $projectMember->permissions_override;
            if (isset($overrides[$permission])) {
                return $overrides[$permission];
            }

            // Check role permissions
            $rolePermissions = $projectMember->role->permissions->pluck('name')->toArray();
            if (in_array($permission, $rolePermissions)) {
                return true;
            }
        }

        // Fall back to team permissions if project belongs to a team
        if ($project->team_id) {
            return $this->evaluateTeamPermission($user, $permission, $project->team);
        }

        return false;
    }

    private function evaluateTeamPermission(User $user, string $permission, Team $team): bool
    {
        // Check if user is team owner
        if ($team->owner_id === $user->id) {
            return true;
        }

        // Check team member role
        $teamMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$teamMember) {
            return false;
        }

        // Get default role permissions for team member role
        $defaultRole = $this->getDefaultRoleForTeamMemberRole($team, $teamMember->role);
        if ($defaultRole) {
            $rolePermissions = $defaultRole->permissions->pluck('name')->toArray();
            return in_array($permission, $rolePermissions);
        }

        return false;
    }

    private function evaluateGlobalPermission(User $user, string $permission): bool
    {
        // Check user's global roles and permissions
        // This would integrate with existing user role system
        return false;
    }

    public function createCustomRole(Team $team, string $name, string $displayName, array $permissions): Role
    {
        $role = Role::create([
            'team_id' => $team->id,
            'name' => $name,
            'display_name' => $displayName,
            'role_type' => 'custom',
            'is_active' => true
        ]);

        // Attach permissions
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        foreach ($permissionIds as $permissionId) {
            $role->permissions()->attach($permissionId, ['granted' => true]);
        }

        // Clear relevant caches
        $this->clearTeamPermissionCaches($team->id);

        return $role;
    }

    public function updateRolePermissions(Role $role, array $permissions): void
    {
        // Detach all current permissions
        $role->permissions()->detach();

        // Attach new permissions
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        foreach ($permissionIds as $permissionId) {
            $role->permissions()->attach($permissionId, ['granted' => true]);
        }

        // Clear relevant caches
        if ($role->team_id) {
            $this->clearTeamPermissionCaches($role->team_id);
        }
    }

    public function getTeamPermissionsMatrix(Team $team): array
    {
        $roles = Role::where('team_id', $team->id)
            ->where('is_active', true)
            ->with('permissions')
            ->orderBy('sort_order')
            ->get();

        $permissions = Permission::orderBy('category')
            ->orderBy('display_name')
            ->get()
            ->groupBy('category');

        $matrix = [];
        foreach ($permissions as $category => $categoryPermissions) {
            $matrix[$category] = [];
            foreach ($categoryPermissions as $permission) {
                $matrix[$category][$permission->name] = [
                    'permission' => $permission,
                    'roles' => []
                ];

                foreach ($roles as $role) {
                    $hasPermission = $role->permissions->contains('name', $permission->name);
                    $matrix[$category][$permission->name]['roles'][$role->id] = $hasPermission;
                }
            }
        }

        return [
            'matrix' => $matrix,
            'roles' => $roles,
            'permissions' => $permissions->flatten()
        ];
    }

    private function createDefaultRoles(Team $team): void
    {
        $defaultRoles = [
            'admin' => [
                'display_name' => 'Administrator',
                'permissions' => [
                    'team.manage', 'team.invite', 'team.remove_members',
                    'project.create', 'project.edit', 'project.delete', 'project.manage_team',
                    'file.upload', 'file.download', 'file.delete',
                    'comment.create', 'comment.edit', 'comment.delete'
                ]
            ],
            'manager' => [
                'display_name' => 'Project Manager',
                'permissions' => [
                    'project.create', 'project.edit', 'project.manage_team',
                    'file.upload', 'file.download',
                    'comment.create', 'comment.edit'
                ]
            ],
            'engineer' => [
                'display_name' => 'Audio Engineer',
                'permissions' => [
                    'project.view', 'project.edit',
                    'file.upload', 'file.download',
                    'comment.create'
                ]
            ],
            'assistant' => [
                'display_name' => 'Assistant',
                'permissions' => [
                    'project.view',
                    'file.download',
                    'comment.create'
                ]
            ],
            'client' => [
                'display_name' => 'Client',
                'permissions' => [
                    'project.view',
                    'file.download',
                    'comment.create'
                ]
            ]
        ];

        foreach ($defaultRoles as $name => $roleData) {
            $role = Role::create([
                'team_id' => $team->id,
                'name' => $name,
                'display_name' => $roleData['display_name'],
                'role_type' => 'system',
                'is_active' => true
            ]);

            // Attach permissions
            $permissionIds = Permission::whereIn('name', $roleData['permissions'])->pluck('id');
            foreach ($permissionIds as $permissionId) {
                $role->permissions()->attach($permissionId, ['granted' => true]);
            }
        }
    }

    private function getDefaultRoleForTeamMemberRole(Team $team, string $memberRole): ?Role
    {
        return Role::where('team_id', $team->id)
            ->where('name', $memberRole)
            ->where('role_type', 'system')
            ->where('is_active', true)
            ->with('permissions')
            ->first();
    }

    private function userCanAccessProject(User $user, Project $project): bool
    {
        // Project owner
        if ($project->user_id === $user->id) {
            return true;
        }

        // Team member if project belongs to team
        if ($project->team_id) {
            return TeamMember::where('team_id', $project->team_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    private function logPermissionAction(
        ?Team $team,
        ?Project $project,
        User $user,
        User $actionBy,
        string $actionType,
        string $resourceType,
        ?int $resourceId,
        array $metadata = []
    ): void {
        PermissionAuditLog::create([
            'team_id' => $team?->id,
            'project_id' => $project?->id,
            'user_id' => $user->id,
            'action_by_id' => $actionBy->id,
            'action_type' => $actionType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    private function clearUserPermissionCache(int $userId, ?int $teamId = null, ?int $projectId = null): void
    {
        $query = UserPermissionCache::where('user_id', $userId);
        
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        
        $query->delete();

        // Also clear Laravel cache
        $pattern = "permission_cache_{$userId}_*";
        if ($teamId) {
            $pattern = "permission_cache_{$userId}_{$teamId}_*";
        }
        if ($projectId) {
            $pattern = "permission_cache_{$userId}_{$teamId}_{$projectId}_*";
        }
        
        Cache::flush(); // In production, use more targeted cache clearing
    }

    private function clearTeamPermissionCaches(int $teamId): void
    {
        UserPermissionCache::where('team_id', $teamId)->delete();
        Cache::flush(); // In production, use more targeted cache clearing
    }

    private function getPermissionCacheKey(int $userId, string $permission, ?int $projectId, ?int $teamId): string
    {
        return "permission_cache_{$userId}_{$teamId}_{$projectId}_{$permission}";
    }

    public function getSystemPermissions(): array
    {
        return [
            'team' => [
                'team.manage' => 'Manage Team Settings',
                'team.invite' => 'Invite Team Members',
                'team.remove_members' => 'Remove Team Members',
                'team.view_audit' => 'View Audit Logs'
            ],
            'project' => [
                'project.create' => 'Create Projects',
                'project.view' => 'View Projects',
                'project.edit' => 'Edit Projects',
                'project.delete' => 'Delete Projects',
                'project.manage_team' => 'Manage Project Team',
                'project.archive' => 'Archive Projects'
            ],
            'file' => [
                'file.upload' => 'Upload Files',
                'file.download' => 'Download Files',
                'file.delete' => 'Delete Files',
                'file.manage' => 'Manage All Files'
            ],
            'comment' => [
                'comment.create' => 'Create Comments',
                'comment.edit' => 'Edit Comments',
                'comment.delete' => 'Delete Comments',
                'comment.moderate' => 'Moderate Comments'
            ],
            'admin' => [
                'admin.billing' => 'Manage Billing',
                'admin.integrations' => 'Manage Integrations',
                'admin.analytics' => 'View Analytics'
            ]
        ];
    }
}
```

## UI Implementation

### Team Management Component
```php
<?php

namespace App\Livewire\Team;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\TeamPermissionService;
use Livewire\Component;
use Livewire\WithPagination;

class TeamManagement extends Component
{
    use WithPagination;

    public Team $team;
    public string $inviteEmail = '';
    public string $inviteRole = 'engineer';
    public bool $showInviteModal = false;
    public bool $showPermissionsModal = false;
    public array $availableRoles = [
        'admin' => 'Administrator',
        'manager' => 'Project Manager', 
        'engineer' => 'Audio Engineer',
        'assistant' => 'Assistant',
        'client' => 'Client',
        'reviewer' => 'Reviewer'
    ];

    protected $rules = [
        'inviteEmail' => 'required|email',
        'inviteRole' => 'required|in:admin,manager,engineer,assistant,client,reviewer'
    ];

    public function mount(Team $team)
    {
        $this->team = $team;
    }

    public function inviteUser(TeamPermissionService $permissionService)
    {
        $this->validate();

        // Check if user exists
        $user = User::where('email', $this->inviteEmail)->first();
        
        if (!$user) {
            $this->addError('inviteEmail', 'No user found with this email address.');
            return;
        }

        // Check if already a member
        $existingMember = TeamMember::where('team_id', $this->team->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            $this->addError('inviteEmail', 'User is already a team member.');
            return;
        }

        try {
            $permissionService->addTeamMember($this->team, $user, $this->inviteRole, auth()->user());
            
            $this->reset(['inviteEmail', 'inviteRole', 'showInviteModal']);
            $this->resetPage();
            
            $this->dispatch('member-invited', [
                'message' => "Successfully added {$user->name} to the team."
            ]);
            
        } catch (\Exception $e) {
            $this->addError('inviteEmail', $e->getMessage());
        }
    }

    public function updateMemberRole(int $memberId, string $newRole, TeamPermissionService $permissionService)
    {
        $member = TeamMember::where('team_id', $this->team->id)->findOrFail($memberId);
        
        $oldRole = $member->role;
        $member->update(['role' => $newRole]);
        
        // Log the change
        $permissionService->logPermissionAction(
            $this->team,
            null,
            $member->user,
            auth()->user(),
            'role_assigned',
            'team_member',
            $member->id,
            ['old_role' => $oldRole, 'new_role' => $newRole]
        );

        $this->dispatch('member-updated', [
            'message' => "Updated {$member->user->name}'s role to {$this->availableRoles[$newRole]}."
        ]);
    }

    public function removeMember(int $memberId)
    {
        $member = TeamMember::where('team_id', $this->team->id)->findOrFail($memberId);
        
        if ($member->role === 'owner') {
            $this->addError('general', 'Cannot remove team owner.');
            return;
        }

        $member->update(['status' => 'removed', 'removed_at' => now()]);
        
        $this->dispatch('member-removed', [
            'message' => "Removed {$member->user->name} from the team."
        ]);
    }

    public function render()
    {
        $members = TeamMember::where('team_id', $this->team->id)
            ->where('status', 'active')
            ->with('user')
            ->orderBy('role')
            ->orderBy('joined_at')
            ->paginate(20);

        return view('livewire.team.management', [
            'members' => $members
        ]);
    }
}
```

### Permissions Matrix Component
```php
<?php

namespace App\Livewire\Team;

use App\Models\Team;
use App\Models\Role;
use App\Models\Permission;
use App\Services\TeamPermissionService;
use Livewire\Component;

class PermissionsMatrix extends Component
{
    public Team $team;
    public array $permissionsMatrix = [];
    public array $roles = [];
    public bool $showCreateRoleModal = false;
    public array $roleForm = [
        'name' => '',
        'display_name' => '',
        'description' => '',
        'permissions' => []
    ];

    protected $rules = [
        'roleForm.name' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
        'roleForm.display_name' => 'required|string|max:255',
        'roleForm.description' => 'nullable|string|max:500',
        'roleForm.permissions' => 'array'
    ];

    public function mount(Team $team, TeamPermissionService $permissionService)
    {
        $this->team = $team;
        $this->loadPermissionsMatrix($permissionService);
    }

    public function togglePermission(int $roleId, string $permissionName, TeamPermissionService $permissionService)
    {
        $role = Role::where('team_id', $this->team->id)->findOrFail($roleId);
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        $rolePermission = $role->permissions()->where('permission_id', $permission->id)->first();

        if ($rolePermission) {
            // Remove permission
            $role->permissions()->detach($permission->id);
        } else {
            // Add permission
            $role->permissions()->attach($permission->id, ['granted' => true]);
        }

        // Clear caches
        $permissionService->clearTeamPermissionCaches($this->team->id);
        
        // Reload matrix
        $this->loadPermissionsMatrix($permissionService);

        $this->dispatch('permission-updated', [
            'message' => 'Permission updated successfully.'
        ]);
    }

    public function createCustomRole(TeamPermissionService $permissionService)
    {
        $this->validate();

        try {
            $permissionService->createCustomRole(
                $this->team,
                $this->roleForm['name'],
                $this->roleForm['display_name'],
                $this->roleForm['permissions']
            );

            $this->reset(['showCreateRoleModal', 'roleForm']);
            $this->loadPermissionsMatrix($permissionService);

            $this->dispatch('role-created', [
                'message' => 'Custom role created successfully.'
            ]);

        } catch (\Exception $e) {
            $this->addError('roleForm.name', $e->getMessage());
        }
    }

    public function deleteRole(int $roleId, TeamPermissionService $permissionService)
    {
        $role = Role::where('team_id', $this->team->id)
            ->where('role_type', 'custom')
            ->findOrFail($roleId);

        // Check if role is in use
        $inUse = $role->projectTeamMembers()->exists();
        
        if ($inUse) {
            $this->addError('general', 'Cannot delete role that is currently assigned to team members.');
            return;
        }

        $role->delete();
        $this->loadPermissionsMatrix($permissionService);

        $this->dispatch('role-deleted', [
            'message' => 'Role deleted successfully.'
        ]);
    }

    private function loadPermissionsMatrix(TeamPermissionService $permissionService)
    {
        $matrixData = $permissionService->getTeamPermissionsMatrix($this->team);
        
        $this->permissionsMatrix = $matrixData['matrix'];
        $this->roles = $matrixData['roles']->toArray();
    }

    public function render()
    {
        $availablePermissions = Permission::orderBy('category')
            ->orderBy('display_name')
            ->get()
            ->groupBy('category');

        return view('livewire.team.permissions-matrix', [
            'availablePermissions' => $availablePermissions
        ]);
    }
}
```

### Blade Templates

#### Team Management Template
```blade
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ $team->name }} Team</flux:heading>
            <flux:text variant="muted">
                Manage team members and their roles
            </flux:text>
        </div>
        
        <div class="flex items-center space-x-3">
            <flux:button 
                wire:click="$set('showPermissionsModal', true)" 
                variant="outline"
            >
                <flux:icon icon="shield-check" class="w-4 h-4" />
                Manage Permissions
            </flux:button>
            
            <flux:button 
                wire:click="$set('showInviteModal', true)" 
                variant="primary"
            >
                <flux:icon icon="user-plus" class="w-4 h-4" />
                Invite Member
            </flux:button>
        </div>
    </div>

    {{-- Team Members Table --}}
    <flux:card>
        <flux:table>
            <flux:table.header>
                <flux:table.row>
                    <flux:table.cell>Member</flux:table.cell>
                    <flux:table.cell>Role</flux:table.cell>
                    <flux:table.cell>Joined</flux:table.cell>
                    <flux:table.cell>Last Active</flux:table.cell>
                    <flux:table.cell>Actions</flux:table.cell>
                </flux:table.row>
            </flux:table.header>
            
            <flux:table.body>
                @forelse($members as $member)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center space-x-3">
                                <flux:avatar 
                                    src="{{ $member->user->avatar_url }}" 
                                    alt="{{ $member->user->name }}"
                                    size="sm"
                                />
                                <div>
                                    <div class="font-medium">{{ $member->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $member->user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($member->role === 'owner')
                                <flux:badge variant="success">Owner</flux:badge>
                            @else
                                <flux:select 
                                    wire:change="updateMemberRole({{ $member->id }}, $event.target.value)"
                                    size="sm"
                                >
                                    @foreach($availableRoles as $role => $label)
                                        @if($role !== 'owner')
                                            <option 
                                                value="{{ $role }}" 
                                                {{ $member->role === $role ? 'selected' : '' }}
                                            >
                                                {{ $label }}
                                            </option>
                                        @endif
                                    @endforeach
                                </flux:select>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ $member->joined_at?->format('M j, Y') ?? 'Pending' }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($member->last_active_at)
                                {{ $member->last_active_at->diffForHumans() }}
                            @else
                                <span class="text-gray-400">Never</span>
                            @endif
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @if($member->role !== 'owner')
                                <flux:button 
                                    wire:click="removeMember({{ $member->id }})"
                                    wire:confirm="Are you sure you want to remove {{ $member->user->name }} from the team?"
                                    variant="danger" 
                                    size="xs"
                                >
                                    <flux:icon icon="user-minus" class="w-4 h-4" />
                                    Remove
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-gray-500 py-8">
                            No team members found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.body>
        </flux:table>
        
        {{ $members->links() }}
    </flux:card>

    {{-- Invite Member Modal --}}
    @if($showInviteModal)
        <flux:modal wire:model="showInviteModal">
            <flux:modal.header>
                <flux:heading>Invite Team Member</flux:heading>
            </flux:modal.header>
            
            <flux:modal.body>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Email Address</flux:label>
                        <flux:input 
                            wire:model="inviteEmail" 
                            type="email"
                            placeholder="Enter email address"
                        />
                        <flux:error name="inviteEmail" />
                        <flux:description>
                            User must already have a MixPitch account
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Role</flux:label>
                        <flux:select wire:model="inviteRole">
                            @foreach($availableRoles as $role => $label)
                                @if($role !== 'owner')
                                    <option value="{{ $role }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </flux:select>
                        <flux:error name="inviteRole" />
                    </flux:field>
                </div>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button 
                    wire:click="$set('showInviteModal', false)" 
                    variant="outline"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    wire:click="inviteUser" 
                    variant="primary"
                >
                    Send Invitation
                </flux:button>
            </flux:modal.footer>
        </flux:modal>
    @endif
</div>

@script
<script>
    $wire.on('member-invited', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('member-updated', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('member-removed', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });
</script>
@endscript
```

#### Permissions Matrix Template
```blade
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">Permissions Matrix</flux:heading>
            <flux:text variant="muted">
                Configure role-based permissions for your team
            </flux:text>
        </div>
        
        <flux:button 
            wire:click="$set('showCreateRoleModal', true)" 
            variant="primary"
        >
            <flux:icon icon="plus" class="w-4 h-4" />
            Create Custom Role
        </flux:button>
    </div>

    {{-- Permissions Matrix Table --}}
    <flux:card>
        <flux:card.body class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-3 font-medium">Permission</th>
                        @foreach($roles as $role)
                            <th class="text-center p-3 font-medium min-w-[120px]">
                                <div>{{ $role['display_name'] }}</div>
                                @if($role['role_type'] === 'custom')
                                    <flux:button 
                                        wire:click="deleteRole({{ $role['id'] }})"
                                        wire:confirm="Are you sure you want to delete this role?"
                                        variant="danger" 
                                        size="xs" 
                                        class="mt-1"
                                    >
                                        Delete
                                    </flux:button>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissionsMatrix as $category => $permissions)
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <td colspan="{{ count($roles) + 1 }}" class="p-3 font-medium text-gray-900 dark:text-gray-100">
                                {{ ucfirst($category) }} Permissions
                            </td>
                        </tr>
                        @foreach($permissions as $permissionName => $permissionData)
                            <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="p-3">
                                    <div class="font-medium">{{ $permissionData['permission']['display_name'] }}</div>
                                    @if($permissionData['permission']['description'])
                                        <div class="text-gray-500 text-xs">{{ $permissionData['permission']['description'] }}</div>
                                    @endif
                                </td>
                                @foreach($roles as $role)
                                    <td class="p-3 text-center">
                                        @if($role['role_type'] === 'system' && in_array($role['name'], ['owner', 'admin']))
                                            {{-- Always granted for owner/admin --}}
                                            <flux:icon icon="check" class="w-5 h-5 text-green-500 mx-auto" />
                                        @else
                                            <input 
                                                type="checkbox" 
                                                wire:click="togglePermission({{ $role['id'] }}, '{{ $permissionName }}')"
                                                {{ $permissionData['roles'][$role['id']] ? 'checked' : '' }}
                                                class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                                            >
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </flux:card.body>
    </flux:card>

    {{-- Create Custom Role Modal --}}
    @if($showCreateRoleModal)
        <flux:modal wire:model="showCreateRoleModal" size="xl">
            <flux:modal.header>
                <flux:heading>Create Custom Role</flux:heading>
            </flux:modal.header>
            
            <flux:modal.body>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Role Name (slug)</flux:label>
                            <flux:input 
                                wire:model="roleForm.name" 
                                placeholder="e.g., senior_engineer"
                            />
                            <flux:error name="roleForm.name" />
                            <flux:description>
                                Lowercase letters, numbers, and underscores only
                            </flux:description>
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Display Name</flux:label>
                            <flux:input 
                                wire:model="roleForm.display_name" 
                                placeholder="e.g., Senior Engineer"
                            />
                            <flux:error name="roleForm.display_name" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea 
                            wire:model="roleForm.description" 
                            placeholder="Describe this role's responsibilities..."
                            rows="2"
                        />
                        <flux:error name="roleForm.description" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Permissions</flux:label>
                        <div class="space-y-4 max-h-64 overflow-y-auto border rounded-lg p-4">
                            @foreach($availablePermissions as $category => $permissions)
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        {{ ucfirst($category) }}
                                    </h4>
                                    <div class="space-y-2 ml-4">
                                        @foreach($permissions as $permission)
                                            <label class="flex items-center">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model="roleForm.permissions" 
                                                    value="{{ $permission->name }}"
                                                    class="mr-2"
                                                >
                                                <span class="text-sm">{{ $permission->display_name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <flux:error name="roleForm.permissions" />
                    </flux:field>
                </div>
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button 
                    wire:click="$set('showCreateRoleModal', false)" 
                    variant="outline"
                >
                    Cancel
                </flux:button>
                <flux:button 
                    wire:click="createCustomRole" 
                    variant="primary"
                >
                    Create Role
                </flux:button>
            </flux:modal.footer>
        </flux:modal>
    @endif
</div>

@script
<script>
    $wire.on('permission-updated', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('role-created', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });

    $wire.on('role-deleted', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: 'success', message: data.message }
        }));
    });
</script>
@endscript
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature\TeamPermissions;

use App\Models\User;
use App\Models\Team;
use App\Models\Project;
use App\Models\Permission;
use App\Services\TeamPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_owner_can_add_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $service = new TeamPermissionService();

        $team = $service->createTeam($owner, 'Test Studio', 'studio');
        $teamMember = $service->addTeamMember($team, $member, 'engineer', $owner);

        $this->assertEquals($team->id, $teamMember->team_id);
        $this->assertEquals($member->id, $teamMember->user_id);
        $this->assertEquals('engineer', $teamMember->role);
        $this->assertEquals('active', $teamMember->status);
    }

    public function test_permission_evaluation_for_project_access(): void
    {
        $owner = User::factory()->create();
        $engineer = User::factory()->create();
        $service = new TeamPermissionService();

        $team = $service->createTeam($owner, 'Test Studio');
        $service->addTeamMember($team, $engineer, 'engineer', $owner);

        $project = Project::factory()->for($owner)->create(['team_id' => $team->id]);

        // Engineer should have project view permission
        $this->assertTrue($service->hasPermission($engineer, 'project.view', $project));
        
        // Engineer should not have project delete permission
        $this->assertFalse($service->hasPermission($engineer, 'project.delete', $project));
    }

    public function test_custom_role_creation_and_permissions(): void
    {
        $owner = User::factory()->create();
        $service = new TeamPermissionService();

        $team = $service->createTeam($owner, 'Test Studio');
        
        $customRole = $service->createCustomRole(
            $team,
            'lead_engineer',
            'Lead Engineer',
            ['project.create', 'project.edit', 'file.upload', 'file.download']
        );

        $this->assertEquals('lead_engineer', $customRole->name);
        $this->assertEquals('custom', $customRole->role_type);
        $this->assertCount(4, $customRole->permissions);
    }

    public function test_project_role_assignment(): void
    {
        $owner = User::factory()->create();
        $engineer = User::factory()->create();
        $service = new TeamPermissionService();

        $team = $service->createTeam($owner, 'Test Studio');
        $service->addTeamMember($team, $engineer, 'engineer', $owner);

        $project = Project::factory()->for($owner)->create(['team_id' => $team->id]);
        $role = $team->roles()->where('name', 'manager')->first();

        $assignment = $service->assignProjectRole($project, $engineer, $role, $owner);

        $this->assertEquals($project->id, $assignment->project_id);
        $this->assertEquals($engineer->id, $assignment->user_id);
        $this->assertEquals($role->id, $assignment->role_id);
    }

    public function test_permission_caching(): void
    {
        $owner = User::factory()->create();
        $engineer = User::factory()->create();
        $service = new TeamPermissionService();

        $team = $service->createTeam($owner, 'Test Studio');
        $service->addTeamMember($team, $engineer, 'engineer', $owner);

        $project = Project::factory()->for($owner)->create(['team_id' => $team->id]);

        // First call should hit database
        $result1 = $service->hasPermission($engineer, 'project.view', $project);
        
        // Second call should hit cache
        $result2 = $service->hasPermission($engineer, 'project.view', $project);

        $this->assertEquals($result1, $result2);
        $this->assertTrue($result1);
    }

    public function test_permission_audit_logging(): void
    {
        $owner = User::factory()->create();
        $engineer = User::factory()->create();
        $service = new TeamPermissionService();

        $team = $service->createTeam($owner, 'Test Studio');
        $service->addTeamMember($team, $engineer, 'engineer', $owner);

        $this->assertDatabaseHas('permission_audit_logs', [
            'team_id' => $team->id,
            'user_id' => $engineer->id,
            'action_by_id' => $owner->id,
            'action_type' => 'role_assigned',
            'resource_type' => 'team_member'
        ]);
    }

    public function test_unauthorized_access_is_denied(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $service = new TeamPermissionService();

        $team = $service->createTeam($owner, 'Test Studio');
        $project = Project::factory()->for($owner)->create(['team_id' => $team->id]);

        // Outsider should not have any permissions
        $this->assertFalse($service->hasPermission($outsider, 'project.view', $project));
        $this->assertFalse($service->hasPermission($outsider, 'file.upload', $project));
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Role;
use App\Models\Permission;
use App\Services\TeamPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_default_roles_for_team(): void
    {
        $service = new TeamPermissionService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createDefaultRoles');
        $method->setAccessible(true);

        $team = Team::factory()->create();
        $method->invoke($service, $team);

        $roles = Role::where('team_id', $team->id)->get();
        
        $this->assertGreaterThan(0, $roles->count());
        $this->assertTrue($roles->contains('name', 'admin'));
        $this->assertTrue($roles->contains('name', 'engineer'));
        $this->assertTrue($roles->contains('name', 'client'));
    }

    public function test_permission_cache_key_generation(): void
    {
        $service = new TeamPermissionService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getPermissionCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($service, 123, 'project.view', 456, 789);
        
        $this->assertEquals('permission_cache_123_789_456_project.view', $cacheKey);
    }

    public function test_system_permissions_structure(): void
    {
        $service = new TeamPermissionService();
        $permissions = $service->getSystemPermissions();

        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('team', $permissions);
        $this->assertArrayHasKey('project', $permissions);
        $this->assertArrayHasKey('file', $permissions);
        
        $this->assertArrayHasKey('team.manage', $permissions['team']);
        $this->assertArrayHasKey('project.create', $permissions['project']);
    }

    public function test_validates_role_names(): void
    {
        $service = new TeamPermissionService();
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $invitedBy = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role: invalid_role');

        $service->addTeamMember($team, $user, 'invalid_role', $invitedBy);
    }

    public function test_prevents_duplicate_team_membership(): void
    {
        $service = new TeamPermissionService();
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $invitedBy = User::factory()->create();

        // Add user once
        $service->addTeamMember($team, $user, 'engineer', $invitedBy);

        // Attempt to add again
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User is already a team member');

        $service->addTeamMember($team, $user, 'admin', $invitedBy);
    }
}
```

## Implementation Steps

### Phase 1: Core Permission System (Week 1)
1. **Database Setup**
   - Create team and permission tables
   - Seed system permissions and default roles
   - Set up audit logging infrastructure

2. **Permission Service**
   - Implement core permission evaluation logic
   - Add role-based access control
   - Create permission caching system

3. **Team Management**
   - Team creation and member management
   - Role assignment and updates
   - Basic permission enforcement

### Phase 2: UI Implementation (Week 2)
1. **Team Management Interface**
   - Member invitation and role management
   - Team overview and statistics
   - Member activity tracking

2. **Permissions Matrix**
   - Visual permission management grid
   - Custom role creation and editing
   - Bulk permission operations

3. **Integration Points**
   - Connect with existing project workflow
   - Add permission checks to controllers
   - Update existing UI with role-based visibility

### Phase 3: Advanced Features (Week 3)
1. **Project-Specific Permissions**
   - Project team member assignments
   - Permission overrides and exceptions
   - Hierarchical permission inheritance

2. **Audit and Monitoring**
   - Comprehensive audit trail
   - Permission usage analytics
   - Security monitoring and alerts

3. **Performance Optimization**
   - Permission caching strategies
   - Database query optimization
   - Bulk permission operations

### Phase 4: Polish and Documentation (Week 4)
1. **Advanced Permission Features**
   - Conditional permissions
   - Time-based access control
   - Resource-specific permissions

2. **User Experience Improvements**
   - Permission preview and simulation
   - Guided permission setup
   - Role templates and presets

3. **Documentation and Training**
   - Admin documentation
   - Permission best practices
   - User training materials

## Security Considerations

### Access Control
- **Principle of Least Privilege**: Default to minimal permissions with explicit grants
- **Role Separation**: Clear separation between administrative and operational roles
- **Permission Validation**: Server-side validation for all permission checks
- **Audit Trail**: Complete logging of all permission changes and access attempts

### Data Protection
- **Permission Caching**: Secure caching with automatic invalidation
- **Session Management**: Integration with existing authentication system
- **API Security**: Permission enforcement for all API endpoints
- **Multi-tenancy**: Proper isolation between teams and organizations

### System Integrity
- **Default Roles**: Protected system roles that cannot be modified
- **Owner Protection**: Safeguards against removing team owners
- **Cascade Deletion**: Proper cleanup when teams or users are removed
- **Backup and Recovery**: Permission configuration backup and restoration

This comprehensive implementation plan provides enterprise-grade team management and permission control while maintaining MixPitch's focus on creative workflow efficiency.