<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** @property array<string, mixed> $payload */
class ImportRow extends Model
{
    protected $guarded = [];

    protected $casts = ['payload' => 'array', 'errors' => 'array'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function createdRecord(): MorphTo
    {
        return $this->morphTo();
    }
}
