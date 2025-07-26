<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'admin.access' => 'Toegang tot admin panel',
            'users.view' => 'Gebruikers bekijken',
            'users.create' => 'Gebruikers aanmaken',
            'users.edit' => 'Gebruikers bewerken',
            'users.delete' => 'Gebruikers verwijderen',
            'roles.view' => 'Rollen bekijken',
            'roles.create' => 'Rollen aanmaken',
            'roles.edit' => 'Rollen bewerken',
            'roles.delete' => 'Rollen verwijderen',
            'permissions.view' => 'Permissies bekijken',
            'permissions.create' => 'Permissies aanmaken',
            'permissions.edit' => 'Permissies bewerken',
            'permissions.delete' => 'Permissies verwijderen',
            'collections.view' => 'Collecties bekijken',
            'collections.create' => 'Collecties aanmaken',
            'collections.edit' => 'Collecties bewerken',
            'collections.delete' => 'Collecties verwijderen',
            'collections.view_all' => 'Alle collecties bekijken',
            'collections.manage_all' => 'Alle collecties beheren',
        ];

        foreach ($permissions as $name => $description) {
            Permission::create([
                'name' => $name,
                'guard_name' => 'web',
                'display_name' => $description,
                'description' => $description,
            ]);
        }

        // Create roles
        $roles = [
            'admin' => 'Administrator',
            'moderator' => 'Moderator',
            'user' => 'Gebruiker',
        ];

        foreach ($roles as $name => $display_name) {
            Role::create([
                'name' => $name,
                'guard_name' => 'web',
                'display_name' => $display_name,
            ]);
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        $moderatorRole = Role::where('name', 'moderator')->first();
        $userRole = Role::where('name', 'user')->first();

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Moderator gets most permissions except admin access
        $moderatorPermissions = Permission::whereNotIn('name', ['admin.access'])->get();
        $moderatorRole->givePermissionTo($moderatorPermissions);

        // User gets basic permissions
        $userPermissions = Permission::whereIn('name', [
            'collections.view',
            'collections.create',
            'collections.edit',
            'collections.delete'
        ])->get();
        $userRole->givePermissionTo($userPermissions);

        // Create default admin user
        $adminUser = User::create([
            'name' => 'Administrator',
            'email' => 'admin@collectionmanager.local',
            'password' => Hash::make('admin123'),
        ]);

        // Assign admin role to admin user
        $adminUser->assignRole($adminRole);

        // Create a test user
        $testUser = User::create([
            'name' => 'Test Gebruiker',
            'email' => 'test@collectionmanager.local',
            'password' => Hash::make('test123'),
        ]);

        // Assign user role to test user
        $testUser->assignRole($userRole);
    }
}
