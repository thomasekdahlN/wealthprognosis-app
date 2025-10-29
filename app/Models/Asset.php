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
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetYear> $years
 * @property \App\Models\AssetType|null $assetType
 * @property string $name
 * @property string $asset_type
 * @property string $group
 * @property string|null $code
 * @property bool $is_active
 * @property string|null $tax_property
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany years()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany assetYears()
 */
class Asset extends Model
{
    use Auditable, HasFactory;

    protected static function booted(): void
    {
        parent::booted();

        // Apply team-based filtering
        static::addGlobalScope(new TeamScope);

        // Always inherit current active asset configuration on create when not explicitly set
        static::creating(function (self $asset): void {
            if (empty($asset->asset_configuration_id)) {
                $id = (int) (app(\App\Services\CurrentAssetConfiguration::class)->id() ?? 0);
                if ($id > 0) {
                    $asset->asset_configuration_id = $id;
                }
            }
        });
    }

    protected $fillable = [
        'asset_configuration_id',
        'user_id',
        'team_id',
        'name',
        'description',
        'asset_type',
        'group',

        'tax_property',
        'tax_country',
        'is_active',
        'debug',
        'sort_order',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'debug' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Note: Asset types are now managed in the asset_types table
    // Use AssetTypeValidator::getValidAssetTypes() to get available types

    // Asset groups
    public const GROUPS = [
        'private' => 'Private',
        'company' => 'Company',
    ];

    // removed prognosis relation; assets are not tied to prognoses

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

    // Deprecated: owner() has been replaced by configuration()
    // public function owner(): BelongsTo
    // {
    //     return $this->belongsTo(AssetConfiguration::class, 'asset_configuration_id');
    // }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(AssetConfiguration::class, 'asset_configuration_id');
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type', 'type');
    }

    public function years(): HasMany
    {
        return $this->hasMany(AssetYear::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(AssetIncome::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(AssetExpense::class);
    }

    public function mortgages(): HasMany
    {
        return $this->hasMany(AssetMortgage::class);
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
