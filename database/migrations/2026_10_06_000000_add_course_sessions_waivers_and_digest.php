<?php

use App\Support\Mandate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026/27 upgrade. Additive only: new tables and new nullable or defaulted
 * columns, nothing dropped or renamed, so the previous release keeps working
 * against this schema and a code-only rollback is safe. The one data change
 * sets running Elnök/Alelnök/Teamvezető end dates to their statutory mandate
 * end, which the previous release reads the same way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            // "polling" while members vote on the date options, then "scheduled".
            $table->string('schedule_status')->default('scheduled')->after('status');
            // Sessions a member may miss and still complete the course.
            $table->unsignedTinyInteger('allowed_absences')->default(2)->after('capacity');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('session_number')->nullable()->after('course_offering_id');
            $table->string('status')->default('scheduled')->after('type');
            // RFC 5545 SEQUENCE: bumped whenever time or place changes.
            $table->unsignedInteger('sequence')->default(0)->after('status');
            $table->string('checkin_secret', 64)->nullable()->after('participant_count');
            $table->index(['semester_id', 'starts_at']);
            $table->index(['course_offering_id', 'session_number']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('finalized_at');
        });

        Schema::table('enrollment_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            // "digest": one email a day; "immediate": one email per notification.
            $table->string('notification_mode', 20)->default('digest');
            $table->timestamp('digest_sent_at')->nullable();
        });

        Schema::create('course_date_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('course_date_votes', function (Blueprint $table) {
            $table->foreignId('course_date_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['course_date_option_id', 'user_id']);
        });

        Schema::create('obligation_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_offering_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('obligation_rule_code')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('obligation_waiver_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obligation_waiver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('decision');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['obligation_waiver_id', 'user_id']);
        });

        // Mandates: Elnök and Alelnök to 30 June, Teamvezető to the end of
        // the half-year. Running appointments made before this release ended
        // with their semester (or never); align them so a semester switch
        // carries them correctly.
        foreach (DB::table('role_assignments')->whereIn('role', ['president', 'vice_president', 'team_leader'])->whereNull('revoked_at')->get(['id', 'role', 'starts_at']) as $row) {
            $end = Mandate::endFor($row->role, Carbon::parse($row->starts_at));
            DB::table('role_assignments')->where('id', $row->id)->update(['ends_at' => $end?->toDateString()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_waiver_votes');
        Schema::dropIfExists('obligation_waivers');
        Schema::dropIfExists('course_date_votes');
        Schema::dropIfExists('course_date_options');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_mode', 'digest_sent_at']);
        });
        Schema::table('enrollment_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('checked_in_at');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['semester_id', 'starts_at']);
            $table->dropIndex(['course_offering_id', 'session_number']);
            $table->dropColumn(['session_number', 'status', 'sequence', 'checkin_secret']);
        });
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropColumn(['schedule_status', 'allowed_absences']);
        });
    }
};
