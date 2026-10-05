<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObligationWaiver extends Model
{
    protected $fillable = ['semester_id', 'user_id', 'course_offering_id', 'obligation_rule_code', 'requested_by', 'reason', 'status', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    /** @return HasMany<ObligationWaiverVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(ObligationWaiverVote::class);
    }
}
