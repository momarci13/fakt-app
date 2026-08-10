<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property Carbon|null $due_at
 */
class Task extends Model
{
    protected $fillable = ['parent_id', 'semester_id', 'org_unit_id', 'project_id', 'created_by', 'title', 'description', 'status', 'priority', 'due_at', 'recurrence_rule', 'visibility'];

    protected $casts = ['due_at' => 'datetime'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps();
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPresident()) {
            return $query;
        }
        $unitIds = $user->managedOrgUnitIds();
        $memberUnitIds = $user->teamMemberships()->where('semester_id', ($nullsafeVariable1 = Semester::active()) ? $nullsafeVariable1->id : null)->pluck('org_unit_id');
        $visibleUnitIds = $unitIds->merge($memberUnitIds)->unique();
        $projectIds = $user->projects()->pluck('projects.id');

        return $query->where(function (Builder $q) use ($user, $visibleUnitIds, $projectIds) {
            $q->where('created_by', $user->id)
                ->orWhereHas('assignees', fn (Builder $a) => $a->where('users.id', $user->id))
                ->when($visibleUnitIds->isNotEmpty(), fn (Builder $u) => $u->orWhereIn('org_unit_id', $visibleUnitIds))
                ->when($projectIds->isNotEmpty(), fn (Builder $p) => $p->orWhereIn('project_id', $projectIds));
        });
    }
}
