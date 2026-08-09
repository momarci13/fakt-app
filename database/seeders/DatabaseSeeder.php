<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\CourseOffering;
use App\Models\EnrollmentRequest;
use App\Models\Event;
use App\Models\MemberProfile;
use App\Models\Mentorship;
use App\Models\ObligationRule;
use App\Models\OrgUnit;
use App\Models\ProgressRecord;
use App\Models\Project;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\Task;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $semester = Semester::query()->create([
            'name' => '2026/27 tavasz', 'starts_at' => '2027-02-01', 'ends_at' => '2027-06-30',
            'is_active' => true, 'course_selection_open' => true, 'rules_published_at' => now(),
        ]);

        $users = collect([
            ['Elnök Demo', 'elnok@fakt.local', 'active'],
            ['Közösség és Marketing Alelnök', 'kozosseg.alelnok@fakt.local', 'active'],
            ['Szervezetfejlesztés és Társadalmi Felelősségvállalás Alelnök', 'szervfejl.alelnok@fakt.local', 'active'],
            ['Szakmaiság Alelnök', 'szakmaisag.alelnok@fakt.local', 'active'],
            ['Pénzügy és Vállalati Kapcsolatok Alelnök', 'penzugy.alelnok@fakt.local', 'active'],
            ['Közösség Teamvezető', 'kozosseg.tv@fakt.local', 'active'],
            ['Marketing Teamvezető', 'marketing.tv@fakt.local', 'active'],
            ['Szakmaiság Teamvezető', 'szakmaisag.tv@fakt.local', 'active'],
            ['Szervezetfejlesztés Teamvezető', 'szervfejl.tv@fakt.local', 'active'],
            ['Társadalmi Felelősségvállalás Teamvezető', 'tarsfelel.tv@fakt.local', 'active'],
            ['Pénzügy és Vállalati Kapcsolatok Teamvezető', 'penzugy.tv@fakt.local', 'active'],
            ['Kovács Anna', 'anna@fakt.local', 'active'], ['Nagy Bence', 'bence@fakt.local', 'active'],
            ['Szabó Dóra', 'dora@fakt.local', 'active'], ['Tóth Márton', 'marton@fakt.local', 'senior'],
            ['Varga Luca', 'luca@fakt.local', 'active'], ['Kiss Gergő', 'gergo@fakt.local', 'active'],
            ['Alumni Júlia', 'julia.alumni@fakt.local', 'alumni'], ['Alumni Péter', 'peter.alumni@fakt.local', 'alumni'],
        ])->map(function (array $item, int $index) {
            $user = User::query()->create([
                'name' => $item[0], 'email' => $item[1], 'email_verified_at' => now(),
                'password' => Hash::make('Fakt2027!'), 'calendar_token' => Str::random(48),
            ]);
            MemberProfile::query()->create([
                'user_id' => $user->id, 'member_status' => $item[2], 'cohort_year' => $item[2] === 'alumni' ? 2018 + $index % 3 : 2024 + $index % 3,
                'first_year' => $index >= 11 && $index <= 13, 'alumni_visible' => $item[2] === 'alumni',
                'mentor_available' => $item[2] === 'alumni', 'expertise' => $item[2] === 'alumni' ? ($index % 2 ? 'Pénzügy és vállalatértékelés' : 'Adatelemzés és karriertervezés') : null,
                'bio' => $item[2] === 'alumni' ? 'FAKT alumni, örömmel segítek szakmai és karrierkérdésekben.' : null,
            ]);

            return $user;
        });

        [$president, $vpCommunity, $vpOrg, $vpProfessional, $vpFinance] = $users->take(5)->all();
        $leaderUsers = $users->slice(5, 6)->values();

        $portfolios = collect([
            ['kozosseg-marketing', 'Közösség és Marketing', '#287a61'],
            ['szervfejl-tarsfelel', 'Szervezetfejlesztés és Társadalmi Felelősségvállalás', '#b7791f'],
            ['szakmaisag-portfolio', 'Szakmaiság', '#245b8f'],
            ['penzugy-vallalati', 'Pénzügy és Vállalati Kapcsolatok', '#7c3a85'],
        ])->map(fn (array $p) => OrgUnit::query()->create(['semester_id' => $semester->id, 'type' => 'portfolio', 'slug' => $p[0], 'name' => $p[1], 'color' => $p[2]]));

        $teamSpecs = [
            [$portfolios[0], 'kozosseg', 'Közösség', '#308330'], [$portfolios[0], 'marketing', 'Marketing', '#3182ce'],
            [$portfolios[2], 'szakmaisag', 'Szakmaiság', '#245b8f'], [$portfolios[1], 'szervezetfejlesztes', 'Szervezetfejlesztés', '#d69e2e'],
            [$portfolios[1], 'tarsadalmi-felelossegvallalas', 'Társadalmi Felelősségvállalás', '#dd6b20'], [$portfolios[3], 'penzugy-vallalati-kapcsolatok', 'Pénzügy és Vállalati Kapcsolatok', '#7c3a85'],
        ];
        $teams = collect($teamSpecs)->map(fn (array $t) => OrgUnit::query()->create(['semester_id' => $semester->id, 'parent_id' => $t[0]->id, 'type' => 'team', 'slug' => $t[1], 'name' => $t[2], 'color' => $t[3]]));

        RoleAssignment::query()->create(['semester_id' => $semester->id, 'user_id' => $president->id, 'role' => 'president', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30']);
        collect([$vpCommunity, $vpOrg, $vpProfessional, $vpFinance])->each(fn (User $user, int $i) => RoleAssignment::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $portfolios[$i]->id, 'user_id' => $user->id, 'appointed_by' => $president->id, 'role' => 'vice_president', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30']));
        $leaderUsers->each(fn (User $user, int $i) => RoleAssignment::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $teams[$i]->id, 'user_id' => $user->id, 'appointed_by' => $teams[$i]->parent_id === $portfolios[0]->id ? $vpCommunity->id : ($teams[$i]->parent_id === $portfolios[1]->id ? $vpOrg->id : ($teams[$i]->parent_id === $portfolios[2]->id ? $vpProfessional->id : $vpFinance->id)), 'role' => 'team_leader', 'starts_at' => '2027-01-01', 'ends_at' => '2027-06-30']));

        $members = $users->slice(11, 6)->values();
        $members->each(fn (User $user, int $i) => TeamMembership::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $teams[$i]->id, 'user_id' => $user->id, 'assigned_by' => $leaderUsers[$i]->id, 'starts_at' => '2027-02-01', 'ends_at' => '2027-06-30']));

        $project = Project::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $teams[1]->id, 'lead_user_id' => $members[0]->id, 'created_by' => $vpCommunity->id, 'name' => 'FAKT Tavaszi Fesztivál', 'description' => 'A tavaszi szakmai és közösségi programsorozat megszervezése.', 'status' => 'active', 'starts_at' => '2027-02-01', 'ends_at' => '2027-05-31']);
        $project->members()->sync([$members[0]->id, $members[1]->id, $members[4]->id]);
        RoleAssignment::query()->create(['semester_id' => $semester->id, 'user_id' => $members[0]->id, 'appointed_by' => $vpCommunity->id, 'role' => 'project_leader', 'starts_at' => '2027-02-01', 'ends_at' => '2027-05-31']);

        $courses = collect([
            ['Network Science a gyakorlatban', 'Adatelemzés és statisztika', 'Dr. Minta Ágnes', 14, '2027-02-10 17:30:00', 'C109'],
            ['Vállalatértékelés', 'Pénzügyek', 'Minta Balázs, CFA', 16, '2027-02-11 18:00:00', 'E2001'],
            ['Vezetői készségek és coaching', 'Menedzsment', 'Minta Kata', 12, '2027-02-12 17:00:00', 'FAKT iroda'],
            ['Python adatelemzés', 'Programozás', 'Minta Dániel', 15, '2027-02-15 18:00:00', 'C203'],
        ])->map(fn (array $c) => CourseOffering::query()->create(['semester_id' => $semester->id, 'created_by' => $vpProfessional->id, 'title' => $c[0], 'category' => $c[1], 'description' => 'Interaktív, kiscsoportos FAKT kurzus gyakorlati feladatokkal.', 'instructor_name' => $c[2], 'capacity' => $c[3], 'status' => 'published', 'starts_at' => $c[4], 'ends_at' => date('Y-m-d H:i:s', strtotime($c[4].' +90 minutes')), 'location' => $c[5], 'recurrence_rule' => 'FREQ=WEEKLY;COUNT=10']));
        EnrollmentRequest::query()->create(['course_offering_id' => $courses[0]->id, 'user_id' => $president->id, 'preference_rank' => 1, 'status' => 'approved', 'reviewed_by' => $vpProfessional->id, 'reviewed_at' => now()]);

        $events = collect([
            ['Tavaszi Nyitógyűlés', 'assembly', '2027-02-05 18:00:00', '2027-02-05 20:30:00', 'E2001', 'company', 'required', null, null],
            ['Közösségi est', 'community', '2027-02-09 19:00:00', '2027-02-09 22:00:00', 'FAKT iroda', 'members', 'optional', $teams[0]->id, null],
            ['Fesztivál projektindító', 'project', '2027-02-08 17:00:00', '2027-02-08 18:30:00', 'FAKT iroda', 'scope', 'required', $teams[1]->id, $project->id],
            ['Alumni mentor est', 'alumni', '2027-02-18 18:00:00', '2027-02-18 20:00:00', 'FAKT iroda', 'alumni', 'optional', null, null],
        ])->map(fn (array $e) => Event::query()->create(['semester_id' => $semester->id, 'organizer_id' => $president->id, 'title' => $e[0], 'type' => $e[1], 'starts_at' => $e[2], 'ends_at' => $e[3], 'location' => $e[4], 'visibility' => $e[5], 'obligation' => $e[6], 'org_unit_id' => $e[7], 'project_id' => $e[8], 'description' => 'Részletek és napirend az esemény előtt frissülnek.', 'agenda' => $e[1] === 'assembly' ? "1. Féléves célok\n2. Vezetőségi stratégia\n3. Kurzusok" : null, 'quorum_required' => $e[1] === 'assembly' ? 20 : null]));
        Attendance::query()->create(['event_id' => $events[0]->id, 'user_id' => $president->id, 'rsvp_status' => 'attending']);

        $tasks = collect([
            ['Nyitógyűlés napirendjének véglegesítése', 'review', 'urgent', '2027-02-03 18:00:00', null, null, [$president->id, $vpOrg->id]],
            ['Kurzusbeosztás ellenőrzése', 'in_progress', 'high', '2027-02-06 12:00:00', $teams[2]->id, null, [$vpProfessional->id, $leaderUsers[2]->id]],
            ['Fesztivál kommunikációs terv', 'todo', 'high', '2027-02-12 18:00:00', $teams[1]->id, $project->id, [$members[0]->id, $members[1]->id]],
            ['Heti Team check-in', 'done', 'normal', '2027-02-04 16:00:00', $teams[0]->id, null, [$leaderUsers[0]->id, $members[0]->id]],
        ])->map(function (array $t) use ($semester, $president) {
            $task = Task::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $t[4], 'project_id' => $t[5], 'created_by' => $president->id, 'title' => $t[0], 'description' => 'A részletek és kapcsolódó anyagok a feladatlap megjegyzéseiben találhatók.', 'status' => $t[1], 'priority' => $t[2], 'due_at' => $t[3], 'visibility' => 'scope']);
            $task->assignees()->sync($t[6]);

            return $task;
        });

        collect([
            ['course_count', 'Elvégzett FAKT kurzusok', 'minimum', 4], ['community_event', 'Közösségi alkalmak', 'minimum', 3],
            ['active_semester', 'Aktív félévek', 'minimum', 3], ['accepted_role_semester', 'Elfogadott tisztségviselői félévek', 'minimum', 4],
            ['tdk', 'TDK dolgozat', 'minimum', 1], ['net_error_points', 'Nettó hibapontok', 'maximum', 3],
        ])->each(fn (array $r) => ObligationRule::query()->create(['semester_id' => $semester->id, 'code' => $r[0], 'name' => $r[1], 'description' => 'A szabály a publikált féléves szakmai követelmények alapján értékelődik.', 'kind' => $r[2], 'threshold' => $r[3], 'version' => 1, 'effective_at' => $semester->starts_at, 'published_at' => now(), 'published_by' => $president->id]));
        collect([['course_count', 3], ['community_event', 2], ['active_semester', 2], ['accepted_role_semester', 2], ['tdk', 1], ['net_error_points', 0]])->each(fn (array $p) => ProgressRecord::query()->create(['user_id' => $president->id, 'semester_id' => $semester->id, 'type' => $p[0], 'value' => $p[1], 'status' => 'approved', 'approved_by' => $vpProfessional->id, 'approved_at' => now()]));

        Announcement::query()->create(['semester_id' => $semester->id, 'author_id' => $president->id, 'title' => 'Elindult a tavaszi félév tervezése', 'body' => 'Nézd át a kurzuskínálatot, ellenőrizd a naptáradat, és állítsd be a preferenciáidat a jelentkezési időszak végéig.', 'audience' => 'members', 'is_pinned' => true, 'published_at' => now()]);
        Announcement::query()->create(['semester_id' => $semester->id, 'author_id' => $vpCommunity->id, 'title' => 'Mentorprogram jelentkezés', 'body' => 'Az alumni mentorok profiljai már elérhetők. Válassz érdeklődési területet, és küldj mentorálási kérést.', 'audience' => 'all', 'published_at' => now()]);

        Mentorship::query()->create(['mentor_id' => $users[17]->id, 'mentee_id' => $members[3]->id, 'status' => 'active', 'focus' => 'Karriertervezés és pénzügyi pálya', 'starts_at' => '2027-02-01']);
    }
}
