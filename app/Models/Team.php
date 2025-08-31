<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'is_active',
        'settings',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function prognoses(): HasMany
    {
        return $this->hasMany(\App\Models\PrognosisType::class, 'team_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assetTypes(): HasMany
    {
        return $this->hasMany(AssetType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all users that belong to this team
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get users with current team set to this team
     */
    public function currentUsers(): HasMany
    {
        return $this->hasMany(User::class, 'current_team_id');
    }

    /**
     * Add a user to this team
     */
    public function addUser(User $user, string $role = 'member'): void
    {
        if (! $this->users()->where('user_id', $user->id)->exists()) {
            $this->users()->attach($user->id, [
                'role' => $role,
                'joined_at' => now(),
            ]);
        }
    }

    /**
     * Remove a user from this team
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);

        // If this was their current team, clear it
        if ($user->current_team_id === $this->id) {
            $user->current_team_id = null;
            $user->save();
        }
    }

    /**
     * Check if a user belongs to this team
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Get team members by role
     */
    public function getUsersByRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->users()->wherePivot('role', $role)->get();
    }
}
