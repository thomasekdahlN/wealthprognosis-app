<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetType extends Model
{
    use Auditable, HasFactory;

    protected static function booted(): void
    {
        parent::booted();

        // Apply team-based filtering
        static::addGlobalScope(new TeamScope);
    }

    protected $fillable = [
        'type',
        'name',
        'description',
        'category',
        'icon',
        'color',
        'is_active',
        'is_private',
        'is_company',
        'is_tax_optimized',
        'is_liquid',
        'can_generate_income',
        'can_generate_expenses',
        'can_have_mortgage',
        'can_have_market_value',
        'sort_order',
        'income_changerate',
        'expence_changerate',
        'asset_changerate',
        'asset_category_id',
        'tax_type_id',
        'user_id',
        'team_id',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_private' => 'boolean',
        'is_company' => 'boolean',
        'is_tax_optimized' => 'boolean',
        'is_liquid' => 'boolean',
        'can_generate_income' => 'boolean',
        'can_generate_expenses' => 'boolean',
        'can_have_mortgage' => 'boolean',
        'can_have_market_value' => 'boolean',
        'sort_order' => 'integer',
        'income_changerate' => 'string',
        'expence_changerate' => 'string',
        'asset_changerate' => 'string',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopeCompany($query)
    {
        return $query->where('is_company', true);
    }

    public function scopeTaxOptimized($query)
    {
        return $query->where('is_tax_optimized', true);
    }

    public function scopeLiquid($query)
    {
        return $query->where('is_liquid', true);
    }

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
