<?php

namespace App\Services;

use App\Models\AssetConfiguration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetExportService
{
    protected AssetConfiguration $assetConfiguration;

    public function __construct(AssetConfiguration $assetConfiguration)
    {
        $this->assetConfiguration = $assetConfiguration;
    }

    /**
     * Export AssetConfiguration to JSON format
     */
    public function toJson(): string
    {
        $data = $this->buildJsonStructure();

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export AssetConfiguration to JSON file
     */
    public function toFile(?string $filePath = null): string
    {
        if (! $filePath) {
            $exportDate = now()->format('Y-m-d');
            $filename = $exportDate.'_'.Str::slug($this->assetConfiguration->name).'_'.$this->assetConfiguration->id.'.json';
            $filePath = 'exports/'.$filename;
        }

        $jsonContent = $this->toJson();
        Storage::disk('local')->put($filePath, $jsonContent);

        return Storage::disk('local')->path($filePath);
    }

    /**
     * Build the JSON structure from database data
     */
    protected function buildJsonStructure(): array
    {
        $data = [];

        // Add meta section from AssetConfiguration
        $data['meta'] = [
            'name' => $this->assetConfiguration->name,
            'birthYear' => (string) $this->assetConfiguration->birth_year,
            'prognoseAge' => (string) $this->assetConfiguration->prognose_age,
            'pensionOfficialAge' => (string) $this->assetConfiguration->pension_official_age,
            'pensionWishAge' => (string) $this->assetConfiguration->pension_wish_age,
            'deathAge' => (string) $this->assetConfiguration->expected_death_age,
            'exportStartYear' => (string) $this->assetConfiguration->export_start_age,
        ];

        // Add timestamps
        if ($this->assetConfiguration->created_at) {
            $data['meta']['createdAt'] = $this->assetConfiguration->created_at->toISOString();
        }
        if ($this->assetConfiguration->updated_at) {
            $data['meta']['updatedAt'] = $this->assetConfiguration->updated_at->toISOString();
        }
        $data['meta']['exportedAt'] = now()->toISOString();

        // Process each asset (ordered by sort_order to maintain JSON sequence)
        $assets = $this->assetConfiguration->assets()->with('years')->orderBy('sort_order')->get();

        foreach ($assets as $asset) {
            $assetData = [];

            // Add asset meta section
            $assetData['meta'] = [
                'type' => $asset->asset_type,
                'group' => $asset->group,
                'name' => $asset->name,
                'description' => $asset->description ?? '',
                'active' => $asset->is_active,
                'taxProperty' => $asset->tax_property,
            ];

            // Process asset years
            $years = $asset->years()->orderBy('year')->get();

            foreach ($years as $assetYear) {
                $yearKey = $this->resolveYearKey($assetYear->year);
                $assetData[$yearKey] = $this->buildYearData($assetYear);
            }

            // Use the asset code as the key
            $data[$asset->code] = $assetData;
        }

        return $data;
    }

    /**
     * Build year data from AssetYear model
     */
    protected function buildYearData($assetYear): array
    {
        $yearData = [];

        // Unified year-level description
        if (! empty($assetYear->description)) {
            $yearData['description'] = $assetYear->description;
        }

        // Income data
        if ($this->hasIncomeData($assetYear)) {
            $yearData['income'] = array_filter([
                'amount' => $assetYear->income_amount ?: null,
                'changerate' => $assetYear->income_changerate,
                'transfer' => $assetYear->income_transfer,
                'source' => $assetYear->income_source,
                'rule' => $assetYear->income_rule,
                'repeat' => $assetYear->income_repeat ?: null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        // Expense data
        if ($this->hasExpenseData($assetYear)) {
            $yearData['expence'] = array_filter([
                'amount' => $assetYear->expence_amount ?: null,
                'factor' => $assetYear->expence_factor && $assetYear->expence_factor !== 'yearly' ? 12 : null,
                'changerate' => $assetYear->expence_changerate,
                'transfer' => $assetYear->expence_transfer,
                'source' => $assetYear->expence_source,
                'rule' => $assetYear->expence_rule,
                'repeat' => $assetYear->expence_repeat ?: null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        // Asset data
        if ($this->hasAssetData($assetYear)) {
            $yearData['asset'] = array_filter([
                'marketAmount' => $assetYear->asset_market_amount ?: null,
                'acquisitionAmount' => $assetYear->asset_acquisition_amount ?: null,
                'equityAmount' => $assetYear->asset_equity_amount ?: null,
                'taxableInitialAmount' => $assetYear->asset_taxable_initial_amount ?: null,
                'paidAmount' => $assetYear->asset_paid_amount ?: null,
                'changerate' => $assetYear->asset_changerate,
                'rule' => $assetYear->asset_rule,
                'transfer' => $assetYear->asset_transfer,
                'source' => $assetYear->asset_source,
                'repeat' => $assetYear->asset_repeat ?: null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        // Mortgage data
        if ($this->hasMortgageData($assetYear)) {
            $yearData['mortgage'] = array_filter([
                'amount' => $assetYear->mortgage_amount ?: null,
                'years' => $assetYear->mortgage_years ?: null,
                'interest' => $assetYear->mortgage_interest,
                'gebyr' => $assetYear->mortgage_gebyr ?: null,
                'tax' => $assetYear->mortgage_tax ?: null,
                'paymentExtra' => $assetYear->mortgage_extra_downpayment_amount ?: null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return $yearData;
    }

    /**
     * Resolve year number to year key (handle variable years)
     */
    protected function resolveYearKey(int $year): string
    {
        $birthYear = $this->assetConfiguration->birth_year;
        $pensionWishAge = $this->assetConfiguration->pension_wish_age;
        $pensionOfficialAge = $this->assetConfiguration->pension_official_age;

        // Check if this year matches any special year
        if ($birthYear && $pensionWishAge && $year == ($birthYear + $pensionWishAge)) {
            return '$pensionWishYear';
        }

        if ($birthYear && $pensionOfficialAge && $year == ($birthYear + $pensionOfficialAge)) {
            return '$pensionOfficialYear';
        }

        // Return as regular year
        return (string) $year;
    }

    /**
     * Check if asset year has income data
     */
    protected function hasIncomeData($assetYear): bool
    {
        return ! empty($assetYear->income_amount) ||
               ! empty($assetYear->income_amount) ||
               ! empty($assetYear->income_changerate);
    }

    /**
     * Check if asset year has expense data
     */
    protected function hasExpenseData($assetYear): bool
    {
        return ! empty($assetYear->expence_amount) ||
               ! empty($assetYear->expence_amount) ||
               ! empty($assetYear->expence_changerate);
    }

    /**
     * Check if asset year has asset data
     */
    protected function hasAssetData($assetYear): bool
    {
        return ! empty($assetYear->asset_market_amount) ||
               ! empty($assetYear->asset_market_amount) ||
               ! empty($assetYear->asset_changerate);
    }

    /**
     * Check if asset year has mortgage data
     */
    protected function hasMortgageData($assetYear): bool
    {
        return ! empty($assetYear->mortgage_amount) ||
               ! empty($assetYear->mortgage_amount) ||
               ! empty($assetYear->mortgage_interest);
    }

    /**
     * Static method for easy usage
     */
    public static function export(AssetConfiguration $assetConfiguration, ?string $filePath = null): string
    {
        $service = new static($assetConfiguration);

        return $service->toFile($filePath);
    }

    /**
     * Static method to get JSON string
     */
    public static function toJsonString(AssetConfiguration $assetConfiguration): string
    {
        $service = new static($assetConfiguration);

        return $service->toJson();
    }
}
