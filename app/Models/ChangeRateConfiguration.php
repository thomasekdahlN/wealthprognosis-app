<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRateConfiguration extends Model
{
    use Auditable, HasFactory;

    protected $table = 'prognosis_change_rates';

    protected $fillable = [
        'user_id',
        'team_id',
        'scenario_type',
        'asset_type',
        'year',
        'change_rate',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'change_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'year' => 'integer',
    ];
}
