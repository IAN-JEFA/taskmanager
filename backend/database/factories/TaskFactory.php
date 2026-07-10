<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => ucfirst($this->faker->unique()->words(3, true)),
            'due_date' => $this->faker->dateTimeBetween('now', '+14 days')->format('Y-m-d'),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'done']),
        ];
    }
}
