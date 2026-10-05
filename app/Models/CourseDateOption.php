<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $location
 */
class CourseDateOption extends Model
{
    protected $fillable = ['course_offering_id', 'starts_at', 'ends_at', 'location'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_date_votes')->withTimestamps();
    }
}
