<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mentorship extends Model
{
    protected $fillable = ['mentor_id', 'mentee_id', 'status', 'focus', 'starts_at', 'ends_at'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date'];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }
}
