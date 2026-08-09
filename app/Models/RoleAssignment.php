<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends Model
{
    protected $fillable = ['semester_id', 'org_unit_id', 'user_id', 'appointed_by', 'role', 'starts_at', 'ends_at', 'revoked_at', 'note'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'revoked_at' => 'datetime'];
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /** @return BelongsTo<OrgUnit, $this> */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appointed_by');
    }
}
