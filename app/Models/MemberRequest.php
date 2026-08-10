<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberRequest extends Model
{
    protected $fillable = ['user_id', 'semester_id', 'type', 'reason', 'evidence_path', 'status', 'reviewed_by', 'reviewed_at', 'decision_note'];

    protected $casts = ['reviewed_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
