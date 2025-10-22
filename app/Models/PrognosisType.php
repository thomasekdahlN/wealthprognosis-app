<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrognosisType extends Model
{
    use Auditable, HasFactory;

    protected $table = 'prognoses';

    protected $fillable = [
        'user_id',
        'team_id',
        'code',
        'label',
        'icon',
        'color',
        'description',
        'public',
        'is_active',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'public' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get options array of active prognosis types [code => label].
     */
    public static function options(): array
    {
        return static::query()->active()->orderBy('code')->pluck('label', 'code')->all();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function prognosisResults(): HasMany
    {
        return $this->hasMany(PrognosisResult::class, 'prognosis_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (auth()->check()) {
                $model->created_by = $model->created_by ?: auth()->id();
                $model->updated_by = $model->updated_by ?: auth()->id();
            }
        });

        static::updating(function (self $model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
