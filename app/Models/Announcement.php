<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['semester_id', 'org_unit_id', 'author_id', 'title', 'body', 'audience', 'is_pinned', 'published_at', 'expires_at'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean', 'published_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
