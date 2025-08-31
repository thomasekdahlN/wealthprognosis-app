<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AssetRuleValidation implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow empty/null values
        if (empty($value)) {
            return;
        }

        $value = trim($value);

        // Check if the value matches any of the supported patterns
        if (! $this->isValidRulePattern($value)) {
            $fail(__('validation.asset_rule_invalid', [
                'attribute' => $attribute,
                'examples' => '+10%, -10%, 10%, +1000, -1000, +1/10, -1/10, 1/10, +1|10, -1|10, 1|10',
            ]));
        }
    }

    /**
     * Check if the value matches any supported rule pattern.
     */
    private function isValidRulePattern(string $value): bool
    {
        // Pattern 1: Percentage operations (+10%, -10%, 10%)
        if (preg_match('/^[+-]?\d+(\.\d+)?%$/', $value)) {
            return true;
        }

        // Pattern 2: Fixed amount operations (+1000, -1000)
        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
            return true;
        }

        // Pattern 3: Fraction operations (+1/10, -1/10, 1/10)
        if (preg_match('/^[+-]?\d+\/\d+$/', $value)) {
            // Validate denominator is not zero
            $parts = explode('/', str_replace(['+', '-'], '', $value));
            if (count($parts) === 2 && (int) $parts[1] !== 0) {
                return true;
            }
        }

        // Pattern 4: Decreasing fraction operations (+1|10, -1|10, 1|10)
        if (preg_match('/^[+-]?\d+\|\d+$/', $value)) {
            // Validate denominator is not zero
            $parts = explode('|', str_replace(['+', '-'], '', $value));
            if (count($parts) === 2 && (int) $parts[1] !== 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.asset_rule_invalid');
    }
}
