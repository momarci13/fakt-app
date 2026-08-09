<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('calendar_token', 64)->nullable()->unique();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('course_selection_open')->default(false);
            $table->timestamp('rules_published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('member_status')->default('active')->index();
            $table->unsignedSmallInteger('cohort_year')->nullable();
            $table->boolean('first_year')->default(false);
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('expertise')->nullable();
            $table->boolean('alumni_visible')->default(false);
            $table->boolean('mentor_available')->default(false);
            $table->boolean('mentor_seeking')->default(false);
            $table->date('diploma_awarded_at')->nullable();
            $table->date('exited_at')->nullable();
            $table->timestamps();
        });

        Schema::create('org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('org_units')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 20)->default('#308330');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['semester_id', 'slug']);
        });

        Schema::create('role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->index();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'semester_id', 'role']);
        });

        Schema::create('team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['semester_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->primary(['project_id', 'user_id']);
        });

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('instructor_name');
            $table->string('instructor_email')->nullable();
            $table->unsignedSmallInteger('capacity')->default(15);
            $table->string('status')->default('published')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location')->nullable();
            $table->string('recurrence_rule')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('preference_rank')->default(1);
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->unique(['course_offering_id', 'user_id']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_offering_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->index();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('location')->nullable();
            $table->string('visibility')->default('company')->index();
            $table->string('obligation')->default('optional');
            $table->text('description')->nullable();
            $table->text('agenda')->nullable();
            $table->text('minutes')->nullable();
            $table->text('decision_summary')->nullable();
            $table->unsignedSmallInteger('quorum_required')->nullable();
            $table->unsignedSmallInteger('participant_count')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rsvp_status')->default('pending');
            $table->string('final_status')->nullable();
            $table->text('excuse_reason')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo')->index();
            $table->string('priority')->default('normal')->index();
            $table->dateTime('due_at')->nullable()->index();
            $table->string('recurrence_rule')->nullable();
            $table->string('visibility')->default('scope');
            $table->timestamps();
        });

        Schema::create('task_assignees', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['task_id', 'user_id']);
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('obligation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('kind');
            $table->decimal('threshold', 8, 2)->default(0);
            $table->json('configuration')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->dateTime('effective_at');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['semester_id', 'code', 'version']);
        });

        Schema::create('progress_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('value', 8, 2)->default(1);
            $table->string('status')->default('pending')->index();
            $table->text('note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('member_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->text('reason');
            $table->string('evidence_path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('audience')->default('members')->index();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('documentable');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('category')->index();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('visibility')->default('scope');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mentorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('proposed')->index();
            $table->string('focus')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['mentor_id', 'mentee_id']);
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('members');
            $table->string('original_name');
            $table->string('status')->default('staged')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->json('mapping')->nullable();
            $table->json('reconciliation')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('payload');
            $table->json('errors')->nullable();
            $table->string('status')->default('valid')->index();
            $table->nullableMorphs('created_record');
            $table->timestamps();
            $table->unique(['import_batch_id', 'row_number']);
        });

        Schema::create('audit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('auditable');
            $table->string('event')->index();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_entries');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('mentorships');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('member_requests');
        Schema::dropIfExists('progress_records');
        Schema::dropIfExists('obligation_rules');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('events');
        Schema::dropIfExists('enrollment_requests');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('team_memberships');
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('org_units');
        Schema::dropIfExists('member_profiles');
        Schema::dropIfExists('semesters');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropColumn(['invited_by', 'invited_at', 'last_seen_at', 'calendar_token']);
        });
    }
};
