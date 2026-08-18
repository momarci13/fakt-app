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
Route::get('calendar/feed/{token}.ics', CalendarFeedController::class)->middleware('throttle:30,1')->name('calendar.feed');
Route::get('kurzuskinalat', [CourseController::class, 'publicIndex'])->name('courses.public');

Route::middleware(['auth', 'approved', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('ertesitesek/{notification}/olvasott', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('dokumentumok', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('dokumentumok', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('dokumentumok/{document}/letoltes', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('szervezet', [OrganizationController::class, 'index'])->name('organization.index');
    Route::post('szervezet/kinevezes', [OrganizationController::class, 'appoint'])->name('organization.appoint');
    Route::patch('szervezet/kinevezes/{roleAssignment}/visszavonas', [OrganizationController::class, 'revoke'])->name('organization.revoke');
    Route::post('szervezet/team-tagsag', [OrganizationController::class, 'assignMember'])->name('organization.members.assign');
    Route::post('szervezet/projektek', [OrganizationController::class, 'storeProject'])->name('organization.projects.store');

    Route::get('kurzusok', [CourseController::class, 'index'])->name('courses.index');
    Route::post('kurzusok', [CourseController::class, 'store'])->name('courses.store');
    Route::post('kurzusok/{course}/jelentkezes', [CourseController::class, 'request'])->name('courses.request');
    Route::patch('kurzusjelentkezesek/{enrollment}/elbiras', [CourseController::class, 'review'])->name('courses.review');

    Route::get('naptar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('naptar/esemenyek', [CalendarController::class, 'store'])->name('calendar.events.store');
    Route::put('naptar/esemenyek/{event}/visszajelzes', [CalendarController::class, 'rsvp'])->name('calendar.rsvp');
    Route::patch('naptar/esemenyek/{event}/jelenlet', [CalendarController::class, 'finalize'])->name('calendar.finalize');
    Route::patch('naptar/esemenyek/{event}/jegyzokonyv', [CalendarController::class, 'updateMeeting'])->name('calendar.meeting.update');
    Route::post('naptar/token', [CalendarController::class, 'rotateToken'])->name('calendar.token.rotate');

    Route::get('feladatok', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('feladatok', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('feladatok/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('feladatok/{task}/hozzaszolas', [TaskController::class, 'comment'])->name('tasks.comments.store');

    Route::get('eletut', [LifecycleController::class, 'index'])->name('lifecycle.index');
    Route::post('eletut/kerelmek', [LifecycleController::class, 'storeRequest'])->name('lifecycle.requests.store');
    Route::get('eletut/kerelmek/{memberRequest}/bizonyitek', [LifecycleController::class, 'downloadEvidence'])->name('lifecycle.evidence.download');
    Route::post('eletut/eredmenyek', [LifecycleController::class, 'addProgress'])->name('lifecycle.progress.store');

    Route::get('alumni', [AlumniController::class, 'index'])->name('alumni.index');
    Route::post('alumni/mentor', [AlumniController::class, 'requestMentor'])->name('alumni.mentor.request');

    Route::get('admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('admin/meghivok', [AdminController::class, 'invite'])->name('admin.invite');
    Route::post('admin/importok', [AdminController::class, 'stageMemberImport'])->name('admin.imports.stage');
    Route::post('admin/importok/{importBatch}/alkalmazas', [AdminController::class, 'applyMemberImport'])->name('admin.imports.apply');
    Route::post('admin/importok/{importBatch}/visszavonas', [AdminController::class, 'rollbackMemberImport'])->name('admin.imports.rollback');
    Route::post('admin/felevek', [AdminController::class, 'storeSemester'])->name('admin.semesters.store');
    Route::post('admin/szabalyok', [AdminController::class, 'storeRule'])->name('admin.rules.store');
    Route::post('admin/szabalyok/publikalas', [AdminController::class, 'publishRules'])->name('admin.rules.publish');
    Route::post('admin/kozlemenyek', [AdminController::class, 'announce'])->name('admin.announcements.store');
    Route::patch('admin/kerelmek/{memberRequest}', [AdminController::class, 'reviewMemberRequest'])->name('admin.requests.review');
    Route::patch('admin/regisztraciok/{user}', [AdminController::class, 'reviewRegistration'])->name('admin.registrations.review');
});

require __DIR__.'/settings.php';
