<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMembership extends Model
{
    protected $fillable = ['semester_id', 'org_unit_id', 'user_id', 'assigned_by', 'starts_at', 'ends_at'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date'];

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
