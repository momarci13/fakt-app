<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = ['semester_id', 'org_unit_id', 'lead_user_id', 'created_by', 'name', 'description', 'status', 'starts_at', 'ends_at'];

    /**
     * Calendar dates, not instants. The plain 'date' cast serializes to a UTC
     * timestamp, so Budapest midnight on the 15th reaches the browser as
     * 23:00Z on the 14th and renders one day early outside Budapest time.
     */
    protected $casts = ['starts_at' => 'date:Y-m-d', 'ends_at' => 'date:Y-m-d'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')->withTimestamps();
    }
}
