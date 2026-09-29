<?php

namespace App\Support;

use App\Models\OrgUnit;
use App\Models\Semester;

/**
 * The statutory organisation: four Alelnök portfolios (SZMSZ 12.2) and the six
 * Teams beneath them (SZMSZ 12.3).
 *
 * Org units are per semester and nothing in the interface creates them, so a
 * fresh production database, or a newly opened semester, would otherwise have
 * no portfolios or Teams at all: no Alelnök or Teamvezető could be appointed and
 * the KTSZT would have no ex-officio seats.
 *
 * Idempotent: keyed by (semester_id, slug), which is unique in the schema.
 * The slugs match the demo DatabaseSeeder so dev and production agree, and the
 * Szakmaiság slugs are what Ktszt and AccessScope use to find that portfolio.
 */
final class OrgStructure
{
    /** slug => [name, colour]. Colours come from the brand chart ramp. */
    private const PORTFOLIOS = [
        'kozosseg-marketing' => ['Közösség és Marketing', '#1d6a5a'],
        'szervfejl-tarsfelel' => ['Szervezetfejlesztés és Társadalmi Felelősségvállalás', '#8a6a1c'],
        'szakmaisag-portfolio' => ['Szakmaiság', '#308330'],
        'penzugy-vallalati' => ['Pénzügy és Vállalati Kapcsolatok', '#6b4e86'],
    ];

    /** slug => [name, portfolio slug]. A Team takes its portfolio's colour. */
    private const TEAMS = [
        'kozosseg' => ['Közösség', 'kozosseg-marketing'],
        'marketing' => ['Marketing', 'kozosseg-marketing'],
        'szakmaisag' => ['Szakmaiság', 'szakmaisag-portfolio'],
        'szervezetfejlesztes' => ['Szervezetfejlesztés', 'szervfejl-tarsfelel'],
        'tarsadalmi-felelossegvallalas' => ['Társadalmi Felelősségvállalás', 'szervfejl-tarsfelel'],
        'penzugy-vallalati-kapcsolatok' => ['Pénzügy és Vállalati Kapcsolatok', 'penzugy-vallalati'],
    ];

    /**
     * Create whatever part of the statutory structure the semester is missing.
     *
     * @return int Number of org units created.
     */
    public static function ensureFor(Semester $semester): int
    {
        $created = 0;
        $portfolios = [];

        foreach (self::PORTFOLIOS as $slug => [$name, $colour]) {
            $unit = OrgUnit::query()->firstOrCreate(
                ['semester_id' => $semester->id, 'slug' => $slug],
                ['type' => 'portfolio', 'name' => $name, 'color' => $colour, 'is_active' => true],
            );
            $created += (int) $unit->wasRecentlyCreated;
            $portfolios[$slug] = $unit;
        }

        foreach (self::TEAMS as $slug => [$name, $portfolioSlug]) {
            $parent = $portfolios[$portfolioSlug];
            $unit = OrgUnit::query()->firstOrCreate(
                ['semester_id' => $semester->id, 'slug' => $slug],
                ['type' => 'team', 'parent_id' => $parent->id, 'name' => $name, 'color' => $parent->color, 'is_active' => true],
            );
            $created += (int) $unit->wasRecentlyCreated;
        }

        return $created;
    }
}
