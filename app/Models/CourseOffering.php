<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $title
 * @property int $capacity
 * @property string|null $location
 * @property string|null $description
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 */
class CourseOffering extends Model
{
    protected $fillable = ['semester_id', 'created_by', 'title', 'category', 'description', 'instructor_name', 'instructor_email', 'capacity', 'status', 'starts_at', 'ends_at', 'location', 'recurrence_rule'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /** @return HasMany<EnrollmentRequest, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
