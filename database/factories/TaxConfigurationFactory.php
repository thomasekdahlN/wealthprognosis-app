<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxConfiguration>
 */
class TaxConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_code' => fake()->randomElement(['no', 'se', 'dk', 'ch', 'us', 'en']),
            'year' => fake()->numberBetween(2020, 2030),
            'tax_type' => fake()->randomElement(['salary', 'pension', 'stock', 'equityfund', 'bondfund', 'house', 'cash']),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'configuration' => [
                'income' => fake()->randomFloat(2, 0, 50),
                'realization' => fake()->randomFloat(2, 0, 30),
                'fortune' => fake()->randomFloat(2, 0, 2),
                'standardDeduction' => fake()->randomFloat(2, 0, 15),
            ],
        ];
    }
}
