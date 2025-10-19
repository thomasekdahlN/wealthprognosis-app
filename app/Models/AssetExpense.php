<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetExpense extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'asset_id',
        'year',
        'name',
        'description',
        'amount',
        'factor',
        'change_rate_type',
        'custom_change_rate',
        'rule',
        'transfer_to_asset',
        'source_asset',
        'repeat',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
        'factor' => 'string',
        'custom_change_rate' => 'decimal:4',
        'repeat' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function transferToAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'transfer_to_asset');
    }

    public function sourceAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'source_asset');
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
    public function getFactorMultiplier(): int
    {
        return $this->factor === self::FACTOR_MONTHLY ? 12 : 1;
    }

    /**
     * Get total annual amount
     */
    public function getTotalAmount(): float
    {
        return $this->amount * $this->getFactorMultiplier();
    }
}
