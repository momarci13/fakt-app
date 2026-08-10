<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEntry extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'auditable_type', 'auditable_id', 'event', 'before', 'after', 'ip_address', 'created_at'];

    protected $casts = ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
