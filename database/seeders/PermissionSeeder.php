<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'user_manage', 'description' => 'Manage users (create, edit, delete)'],
            ['name' => 'group_manage', 'description' => 'Manage groups (create, edit, delete)'],
            ['name' => 'permission_manage', 'description' => 'Manage permissions (create, edit, delete)'],
            ['name' => 'collection_manage', 'description' => 'Manage collections (create, edit, delete)'],
            ['name' => 'collection_view', 'description' => 'View collections'],
            ['name' => 'collection_edit', 'description' => 'Edit collections'],
            ['name' => 'collection_delete', 'description' => 'Delete collections'],
            ['name' => 'item_manage', 'description' => 'Manage collection items (create, edit, delete)'],
            ['name' => 'item_view', 'description' => 'View collection items'],
            ['name' => 'item_edit', 'description' => 'Edit collection items'],
            ['name' => 'item_delete', 'description' => 'Delete collection items'],
            ['name' => 'system_settings', 'description' => 'Manage system settings'],
            ['name' => 'system_logs', 'description' => 'View system logs'],
            ['name' => 'backup_restore', 'description' => 'Create and restore backups'],
            ['name' => 'manage_users', 'description' => 'Manage all users'],
            ['name' => 'manage_own_collection', 'description' => 'Manage own collection'],
            ['name' => 'manage_all_collections', 'description' => 'Manage all collections'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('Permissions seeded successfully!');
    }
} 