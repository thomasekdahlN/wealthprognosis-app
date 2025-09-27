<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxProperty extends Model
{
    use HasFactory;

    protected $table = 'tax_property';

    protected $fillable = [
        'country_code',
        'year',
        'code',
        // Norway (per user spec)
        'municipality',
        'has_tax_on_homes',
        'has_tax_on_companies',
        // DB columns (permille)
        'tax_home_permill',
        'tax_company_permill',
        'deduction',
        'is_active',
        'user_id',
        'team_id',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'year' => 'integer',
        'tax_home_permill' => 'decimal:3',
        'tax_company_permill' => 'decimal:3',
        'deduction' => 'decimal:2',
        'has_tax_on_homes' => 'boolean',
        'has_tax_on_companies' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtolower($countryCode));
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function assets(): HasMany
    {
        // If assets keep a string code, relation can be defined differently in Asset.
        return $this->hasMany(Asset::class, 'tax_property', 'code');
    }

    // Convenience attributes to align with JSON naming (permill)
    protected function taxHomePermill(): Attribute
    {
        return Attribute::make(
            get: function (): float|int|null {
                $raw = $this->attributes['tax_home_permill'] ?? null;
                if ($raw === null) {
                    return null;
                }
                $val = (float) $raw;

                return $val == 0.0 ? null : $val;
            },
            set: function ($value): array {
                if ($value === null || $value === '' || (is_numeric($value) && (float) $value == 0.0)) {
                    return ['tax_home_permill' => null];
                }

                return ['tax_home_permill' => round((float) $value, 3)];
            },
        );
    }

    protected function taxCompanyPermill(): Attribute
    {
        return Attribute::make(
            get: function (): float|int|null {
                $raw = $this->attributes['tax_company_permill'] ?? null;
                if ($raw === null) {
                    return null;
                }
                $val = (float) $raw;

                return $val == 0.0 ? null : $val;
            },
            set: function ($value): array {
                if ($value === null || $value === '' || (is_numeric($value) && (float) $value == 0.0)) {
                    return ['tax_company_permill' => null];
                }

                return ['tax_company_permill' => round((float) $value, 3)];
            },
        );
    }
}
