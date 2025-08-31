<?php

namespace App\Rules;

use App\Models\Asset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AssetGroupRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! array_key_exists($value, Asset::GROUPS)) {
            $validOptions = implode(', ', array_keys(Asset::GROUPS));
            $fail("The {$attribute} must be one of: {$validOptions}.");
        }
    }
}
