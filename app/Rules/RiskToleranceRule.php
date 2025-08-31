<?php

namespace App\Rules;

use App\Models\AssetConfiguration;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RiskToleranceRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! array_key_exists($value, AssetConfiguration::RISK_TOLERANCE_LEVELS)) {
            $validOptions = implode(', ', array_keys(AssetConfiguration::RISK_TOLERANCE_LEVELS));
            $fail("The {$attribute} must be one of: {$validOptions}.");
        }
    }
}
