<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property string $member_status */
class MemberProfile extends Model
{
    protected $fillable = ['user_id', 'member_status', 'cohort_year', 'first_year', 'phone', 'bio', 'expertise', 'alumni_visible', 'mentor_available', 'mentor_seeking', 'diploma_awarded_at', 'exited_at'];

    protected $casts = ['first_year' => 'boolean', 'alumni_visible' => 'boolean', 'mentor_available' => 'boolean', 'mentor_seeking' => 'boolean', 'diploma_awarded_at' => 'date', 'exited_at' => 'date'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
