<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressRecord extends Model
{
    protected $fillable = ['user_id', 'semester_id', 'type', 'source_type', 'source_id', 'value', 'status', 'note', 'approved_by', 'approved_at'];

    protected $casts = ['approved_at' => 'datetime', 'value' => 'float'];
}
