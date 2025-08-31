<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class SimulationConfiguration extends Model
{
    use Auditable, HasFactory;

    protected $table = 'simulation_configurations';

    public const RISK_TOLERANCE_LEVELS = [
        'conservative' => 'Conservative',
        'moderate_conservative' => 'Moderate Conservative',
        'moderate' => 'Moderate',
        'moderate_aggressive' => 'Moderate Aggressive',
        'aggressive' => 'Aggressive',
    ];

    protected static function booted(): void
    {
        parent::booted();

        // Apply team-based filtering
        static::addGlobalScope(new TeamScope);

        static::saving(function (self $model): void {
            if (is_null($model->pension_official_age) && $model->birth_year) {
                $model->pension_official_age = 67; // Standard retirement age
            }
            if (is_null($model->prognose_age)) {
                $model->prognose_age = (int) now()->year - ($model->birth_year ?? now()->year - 40) + 10;
            }
            if (is_null($model->death_age) && $model->birth_year) {
                $model->death_age = 85; // Average life expectancy
            }
        });
    }

    protected $fillable = [
        'asset_configuration_id',
        'name',
        'description',
        'birth_year',
        'prognose_age',
        'pension_official_age',
        'pension_wish_age',
        'death_age',
        'export_start_age',
        'public',
        'icon',
        'image',
        'color',
        'tags',
        'risk_tolerance',
        'user_id',
        'team_id',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'birth_year' => 'integer',
        'prognose_age' => 'integer',
        'pension_official_age' => 'integer',
        'pension_wish_age' => 'integer',
        'death_age' => 'integer',
        'export_start_age' => 'integer',
        'public' => 'boolean',
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assetConfiguration(): BelongsTo
    {
        return $this->belongsTo(AssetConfiguration::class);
    }

    public function simulationAssets(): HasMany
    {
        return $this->hasMany(SimulationAsset::class, 'asset_configuration_id');
    }

    /**
     * Get validation rules for risk tolerance
     */
    public static function getRiskToleranceValidationRule(): string
    {
        return Rule::in(array_keys(self::RISK_TOLERANCE_LEVELS));
    }

    /**
     * Get the human-readable risk tolerance label
     */
    public function getRiskToleranceLabelAttribute(): string
    {
        return self::RISK_TOLERANCE_LEVELS[$this->risk_tolerance] ?? 'Unknown';
    }
}
