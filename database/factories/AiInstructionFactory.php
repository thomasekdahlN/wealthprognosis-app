<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiInstruction>
 */
class AiInstructionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'system_prompt' => $this->faker->paragraph(),
            'user_prompt_template' => 'Analyze this data: {json_data}',
            'model' => $this->faker->randomElement(['gpt-4', 'gpt-3.5-turbo']),
            'max_tokens' => $this->faker->numberBetween(100, 2000),
            'temperature' => $this->faker->randomFloat(2, 0, 2),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => $this->faker->sha256(),
            'updated_checksum' => $this->faker->sha256(),
        ];
    }
}
