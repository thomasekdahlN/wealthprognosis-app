<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $type
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 */
class TaxType extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
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
        return $this->hasMany(AssetType::class, 'tax_type', 'type');
    }
}
