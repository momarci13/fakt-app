<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'uploaded_by',
        'type',
        'original_name',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'mapping',
        'reconciliation',
        'applied_at',
        'rolled_back_at',
    ];

    protected $casts = [
            'mapping' => 'array',
            'reconciliation' => 'array',
            'applied_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }
}
