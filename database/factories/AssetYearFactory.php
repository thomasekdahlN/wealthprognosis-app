<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetYear>
 */
class AssetYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = \App\Models\User::factory()->create();
        $asset = \App\Models\Asset::factory()->create(['user_id' => $user->id]);

        return [
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'year' => $this->faker->numberBetween(2020, 2030),
            'asset_id' => $asset->id,
            'asset_owner_id' => $asset->asset_owner_id,

            // Income data
            'income_description' => null,
            'income_amount' => 0,
            'income_factor' => null,
            'income_rule' => null,
            'income_transfer' => null,
            'income_source' => null,
            'income_changerate' => null,
            'income_repeat' => false,

            // Expense data
            'expence_description' => null,
            'expence_amount' => 0,
            'expence_factor' => null,
            'expence_rule' => null,
            'expence_transfer' => null,
            'expence_source' => null,
            'expence_changerate' => null,
            'expence_repeat' => false,

            // Asset data
            'asset_description' => null,
            'asset_market_amount' => 0,
            'asset_acquisition_amount' => 0,
            'asset_equity_amount' => 0,
            'asset_taxable_initial_amount' => 0,
            'asset_paid_amount' => 0,
            'asset_changerate' => null,
            'asset_rule' => null,
            'asset_transfer' => null,
            'asset_source' => null,
            'asset_repeat' => false,

            // Mortgage data
            'mortgage_description' => null,
            'mortgage_amount' => 0,
            'mortgage_years' => 0,
            'mortgage_interest' => null,
            'mortgage_gebyr' => 0,
            'mortgage_tax' => 0,
            'mortgage_extra_downpayment_amount' => null,

            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => $this->faker->sha256(),
            'updated_checksum' => $this->faker->sha256(),
        ];
    }
}
