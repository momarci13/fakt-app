<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgUnit extends Model
{
    protected $fillable = ['semester_id', 'parent_id', 'type', 'name', 'slug', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<OrgUnit, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }
}
