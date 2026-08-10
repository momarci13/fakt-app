<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'invited_by',
        'invited_at',
        'last_seen_at',
        'calendar_token',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'invited_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /** @return HasOne<MemberProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }
    /** @return HasMany<RoleAssignment, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')->withTimestamps();
    }
    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees')->withTimestamps();
    }
    public function enrollments(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }
    public function activeRoleNames(?int $semesterId = null): Collection
    {
        if ($semesterId === null) {
            $semester = Semester::active();
            $semesterId = $semester ? $semester->id : null;
        }

        if (! $semesterId) {
            return collect();
        }

        return $this->roles()
            ->where('semester_id', $semesterId)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()))
            ->pluck('role');
    }
    public function isPresident(): bool
    {
        return $this->activeRoleNames()->contains('president');
    }
    public function isLeader(): bool
    {
        return $this->activeRoleNames()->intersect(['president', 'vice_president', 'team_leader', 'project_leader'])->isNotEmpty();
    }
    public function managedOrgUnitIds(): Collection
    {
        $semester = Semester::active();
        if (! $semester) {
            return collect();
        }
        if ($this->isPresident()) {
            return OrgUnit::query()->where('semester_id', $semester->id)->pluck('id');
        }

        $roles = $this->roles()->with('orgUnit.children')->where('semester_id', $semester->id)->whereNull('revoked_at')->get();

        return $roles->flatMap(function (RoleAssignment $role) {
            if (! $role->orgUnit) {
                return [];
            }

            return $role->role === 'vice_president'
                ? array_merge([$role->org_unit_id], $role->orgUnit->children->pluck('id')->all())
                : [$role->org_unit_id];
        })->unique()->values();
    }
}
