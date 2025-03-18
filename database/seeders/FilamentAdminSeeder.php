<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class FilamentAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create permissions
        $permissions = [
            // Filament access permissions
            'access_filament',
            
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Project management
            'view_projects',
            'create_projects',
            'edit_projects',
            'delete_projects',
            
            // Pitch management
            'view_pitches',
            'create_pitches',
            'edit_pitches',
            'delete_pitches',
            
            // Settings management
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles
        $adminRole = Role::findOrCreate('admin');
        $editorRole = Role::findOrCreate('editor');
        $moderatorRole = Role::findOrCreate('moderator');

        // Assign all permissions to admin role
        $adminRole->syncPermissions(Permission::all());
        
        // Assign limited permissions to editor role
        $editorRole->syncPermissions([
            'access_filament',
            'view_projects',
            'create_projects',
            'edit_projects',
            'view_pitches',
            'create_pitches',
            'edit_pitches',
        ]);
        
        // Assign limited permissions to moderator role
        $moderatorRole->syncPermissions([
            'access_filament',
            'view_users',
            'view_projects',
            'view_pitches',
            'edit_pitches',
        ]);

        // Assign admin role to the first user or a specific user
        $adminUser = User::find(1); // Usually the first user in the system
        
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }
    }
} 