<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@taskops.dev'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password123'),
            ]
        );

        if ($demoUser->tasks()->count() === 0) {
            Task::factory()
                ->count(12)
                ->for($demoUser)
                ->create();

            // A few deterministic tasks for today, useful for testing the report endpoint.
            Task::factory()->for($demoUser)->create([
                'title' => 'Ship API documentation',
                'due_date' => now()->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'pending',
            ]);
            Task::factory()->for($demoUser)->create([
                'title' => 'Review pull requests',
                'due_date' => now()->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'done',
            ]);
        }
    }
}
