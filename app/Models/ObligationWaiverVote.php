<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObligationWaiverVote extends Model
{
    protected $fillable = ['obligation_waiver_id', 'user_id', 'decision', 'note'];

    public function waiver(): BelongsTo
    {
        return $this->belongsTo(ObligationWaiver::class, 'obligation_waiver_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
