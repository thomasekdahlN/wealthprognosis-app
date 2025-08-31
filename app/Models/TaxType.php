<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxType extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'type',
        'name',
        'description',
        'default_rate',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'default_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function assetTypes(): HasMany
    {
        return $this->hasMany(AssetType::class);
    }
}
