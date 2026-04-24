<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => TeamInvitation::ROLE_MEMBER,
            'token' => Str::random(64),
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function admin(): self
    {
        return $this->state(fn (): array => ['role' => TeamInvitation::ROLE_ADMIN]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }

    public function accepted(): self
    {
        return $this->state(fn (): array => ['accepted_at' => now()]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (): array => ['cancelled_at' => now()]);
    }
}
