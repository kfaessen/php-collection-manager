<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Permission;

class GroupPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groupPermissions = [
            'admin' => [
                'user_manage', 'group_manage', 'permission_manage', 'collection_manage',
                'collection_view', 'collection_edit', 'collection_delete', 'item_manage',
                'item_view', 'item_edit', 'item_delete', 'system_settings', 'system_logs', 
                'backup_restore', 'manage_users', 'manage_own_collection', 'manage_all_collections'
            ],
            'moderator' => [
                'collection_view', 'collection_edit', 'item_manage', 'item_view', 'item_edit',
                'manage_own_collection'
            ],
            'user' => [
                'collection_view', 'item_view', 'item_edit', 'manage_own_collection'
            ],
            'guest' => [
                'collection_view', 'item_view'
            ]
        ];

        foreach ($groupPermissions as $groupName => $permissions) {
            $group = Group::where('name', $groupName)->first();
            
            if ($group) {
                foreach ($permissions as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    
                    if ($permission) {
                        // Check if permission already exists for this group
                        if (!$group->permissions()->where('permission_id', $permission->id)->exists()) {
                            $group->permissions()->attach($permission->id);
                        }
                    }
                }
            }
        }

        $this->command->info('Group permissions seeded successfully!');
    }
} 