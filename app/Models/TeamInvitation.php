<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $team_id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property int $invited_by
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $cancelled_at
 * @property int|null $user_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $created_checksum
 * @property string|null $updated_checksum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read User $invitedBy
 * @property-read User|null $acceptedByUser
 *
 * @method static Builder<static> pending()
 * @method static Builder<static> expired()
 */
class TeamInvitation extends Model
{
    /** @use HasFactory<TeamInvitationFactory> */
    use Auditable, HasFactory;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    /** @var list<string> */
    public const ROLES = [self::ROLE_ADMIN, self::ROLE_MEMBER];

    protected $fillable = [
        'team_id',
        'email',
        'role',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
        'cancelled_at',
        'user_id',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TeamInvitation $invitation): void {
            if (empty($invitation->token)) {
                $invitation->token = self::generateToken();
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
            if (empty($invitation->role)) {
                $invitation->role = self::ROLE_MEMBER;
            }
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '<=', now());
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->cancelled_at === null
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->accepted_at === null
            && $this->cancelled_at === null
            && $this->expires_at->isPast();
    }

    public function status(): string
    {
        if ($this->accepted_at !== null) {
            return 'accepted';
        }
        if ($this->cancelled_at !== null) {
            return 'cancelled';
        }
        if ($this->expires_at->isPast()) {
            return 'expired';
        }

        return 'pending';
    }
}
