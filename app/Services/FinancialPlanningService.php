<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetType;
use App\Models\AssetYear;
use App\Models\User;

class FinancialPlanningService
{
    public function createChildrenEvents(array $data, int $configurationId, User $user): array
    {
        $configuration = AssetConfiguration::find($configurationId);
        $events = [];

        // Extract children information from the data
        $childrenInfo = $this->extractChildrenInfo($data);

        foreach ($childrenInfo as $child) {
            // Add barnetrygd (child benefit) until age 18
            $this->createBarnetrygdIncome($child, $configurationId, $user);

            // Add child expenses
            $this->createChildExpenses($child, $configurationId, $user);

            // Create event for when child leaves home
            if (isset($child['leave_home_age'])) {
                $this->createChildLeavesHomeEvent($child, $configurationId, $user);
            }

            $events[] = "Created financial plan for {$child['name']}";
        }

        return $events;
    }

    public function createInheritanceEvent(array $data, int $configurationId, User $user): array
    {
        $year = $data['year'] ?? (now()->year + 10); // Default to 10 years from now
        $amount = $data['amount'] ?? 0;

        if ($amount > 0) {
            // Create inheritance asset type if it doesn't exist
            $assetType = AssetType::firstOrCreate(
                ['type' => 'inheritance'],
                [
                    'name' => 'Inheritance',
                    'description' => 'Inherited assets',
                    'is_fire_sellable' => true,
                    'team_id' => $user->current_team_id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            // Create inheritance asset
            $asset = Asset::create([
                'name' => 'Expected Inheritance',
                'asset_type' => $assetType->type,
                'asset_configuration_id' => $configurationId,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode($data)),
                'updated_checksum' => md5(json_encode($data)),
            ]);

            // Create asset year for the inheritance
            AssetYear::create([
                'asset_id' => $asset->id,
                'year' => $year,
                'market_value' => $amount,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode(['inheritance' => $amount])),
                'updated_checksum' => md5(json_encode(['inheritance' => $amount])),
            ]);
        }

        return ["Created inheritance event for {$amount} NOK in {$year}"];
    }

    public function createPropertyChangeEvent(array $data, int $configurationId, User $user): array
    {
        $year = $data['year'] ?? (now()->year + 5); // Default to 5 years from now
        $newValue = $data['amount'] ?? 0;
        $events = [];

        // Find existing house assets
        $houseAssets = Asset::where('asset_configuration_id', $configurationId)
            ->where('asset_type', 'house')
            ->get();

        foreach ($houseAssets as $house) {
            // Create asset year for selling current house
            $currentValue = $house->assetYears()
                ->where('year', '<=', $year)
                ->orderBy('year', 'desc')
                ->first()?->market_value ?? 0;

            if ($currentValue > 0) {
                AssetYear::create([
                    'asset_id' => $house->id,
                    'year' => $year,
                    'market_value' => 0, // Sold
                    'income' => $currentValue, // Sale proceeds
                    'team_id' => $user->current_team_id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => md5('house_sale'),
                    'updated_checksum' => md5('house_sale'),
                ]);

                $events[] = 'Planned sale of current house';
            }
        }

        // Create new house asset if value specified
        if ($newValue > 0) {
            $newHouse = Asset::create([
                'name' => 'New House',
                'asset_type' => 'house',
                'asset_configuration_id' => $configurationId,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode($data)),
                'updated_checksum' => md5(json_encode($data)),
            ]);

            AssetYear::create([
                'asset_id' => $newHouse->id,
                'year' => $year,
                'market_value' => $newValue,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5(json_encode(['new_house' => $newValue])),
                'updated_checksum' => md5(json_encode(['new_house' => $newValue])),
            ]);

            $events[] = 'Planned purchase of new house';
        }

        return $events;
    }

    public function createRetirementPlan(AssetConfiguration $configuration, User $user): array
    {
        $retirementYear = now()->year + ($configuration->pension_wish_age - (now()->year - $configuration->birth_year));
        $events = [];

        // Stop salary income at retirement
        $salaryAssets = Asset::where('asset_configuration_id', $configuration->id)
            ->where('asset_type', 'salary')
            ->get();

        foreach ($salaryAssets as $asset) {
            AssetYear::create([
                'asset_id' => $asset->id,
                'year' => $retirementYear,
                'income' => 0,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5('retirement_stop_salary'),
                'updated_checksum' => md5('retirement_stop_salary'),
            ]);
        }

        // Create pension income
        $this->createPensionIncome($configuration, $user, $retirementYear);

        // Create OTP withdrawal plan (over 15 years)
        $this->createOtpWithdrawalPlan($configuration, $user, $retirementYear);

        $events[] = "Created retirement plan starting at age {$configuration->pension_wish_age}";

        return $events;
    }

    protected function extractChildrenInfo(array $data): array
    {
        // This would extract children information from the conversation
        // For now, return a sample structure
        return [
            [
                'name' => $data['child_name'] ?? 'Child',
                'birth_year' => $data['child_birth_year'] ?? now()->year,
                'leave_home_age' => $data['leave_home_age'] ?? 18,
                'monthly_expenses' => $data['child_expenses'] ?? 5000,
            ],
        ];
    }

    protected function createBarnetrygdIncome(array $child, int $configurationId, User $user): void
    {
        $assetType = AssetType::firstOrCreate(
            ['type' => 'barnetrygd'],
            [
                'name' => 'Barnetrygd',
                'description' => 'Child benefit from the government',
                'is_fire_sellable' => false,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        );

        $asset = Asset::create([
            'name' => "Barnetrygd for {$child['name']}",
            'asset_type' => $assetType->type,
            'asset_configuration_id' => $configurationId,
            'team_id' => $user->current_team_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => md5(json_encode($child)),
            'updated_checksum' => md5(json_encode($child)),
        ]);

        // Add barnetrygd for years until child turns 18
        $currentYear = now()->year;
        $endYear = $child['birth_year'] + 18;

        for ($year = $currentYear; $year <= $endYear; $year++) {
            AssetYear::create([
                'asset_id' => $asset->id,
                'year' => $year,
                'income' => 12000, // Approximate annual barnetrygd
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5("barnetrygd_{$year}"),
                'updated_checksum' => md5("barnetrygd_{$year}"),
            ]);
        }
    }

    protected function createChildExpenses(array $child, int $configurationId, User $user): void
    {
        $assetType = AssetType::firstOrCreate(
            ['type' => 'child_expenses'],
            [
                'name' => 'Child Expenses',
                'description' => 'Expenses related to children',
                'is_fire_sellable' => false,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        );

        $asset = Asset::create([
            'name' => "Expenses for {$child['name']}",
            'asset_type' => $assetType->type,
            'asset_configuration_id' => $configurationId,
            'team_id' => $user->current_team_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => md5(json_encode($child)),
            'updated_checksum' => md5(json_encode($child)),
        ]);

        // Add expenses until child leaves home
        $currentYear = now()->year;
        $endYear = $child['birth_year'] + $child['leave_home_age'];

        for ($year = $currentYear; $year <= $endYear; $year++) {
            AssetYear::create([
                'asset_id' => $asset->id,
                'year' => $year,
                'expense' => $child['monthly_expenses'] * 12,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5("child_expenses_{$year}"),
                'updated_checksum' => md5("child_expenses_{$year}"),
            ]);
        }
    }

    protected function createChildLeavesHomeEvent(array $child, int $configurationId, User $user): void
    {
        // This would create an event for when the child leaves home
        // Expenses would stop at this point
    }

    protected function createPensionIncome(AssetConfiguration $configuration, User $user, int $retirementYear): void
    {
        // Create pension asset and income stream
        $assetType = AssetType::firstOrCreate(
            ['type' => 'pension'],
            [
                'name' => 'Pension',
                'description' => 'Pension income',
                'is_fire_sellable' => false,
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        );

        $asset = Asset::create([
            'name' => 'Pension Income',
            'asset_type' => $assetType->type,
            'asset_configuration_id' => $configuration->id,
            'team_id' => $user->current_team_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => md5('pension_income'),
            'updated_checksum' => md5('pension_income'),
        ]);

        // Add pension income from retirement until death
        for ($year = $retirementYear; $year <= ($configuration->birth_year + $configuration->expected_death_age); $year++) {
            AssetYear::create([
                'asset_id' => $asset->id,
                'year' => $year,
                'income' => 300000, // Estimated pension income
                'team_id' => $user->current_team_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => md5("pension_{$year}"),
                'updated_checksum' => md5("pension_{$year}"),
            ]);
        }
    }

    protected function createOtpWithdrawalPlan(AssetConfiguration $configuration, User $user, int $retirementYear): void
    {
        // Find OTP assets and create withdrawal plan over 15 years
        $otpAssets = Asset::where('asset_configuration_id', $configuration->id)
            ->where('asset_type', 'otp')
            ->get();

        foreach ($otpAssets as $asset) {
            $currentValue = $asset->assetYears()
                ->where('year', '<=', $retirementYear)
                ->orderBy('year', 'desc')
                ->first()?->market_value ?? 0;

            if ($currentValue > 0) {
                $annualWithdrawal = $currentValue / 15; // Spread over 15 years

                for ($year = $retirementYear; $year < ($retirementYear + 15); $year++) {
                    AssetYear::create([
                        'asset_id' => $asset->id,
                        'year' => $year,
                        'income' => $annualWithdrawal,
                        'market_value' => $currentValue - (($year - $retirementYear + 1) * $annualWithdrawal),
                        'team_id' => $user->current_team_id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'created_checksum' => md5("otp_withdrawal_{$year}"),
                        'updated_checksum' => md5("otp_withdrawal_{$year}"),
                    ]);
                }
            }
        }
    }
}
