<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $location
 * @property string|null $description
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 */
class Event extends Model
{
    protected $fillable = ['semester_id', 'org_unit_id', 'project_id', 'course_offering_id', 'organizer_id', 'title', 'type', 'starts_at', 'ends_at', 'location', 'visibility', 'obligation', 'description', 'agenda', 'minutes', 'decision_summary', 'quorum_required', 'participant_count'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
