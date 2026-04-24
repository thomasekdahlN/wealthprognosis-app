<?php

namespace App\Models;

use App\Helpers\AssetTypeValidator;
use App\Models\Concerns\Auditable;
use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/**
 * @property int $id
 * @property int|null $simulation_configuration_id
 * @property int|null $asset_configuration_id
 * @property int|null $user_id
 * @property int|null $team_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string $asset_type
 * @property string|null $group
 * @property string|null $tax_property
 * @property string|null $tax_country
 * @property bool $is_active
 * @property int|null $sort_order
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $created_checksum
 * @property string|null $updated_checksum
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $tax_type Virtual accessor returning the tax_type from the related AssetType
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SimulationAssetYear> $simulationAssetYears
 * @property \App\Models\AssetType|null $assetType
 * @property \App\Models\AssetConfiguration|null $assetConfiguration
 * @property \App\Models\SimulationConfiguration|null $simulationConfiguration
 */
class SimulationAsset extends Model
{
    use Auditable, HasFactory;

    protected $table = 'simulation_assets';

    protected static function booted(): void
    {
        parent::booted();

        // Apply team-based filtering
        static::addGlobalScope(new TeamScope);
    }

    protected $fillable = [
        'simulation_configuration_id',
        'asset_configuration_id',
        'user_id',
        'team_id',
        'name',
        'code',
        'description',
        'asset_type',
        'group',
        'tax_property',
        'tax_country',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Asset groups
    public const GROUPS = [
        'private' => 'Private',
        'company' => 'Company',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function simulationConfiguration(): BelongsTo
    {
        return $this->belongsTo(SimulationConfiguration::class, 'simulation_configuration_id');
    }

    public function assetConfiguration(): BelongsTo
    {
        return $this->belongsTo(AssetConfiguration::class, 'asset_configuration_id');
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type', 'type');
    }

    public function simulationAssetYears(): HasMany
    {
        return $this->hasMany(SimulationAssetYear::class, 'asset_id');
    }

    // Helper methods
    public function getTypeLabel(): string
    {
        $assetType = \App\Models\AssetType::where('type', $this->asset_type)->first();

        return $assetType ? $assetType->name : $this->asset_type;
    }

    public function getGroupLabel(): string
    {
        return self::GROUPS[$this->group] ?? $this->group;
    }

    /**
     * Virtual accessor returning the tax_type value from the related AssetType model.
     * Returns the tax_type string (e.g. 'none', 'capital_gains', 'tax_deferred') or null.
     */
    public function getTaxTypeAttribute(): ?string
    {
        return $this->assetType?->tax_type;
    }

    public function getTaxTypeLabel(): string
    {
        $taxTypeName = optional($this->assetType?->taxType)->name;

        return $taxTypeName ?? '';
    }

    /**
     * Check if this asset type is liquid (can be sold in parts for FIRE)
     * Delegates to AssetTypeService for cached lookup
     */
    public function isLiquid(): bool
    {
        return app(\App\Services\AssetTypeService::class)->isLiquid($this->asset_type);
    }

    /**
     * Check if this asset type is FIRE eligible
     * Delegates to AssetTypeService for cached lookup
     */
    public function isFireSavingType(): bool
    {
        return app(\App\Services\AssetTypeService::class)->isFireEligible($this->asset_type);
    }

    /**
     * Check if this asset type supports a specific capability
     * Delegates to AssetTypeService for cached lookup
     */
    public function supportsCapability(string $capability): bool
    {
        return app(\App\Services\AssetTypeService::class)->getCapability($this->asset_type, $capability);
    }

    /**
     * Get all capabilities for this asset type
     * Delegates to AssetTypeService for cached lookup
     */
    public function getCapabilities(): array
    {
        return app(\App\Services\AssetTypeService::class)->getCapabilities($this->asset_type);
    }

    /**
     * Get all valid asset types for dropdowns
     */
    public static function getValidAssetTypes(): array
    {
        return AssetTypeValidator::getAssetTypesForSelect();
    }

    /**
     * Get validation rule for group field
     */
    public static function getGroupValidationRule(): string
    {
        return Rule::in(array_keys(self::GROUPS));
    }

    /**
     * Get the human-readable group label
     */
    public function getGroupLabelAttribute(): string
    {
        return self::GROUPS[$this->group] ?? 'Unknown';
    }
}
