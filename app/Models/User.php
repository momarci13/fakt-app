<?php

namespace App\Models;

use App\Support\RequestMemo;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'approval_status' => 'approved',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'invited_by',
        'invited_at',
        'last_seen_at',
        'calendar_token',
        'approval_status',
        'registration_note',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'notification_mode',
        'digest_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'calendar_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'invited_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'digest_sent_at' => 'datetime',
    ];

    /** @return HasOne<MemberProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }
    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
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

        return $this->activeRoleAssignments($semesterId)->pluck('role')->values();
    }

    /**
     * Active role assignments in a semester, read once per request.
     *
     * @return Collection<int, RoleAssignment>
     */
    public function activeRoleAssignments(?int $semesterId = null): Collection
    {
        if ($semesterId === null) {
            $semesterId = Semester::active()?->id;
        }

        if (! $semesterId) {
            return collect();
        }

        return RequestMemo::remember("roles:{$this->id}:{$semesterId}", fn () => $this->roles()
            ->with('orgUnit.children')
            ->where('semester_id', $semesterId)
            ->whereNull('revoked_at')
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->get());
    }

    public function isPresident(): bool
    {
        return $this->activeRoleNames()->contains('president');
    }
    /** Elnökség: the Elnök and the Alelnökök (SZMSZ). */
    public function isElnoksegMember(): bool
    {
        return $this->activeRoleNames()->intersect(['president', 'vice_president'])->isNotEmpty();
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
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

        return RequestMemo::remember("managed:{$this->id}:{$semester->id}", function () use ($semester) {
            if ($this->isPresident()) {
                return OrgUnit::query()->where('semester_id', $semester->id)->pluck('id');
            }

            return $this->activeRoleAssignments($semester->id)
                ->flatMap(function (RoleAssignment $role) {
                    if (! $role->orgUnit) {
                        return [];
                    }

                    return $role->role === 'vice_president'
                        ? array_merge([$role->org_unit_id], $role->orgUnit->children->pluck('id')->all())
                        : [$role->org_unit_id];
                })->unique()->values();
        });
    }
}
