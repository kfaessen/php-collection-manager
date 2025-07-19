<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'admin', 'description' => 'Administrators with full access'],
            ['name' => 'moderator', 'description' => 'Moderators with limited administrative access'],
            ['name' => 'user', 'description' => 'Regular users with basic access'],
            ['name' => 'guest', 'description' => 'Guest users with read-only access'],
        ];

        foreach ($groups as $group) {
            Group::firstOrCreate(
                ['name' => $group['name']],
                $group
            );
        }

        $this->command->info('Groups seeded successfully!');
    }
} 