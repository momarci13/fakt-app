<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Mandate periods.
 *
 * - Elnök and Alelnök: one academic year, 1 July – 30 June.
 * - Teamvezető: one half-year, 1 July – 31 December or 1 January – 30 June.
 * - Every other role ends with its semester.
 *
 * An assignment's effective end is its ends_at, or, for older rows without
 * one, the end of the mandate that contains its start date.
 */
final class Mandate
{
    public const YEARLY = ['president', 'vice_president'];

    public const HALF_YEARLY = ['team_leader'];

    public static function endFor(string $role, CarbonInterface $start, ?CarbonInterface $semesterEnd = null): ?Carbon
    {
        $start = Carbon::parse($start->toDateString());

        if (in_array($role, self::YEARLY, true)) {
            $year = $start->month >= 7 ? $start->year + 1 : $start->year;

            return Carbon::create($year, 6, 30)->startOfDay();
        }

        if (in_array($role, self::HALF_YEARLY, true)) {
            return $start->month >= 7
                ? Carbon::create($start->year, 12, 31)->startOfDay()
                : Carbon::create($start->year, 6, 30)->startOfDay();
        }

        return $semesterEnd ? Carbon::parse($semesterEnd->toDateString()) : null;
    }

    public static function effectiveEnd(string $role, CarbonInterface $start, ?CarbonInterface $endsAt, ?CarbonInterface $semesterEnd = null): ?Carbon
    {
        return $endsAt ? Carbon::parse($endsAt->toDateString()) : self::endFor($role, $start, $semesterEnd);
    }
}
