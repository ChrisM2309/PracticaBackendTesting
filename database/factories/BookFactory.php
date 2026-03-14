<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->name,
            'description' => $this->faker->text(200),
            'ISBN' => $this->faker->unique()->numerify('#############'),
            'total_copies' => $this->faker->numberBetween(5,10),
            'available_copies' => $this->faker->numberBetween(1,5),
            'is_available' => $this->faker->boolean(),
        ];
    }
}
