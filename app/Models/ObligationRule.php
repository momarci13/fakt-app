<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObligationRule extends Model
{
    protected $fillable = ['semester_id', 'code', 'name', 'description', 'kind', 'threshold', 'configuration', 'version', 'effective_at', 'published_at', 'published_by', 'is_active'];

    protected function casts(): array
    {
        return ['configuration' => 'array', 'effective_at' => 'datetime', 'published_at' => 'datetime', 'is_active' => 'boolean', 'threshold' => 'float'];
    }
}
