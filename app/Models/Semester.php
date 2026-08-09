<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 */
class Semester extends Model
{
    protected $fillable = ['name', 'starts_at', 'ends_at', 'is_active', 'course_selection_open', 'rules_published_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean', 'course_selection_open' => 'boolean', 'rules_published_at' => 'datetime'];
    }

    public function orgUnits(): HasMany
    {
        return $this->hasMany(OrgUnit::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }

    public static function activeOrFail(): self
    {
        return static::active() ?? abort(404, 'Nincs aktív félév beállítva.');
    }
}
