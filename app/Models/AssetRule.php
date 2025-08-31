<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRule extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'asset_id',
        'year',
        'rule_type',
        'rule_value',
        'description',
        'transfer_to_asset',
        'source_asset',
        'repeat',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'repeat' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Rule types based on the existing system
    public const RULE_TYPES = [
        'percentage_add' => 'Add Percentage (+10%)',
        'percentage_subtract' => 'Subtract Percentage (-10%)',
        'percentage_calculate' => 'Calculate Percentage (10%)',
        'amount_add' => 'Add Amount (+1000)',
        'amount_subtract' => 'Subtract Amount (-1000)',
        'fraction_add' => 'Add Fraction (+1/10)',
        'fraction_subtract' => 'Subtract Fraction (-1/10)',
        'fraction_calculate' => 'Calculate Fraction (1/10)',
        'fraction_diminishing_add' => 'Add Diminishing Fraction (+1|10)',
        'fraction_diminishing_subtract' => 'Subtract Diminishing Fraction (-1|10)',
        'fraction_diminishing_calculate' => 'Calculate Diminishing Fraction (1|10)',
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

    // Helper methods
    public function getRuleTypeLabel(): string
    {
        return self::RULE_TYPES[$this->rule_type] ?? $this->rule_type;
    }

    public function parseRule(): array
    {
        // Parse rule_value like "+10%", "-1000", "1/10", "1|10" etc.
        $value = trim($this->rule_value);

        if (preg_match('/^([+-]?)(\d+(?:\.\d+)?)%$/', $value, $matches)) {
            return [
                'type' => 'percentage',
                'operator' => $matches[1] ?: '=',
                'value' => (float) $matches[2],
            ];
        }

        if (preg_match('/^([+-]?)(\d+(?:\.\d+)?)$/', $value, $matches)) {
            return [
                'type' => 'amount',
                'operator' => $matches[1] ?: '=',
                'value' => (float) $matches[2],
            ];
        }

        if (preg_match('/^([+-]?)(\d+)\/(\d+)$/', $value, $matches)) {
            return [
                'type' => 'fraction',
                'operator' => $matches[1] ?: '=',
                'numerator' => (int) $matches[2],
                'denominator' => (int) $matches[3],
            ];
        }

        if (preg_match('/^([+-]?)(\d+)\|(\d+)$/', $value, $matches)) {
            return [
                'type' => 'fraction_diminishing',
                'operator' => $matches[1] ?: '=',
                'numerator' => (int) $matches[2],
                'denominator' => (int) $matches[3],
            ];
        }

        return [
            'type' => 'unknown',
            'operator' => '=',
            'value' => $value,
        ];
    }
}
