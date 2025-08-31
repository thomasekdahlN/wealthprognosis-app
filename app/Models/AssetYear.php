<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetYear extends Model
{
    use Auditable, HasFactory;

    protected static function booted(): void
    {
        parent::booted();

        // Apply team-based filtering
        static::addGlobalScope(new TeamScope);
    }

    protected $fillable = [
        'user_id',
        'team_id',
        'year',
        'asset_id',
        'asset_owner_id',
        'income_name',
        'income_description',
        'income_amount',
        'income_factor',
        'income_rule',
        'income_transfer',
        'income_source',
        'income_changerate',
        'income_repeat',
        'expence_name',
        'expence_description',
        'expence_amount',
        'expence_factor',
        'expence_rule',
        'expence_transfer',
        'expence_source',
        'expence_changerate',
        'expence_repeat',
        'asset_name',
        'asset_description',
        'asset_market_amount',
        'asset_acquisition_amount',
        'asset_equity_amount',
        'asset_taxable_initial_amount',
        'asset_paid_amount',
        'asset_changerate',
        'asset_rule',
        'asset_transfer',
        'asset_source',
        'asset_repeat',
        'mortgage_name',
        'mortgage_description',
        'mortgage_amount',
        'mortgage_years',
        'mortgage_interest',
        'mortgage_interest_only_years',
        'mortgage_extra_downpayment_amount',
        'mortgage_gebyr',
        'mortgage_tax',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'year' => 'integer',
        'income_amount' => 'decimal:2',
        'income_factor' => 'string',
        'income_source' => 'string',
        'income_repeat' => 'boolean',
        'expence_amount' => 'decimal:2',
        'expence_factor' => 'string',
        'expence_repeat' => 'boolean',
        'asset_market_amount' => 'decimal:2',
        'asset_acquisition_amount' => 'decimal:2',
        'asset_equity_amount' => 'decimal:2',
        'asset_taxable_initial_amount' => 'decimal:2',
        'asset_paid_amount' => 'decimal:2',
        'asset_repeat' => 'boolean',
        'mortgage_amount' => 'decimal:2',
        'mortgage_years' => 'integer',
        'mortgage_interest_only_years' => 'integer',
        'mortgage_gebyr' => 'decimal:2',
        'mortgage_tax' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assetConfiguration(): BelongsTo
    {
        return $this->belongsTo(AssetConfiguration::class);
    }

    // Factor enum constants
    public const FACTOR_MONTHLY = 'monthly';
    public const FACTOR_YEARLY = 'yearly';

    /**
     * Get valid factor options
     */
    public static function getFactorOptions(): array
    {
        return [
            self::FACTOR_MONTHLY => 'Monthly',
            self::FACTOR_YEARLY => 'Yearly',
        ];
    }

    /**
     * Convert factor enum to numeric multiplier for calculations
     */
    public function getIncomeFactorMultiplier(): int
    {
        return $this->income_factor === self::FACTOR_MONTHLY ? 12 : 1;
    }

    /**
     * Convert factor enum to numeric multiplier for calculations
     */
    public function getExpenseFactorMultiplier(): int
    {
        return $this->expence_factor === self::FACTOR_MONTHLY ? 12 : 1;
    }

    /**
     * Get total annual income amount
     */
    public function getTotalIncomeAmount(): float
    {
        return $this->income_amount * $this->getIncomeFactorMultiplier();
    }

    /**
     * Get total annual expense amount
     */
    public function getTotalExpenseAmount(): float
    {
        return $this->expence_amount * $this->getExpenseFactorMultiplier();
    }

    /**
     * Get validation rules for source fields
     */
    public static function getSourceValidationRules(int $assetId): array
    {
        $asset = Asset::find($assetId);
        if (! $asset || ! $asset->asset_owner_id) {
            return [
                'income_source' => 'nullable|string',
                'expence_source' => 'nullable|string',
                'asset_source' => 'nullable|string',
            ];
        }

        // Get valid source asset codes/IDs with lower sort order and same owner
        $validAssets = Asset::where('asset_owner_id', $asset->asset_owner_id)
            ->where('sort_order', '<', $asset->sort_order)
            ->get();

        $validSourcePatterns = [];
        foreach ($validAssets as $validAsset) {
            $prefix = $validAsset->asset_type;
            $validSourcePatterns[] = $prefix.'.$year.income.amount';
            $validSourcePatterns[] = $prefix.'.$year.expence.amount';
            $validSourcePatterns[] = $prefix.'.$year.asset.amount';
        }

        return [
            'income_source' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($validSourcePatterns) {
                    if ($value && ! in_array($value, $validSourcePatterns)) {
                        $fail("The {$attribute} must reference an asset from the same owner with lower sort order.");
                    }
                },
            ],
            'expence_source' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($validSourcePatterns) {
                    if ($value && ! in_array($value, $validSourcePatterns)) {
                        $fail("The {$attribute} must reference an asset from the same owner with lower sort order.");
                    }
                },
            ],
            'asset_source' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($validSourcePatterns) {
                    if ($value && ! in_array($value, $validSourcePatterns)) {
                        $fail("The {$attribute} must reference an asset from the same owner with lower sort order.");
                    }
                },
            ],
        ];
    }
}
