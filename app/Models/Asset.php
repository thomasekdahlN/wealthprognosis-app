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

class Asset extends Model
{
    use Auditable, HasFactory;

    protected static function booted(): void
    {
        parent::booted();

        // Apply team-based filtering
        static::addGlobalScope(new TeamScope);
    }

    protected $fillable = [
        'asset_configuration_id',
        'user_id',
        'team_id',
        'name',
        'description',
        'asset_type',
        'group',
        'tax_type',
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

    // Note: Asset types are now managed in the asset_types table
    // Use AssetTypeValidator::getValidAssetTypes() to get available types

    // Asset groups
    public const GROUPS = [
        'private' => 'Private',
        'company' => 'Company',
    ];

    // Tax types
    public const TAX_TYPES = [
        'none' => 'No Tax',
        'income' => 'Income Tax',
        'salary' => 'Salary Tax',
        'house' => 'House Tax',
        'rental' => 'Rental Tax',
        'equityfund' => 'Equity Fund Tax',
        'bondfund' => 'Bond Fund Tax',
        'stock' => 'Stock Tax',
        'crypto' => 'Crypto Tax',
        'cash' => 'Cash Tax',
        'pension' => 'Pension Tax',
        'inheritance' => 'Inheritance Tax',
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

    public function rules(): HasMany
    {
        return $this->hasMany(AssetRule::class);
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
        return self::TAX_TYPES[$this->tax_type] ?? $this->tax_type;
    }

    public function isFirePartSalePossible(): bool
    {
        $assetType = \App\Models\AssetType::where('type', $this->asset_type)->first();

        return $assetType ? $assetType->is_fire_sellable : false;
    }

    public function isFireSavingType(): bool
    {
        $assetType = \App\Models\AssetType::where('type', $this->asset_type)->first();

        return $assetType ? ($assetType->can_have_market_value || $assetType->can_generate_income) : false;
    }

    /**
     * Check if this asset type supports a specific capability
     */
    public function supportsCapability(string $capability): bool
    {
        return AssetTypeValidator::supportsCapability($this->asset_type, $capability);
    }

    /**
     * Get all capabilities for this asset type
     */
    public function getCapabilities(): array
    {
        return AssetTypeValidator::getAssetTypeCapabilities($this->asset_type);
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
