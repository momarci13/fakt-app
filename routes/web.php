<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarFeedController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LifecycleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::redirect('/', '/login')->name('home');
Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('login.store');
Route::get('calendar/feed/{token}.ics', CalendarFeedController::class)
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:12,1')
    ->name('calendar.feed');
Route::get('kurzuskinalat', [CourseController::class, 'publicIndex'])->middleware('throttle:60,1')->name('courses.public');

Route::middleware(['auth', 'approved', 'verified', 'mfa.leader'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('ertesitesek/{notification}/olvasott', [NotificationController::class, 'read'])->whereUuid('notification')->middleware('throttle:mutations')->name('notifications.read');

    Route::get('dokumentumok', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('dokumentumok', [DocumentController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
    Route::get('dokumentumok/{document}/letoltes', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('szervezet', [OrganizationController::class, 'index'])->name('organization.index');
    Route::post('szervezet/kinevezes', [OrganizationController::class, 'appoint'])->middleware(['throttle:sensitive', 'password.confirm'])->name('organization.appoint');
    Route::patch('szervezet/kinevezes/{roleAssignment}/visszavonas', [OrganizationController::class, 'revoke'])->middleware(['throttle:sensitive', 'password.confirm'])->name('organization.revoke');
    Route::post('szervezet/team-tagsag', [OrganizationController::class, 'assignMember'])->middleware(['throttle:sensitive', 'password.confirm'])->name('organization.members.assign');
    Route::post('szervezet/projektek', [OrganizationController::class, 'storeProject'])->middleware(['throttle:sensitive', 'password.confirm'])->name('organization.projects.store');

    Route::get('kurzusok', [CourseController::class, 'index'])->name('courses.index');
    Route::post('kurzusok', [CourseController::class, 'store'])->middleware('throttle:mutations')->name('courses.store');
    Route::post('kurzusok/{course}/jelentkezes', [CourseController::class, 'request'])->middleware('throttle:mutations')->name('courses.request');
    Route::patch('kurzusjelentkezesek/{enrollment}/elbiras', [CourseController::class, 'review'])->middleware('throttle:mutations')->name('courses.review');

    Route::get('naptar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('naptar/esemenyek', [CalendarController::class, 'store'])->middleware('throttle:mutations')->name('calendar.events.store');
    Route::put('naptar/esemenyek/{event}/visszajelzes', [CalendarController::class, 'rsvp'])->middleware('throttle:mutations')->name('calendar.rsvp');
    Route::patch('naptar/esemenyek/{event}/jelenlet', [CalendarController::class, 'finalize'])->middleware('throttle:mutations')->name('calendar.finalize');
    Route::patch('naptar/esemenyek/{event}/jegyzokonyv', [CalendarController::class, 'updateMeeting'])->middleware('throttle:mutations')->name('calendar.meeting.update');
    Route::post('naptar/token', [CalendarController::class, 'rotateToken'])->middleware(['throttle:sensitive', 'password.confirm'])->name('calendar.token.rotate');

    Route::get('feladatok', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('feladatok', [TaskController::class, 'store'])->middleware('throttle:mutations')->name('tasks.store');
    Route::patch('feladatok/{task}', [TaskController::class, 'update'])->middleware('throttle:mutations')->name('tasks.update');
    Route::post('feladatok/{task}/hozzaszolas', [TaskController::class, 'comment'])->middleware('throttle:mutations')->name('tasks.comments.store');

    Route::get('eletut', [LifecycleController::class, 'index'])->name('lifecycle.index');
    Route::post('eletut/kerelmek', [LifecycleController::class, 'storeRequest'])->middleware('throttle:uploads')->name('lifecycle.requests.store');
    Route::get('eletut/kerelmek/{memberRequest}/bizonyitek', [LifecycleController::class, 'downloadEvidence'])->name('lifecycle.evidence.download');
    Route::post('eletut/eredmenyek', [LifecycleController::class, 'addProgress'])->middleware('throttle:mutations')->name('lifecycle.progress.store');

    Route::get('alumni', [AlumniController::class, 'index'])->name('alumni.index');
    Route::post('alumni/mentor', [AlumniController::class, 'requestMentor'])->middleware('throttle:mutations')->name('alumni.mentor.request');

    Route::get('admin', [AdminController::class, 'index'])->name('admin.index');
    Route::middleware(['throttle:sensitive', 'password.confirm'])->group(function () {
        Route::post('admin/meghivok', [AdminController::class, 'invite'])->name('admin.invite');
        Route::post('admin/importok', [AdminController::class, 'stageMemberImport'])->middleware('throttle:uploads')->name('admin.imports.stage');
        Route::post('admin/importok/{importBatch}/alkalmazas', [AdminController::class, 'applyMemberImport'])->name('admin.imports.apply');
        Route::post('admin/importok/{importBatch}/visszavonas', [AdminController::class, 'rollbackMemberImport'])->name('admin.imports.rollback');
        Route::post('admin/felevek', [AdminController::class, 'storeSemester'])->name('admin.semesters.store');
        Route::post('admin/szabalyok', [AdminController::class, 'storeRule'])->name('admin.rules.store');
        Route::post('admin/szabalyok/publikalas', [AdminController::class, 'publishRules'])->name('admin.rules.publish');
        Route::post('admin/kozlemenyek', [AdminController::class, 'announce'])->name('admin.announcements.store');
        Route::patch('admin/kerelmek/{memberRequest}', [AdminController::class, 'reviewMemberRequest'])->name('admin.requests.review');
        Route::patch('admin/regisztraciok/{user}', [AdminController::class, 'reviewRegistration'])->name('admin.registrations.review');
    });
});

require __DIR__.'/settings.php';
