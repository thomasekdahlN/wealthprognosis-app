<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $team_id
 * @property int $year
 * @property int $asset_id
 * @property int|null $asset_configuration_id
 * @property string|null $description
 * @property string|null $income_changerate
 * @property string|null $expence_changerate
 * @property string|null $asset_changerate
 * @property \App\Models\Asset|null $asset
 * @property \App\Models\AssetConfiguration|null $assetConfiguration
 *
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo asset()
 */
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
        'asset_configuration_id',

        // Unified description for the year (consolidated from income/expense/asset/mortgage)
        'description',

        // Income data
        'income_amount',
        'income_factor',
        'income_rule',
        'income_transfer',
        'income_source',
        'income_changerate',
        'income_repeat',

        // Expense data
        'expence_amount',
        'expence_factor',
        'expence_rule',
        'expence_transfer',
        'expence_source',
        'expence_changerate',
        'expence_repeat',

        // Asset data
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

        // Mortgage data
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
        return $this->belongsTo(AssetConfiguration::class, 'asset_configuration_id');
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
        return app(\App\Services\Utilities\HelperService::class)->normalizeFactor($this->income_factor);
    }

    /**
     * Convert factor enum to numeric multiplier for calculations
     */
    public function getExpenseFactorMultiplier(): int
    {
        return app(\App\Services\Utilities\HelperService::class)->normalizeFactor($this->expence_factor);
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
        if (! $asset || ! $asset->asset_configuration_id) {
            return [
                'income_source' => 'nullable|string',
                'expence_source' => 'nullable|string',
                'asset_source' => 'nullable|string',
            ];
        }

        // Get valid source asset codes/IDs with lower sort order and same configuration
        $validAssets = Asset::where('asset_configuration_id', $asset->asset_configuration_id)
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

    protected function description(): Attribute
    {
        return Attribute::make(
            get: function ($value): ?string {
                if ($value === null) {
                    return null;
                }

                // Ensure numeric-only values render safely in RichEditor (TipTap) by wrapping in HTML
                if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                    return '<p>'.(string) $value.'</p>';
                }

                return (string) $value;
            },
            set: function ($value): ?string {
                if ($value === null) {
                    return null;
                }

                return (string) $value;
            }
        );
    }
}
