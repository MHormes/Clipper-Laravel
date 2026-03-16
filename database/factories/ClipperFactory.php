<?php

namespace Database\Factories;

use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clipper>
 */
class ClipperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'series_id' => Series::factory(),
            'series_number' => $this->faker->unique()->numberBetween(1, 1000), // Increased range to avoid unique constraint issues in tests
            'image_data' => null,
            'requested_by' => \App\Models\User::factory(),
            'auto_add_to_collection' => false,
            'accepted_by' => null,
        ];
    }
}
