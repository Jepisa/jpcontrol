<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'assigned_to' => fake()->optional(0.5)->passthrough(\App\Models\User::factory()),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['open', 'in_progress', 'resolved', 'closed', 'on_hold']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'store_name' => fake()->company(),
            'environment' => fake()->randomElement(['production', 'qa']),
            'steps_to_reproduce' => fake()->optional()->paragraph(),
            'expected_behavior' => fake()->optional()->paragraph(),
            'actual_behavior' => fake()->optional()->paragraph(),
        ];
    }
}
