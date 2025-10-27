<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrognosisChangeRate extends Model
{
    use Auditable, HasFactory;

    protected $table = 'prognosis_change_rates';

    protected $fillable = [
        'scenario_type',
        'asset_type',
        'year',
        'change_rate',
        'description',
        'is_active',
        // Ownership & auditing
        'user_id',
        'team_id',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'year' => 'integer',
        'change_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public static function prognosisOptions(): array
    {
        return PrognosisType::query()->active()->orderBy('code')->pluck('label', 'code')->all();
    }

    public function getScenarioTypeLabel(): string
    {
        return PrognosisType::query()->where('code', $this->scenario_type)->value('label') ?? $this->scenario_type;
    }

    /**
     * Dynamic asset type options sourced from the asset_types table.
     * Returns array [type => name].
     */
    public static function assetTypeOptions(): array
    {
        return \App\Models\AssetType::options();
    }

    public function getAssetTypeLabel(): string
    {
        $label = \App\Models\AssetType::query()->where('type', $this->asset_type)->value('name');

        return $label ?? $this->asset_type;
    }

    public function getChangeRateDecimal(): float
    {
        return $this->change_rate / 100;
    }

    public function getChangeRateMultiplier(): float
    {
        return 1 + ($this->change_rate / 100);
    }

    public function scopeForScenario($query, string $scenarioType)
    {
        return $query->where('scenario_type', $scenarioType);
    }

    public function scopeForAssetType($query, string $assetType)
    {
        return $query->where('asset_type', $assetType);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getChangeRate(string $scenarioType, string $assetType, int $year): float
    {
        $config = self::forScenario($scenarioType)
            ->forAssetType($assetType)
            ->forYear($year)
            ->active()
            ->first();

        if ($config) {
            return (float) $config->change_rate;
        }

        $config = self::forScenario($scenarioType)
            ->forAssetType($assetType)
            ->where('year', '<=', $year)
            ->active()
            ->orderBy('year', 'desc')
            ->first();

        return $config ? (float) $config->change_rate : 0.0;
    }

    /**
     * Get available changerate options for dropdowns
     * Returns array with 'changerates.asset_type' as value and asset type name as label
     */
    public static function getChangeRateOptions(): array
    {
        // Get distinct asset types from prognosis_change_rates table
        $distinctAssetTypes = self::query()
            ->select('asset_type')
            ->distinct()
            ->active()
            ->pluck('asset_type')
            ->toArray();

        $options = [];

        // Build options array with 'changerates.asset_type' format
        foreach ($distinctAssetTypes as $assetType) {
            $key = "changerates.{$assetType}";

            // Try to get name from AssetType model first, fallback to PrognosisChangeRate constants
            $assetTypeModel = \App\Models\AssetType::where('type', $assetType)->first();
            if ($assetTypeModel) {
                $label = $assetTypeModel->name;
            } else {
                $label = ucfirst($assetType);
            }

            $options[$key] = $label;
        }

        // Sort by label
        asort($options);

        return $options;
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
