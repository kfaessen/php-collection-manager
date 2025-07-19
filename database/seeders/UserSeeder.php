<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Group;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@example.com',
                'password_hash' => Hash::make('admin123'),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'is_active' => true,
                'email_verified' => true,
                'registration_method' => 'local',
                'preferred_language' => 'nl',
                'notifications_enabled' => true,
                'email_notifications' => true,
                'push_notifications' => true,
            ]
        );

        // Create test user
        $user = User::firstOrCreate(
            ['username' => 'user'],
            [
                'email' => 'user@example.com',
                'password_hash' => Hash::make('user123'),
                'first_name' => 'Test',
                'last_name' => 'User',
                'is_active' => true,
                'email_verified' => true,
                'registration_method' => 'local',
                'preferred_language' => 'nl',
                'notifications_enabled' => true,
                'email_notifications' => true,
                'push_notifications' => true,
            ]
        );

        // Create moderator user
        $moderator = User::firstOrCreate(
            ['username' => 'moderator'],
            [
                'email' => 'moderator@example.com',
                'password_hash' => Hash::make('moderator123'),
                'first_name' => 'Moderator',
                'last_name' => 'User',
                'is_active' => true,
                'email_verified' => true,
                'registration_method' => 'local',
                'preferred_language' => 'nl',
                'notifications_enabled' => true,
                'email_notifications' => true,
                'push_notifications' => true,
            ]
        );

        // Assign users to groups
        $adminGroup = Group::where('name', 'admin')->first();
        $userGroup = Group::where('name', 'user')->first();
        $moderatorGroup = Group::where('name', 'moderator')->first();

        if ($adminGroup && !$admin->groups()->where('group_id', $adminGroup->id)->exists()) {
            $admin->groups()->attach($adminGroup->id);
        }

        if ($userGroup && !$user->groups()->where('group_id', $userGroup->id)->exists()) {
            $user->groups()->attach($userGroup->id);
        }

        if ($moderatorGroup && !$moderator->groups()->where('group_id', $moderatorGroup->id)->exists()) {
            $moderator->groups()->attach($moderatorGroup->id);
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Admin credentials: admin / admin123');
        $this->command->info('User credentials: user / user123');
        $this->command->info('Moderator credentials: moderator / moderator123');
    }
} 