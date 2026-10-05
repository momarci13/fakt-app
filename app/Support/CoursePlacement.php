<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\EnrollmentRequest;
use App\Models\Semester;
use Illuminate\Support\Collection;

/**
 * Proposes the KTSZT "beosztás".
 *
 * General problem: min Σ r_ij x_ij subject to Σ_j x_ij ≤ q_i (per member),
 * Σ_i x_ij ≤ κ_j (capacity), x ∈ {0,1}, maximising placements first.
 *
 * FAKT sets q_i = ∞ (a member may take any number of courses) and gives no
 * priority group. The member constraints then never bind, the problem splits
 * into one independent problem per course, and for each course the optimum is
 * simply the κ_j − (already approved) requests with the best ranks. Ties are
 * broken by a hash of (seed, enrollment id), so the same seed always gives the
 * same, auditable proposal.
 */
final class CoursePlacement
{
    /**
     * @return Collection<int, array{enrollment_id: int, course_id: int, course: string, user_id: int, user: string, rank: int, proposal: string}>
     */
    public static function propose(Semester $semester, int $seed): Collection
    {
        $courses = CourseOffering::query()
            ->where('semester_id', $semester->id)
            ->withCount(['enrollments as approved_count' => fn ($q) => $q->where('status', 'approved')])
            ->get(['id', 'title', 'capacity']);

        $requests = EnrollmentRequest::query()
            ->whereIn('course_offering_id', $courses->modelKeys())
            ->whereIn('status', ['pending', 'waitlisted'])
            ->with('user:id,name')
            ->get();

        return $courses->flatMap(function (CourseOffering $course) use ($requests, $seed) {
            $free = max(0, $course->capacity - (int) $course->approved_count);

            return $requests->where('course_offering_id', $course->id)
                ->sortBy(fn (EnrollmentRequest $request) => sprintf('%02d-%s', $request->preference_rank, hash('sha256', $seed.':'.$request->id)))
                ->values()
                ->map(fn (EnrollmentRequest $request, int $index) => [
                    'enrollment_id' => $request->id,
                    'course_id' => $course->id,
                    'course' => $course->title,
                    'user_id' => (int) $request->user_id,
                    'user' => $request->user?->name ?? '',
                    'rank' => (int) $request->preference_rank,
                    'proposal' => $index < $free ? 'approved' : 'waitlisted',
                ]);
        })->values();
    }
}
