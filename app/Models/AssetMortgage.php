<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $interest_rate
 * @property int $years
 * @property int $interest_only_years
 * @property string $amount
 */
class AssetMortgage extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'asset_id',
        'year',
        'name',
        'description',
        'amount',
        'interest_rate',
        'interest_rate_type',
        'years',
        'interest_only_years',
        'fee_amount',
        'extra_downpayment_amount',
        'tax_deductible_rate',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'years' => 'integer',
        'interest_only_years' => 'integer',
        'fee_amount' => 'decimal:2',
        'extra_downpayment_amount' => 'decimal:2',
        'tax_deductible_rate' => 'decimal:4',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    // Helper methods
    public function getMonthlyPayment(): float
    {
        if ($this->years <= 0 || $this->interest_rate <= 0) {
            return 0;
        }

        $monthlyRate = (float) $this->interest_rate / 100 / 12;
        $totalPayments = $this->years * 12;

        if ($this->interest_only_years > 0) {
            // Calculate interest-only period
            $interestOnlyPayments = $this->interest_only_years * 12;
            $interestOnlyPayment = (float) $this->amount * $monthlyRate;

            // Calculate amortizing period
            $remainingPayments = $totalPayments - $interestOnlyPayments;
            if ($remainingPayments > 0) {
                $amortizingPayment = (float) $this->amount *
                    ($monthlyRate * pow(1 + $monthlyRate, $remainingPayments)) /
                    (pow(1 + $monthlyRate, $remainingPayments) - 1);

                return $amortizingPayment;
            }

            return $interestOnlyPayment;
        }

        // Standard amortizing loan
        return (float) $this->amount *
            ($monthlyRate * pow(1 + $monthlyRate, $totalPayments)) /
            (pow(1 + $monthlyRate, $totalPayments) - 1);
    }

    public function getAnnualPayment(): float
    {
        return $this->getMonthlyPayment() * 12;
    }

    public function getTotalInterest(): float
    {
        return ($this->getAnnualPayment() * $this->years) - (float) $this->amount;
    }
}
