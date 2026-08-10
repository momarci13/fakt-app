<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentRequest extends Model
{
    protected $fillable = ['course_offering_id', 'user_id', 'preference_rank', 'status', 'reviewed_by', 'reviewed_at', 'decision_note'];

    protected $casts = ['reviewed_at' => 'datetime'];

    /** @return BelongsTo<CourseOffering, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
