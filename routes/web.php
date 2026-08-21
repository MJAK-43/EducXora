<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CourseSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\LearnerExportController;
use App\Http\Controllers\LearnerPhotoController;
use App\Http\Controllers\OrganizationRoleController;
use App\Http\Controllers\OrganizationSelectionController;
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Controllers\OrganizationUserController;
use App\Http\Controllers\Platform\OrganizationController as PlatformOrganizationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', fn (): Response => Inertia::render('Home'))->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware(['auth', 'auth.session', 'active', 'fresh.session'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::middleware('verified')->group(function (): void {
        Route::get('/organizations/select', [OrganizationSelectionController::class, 'index'])->name('organizations.select');
        Route::post('/organizations/select', [OrganizationSelectionController::class, 'store'])->name('organizations.switch');

        Route::prefix('platform')->name('platform.')->middleware('platform.admin')->group(function (): void {
            Route::get('/organizations', [PlatformOrganizationController::class, 'index'])->name('organizations.index');
            Route::post('/organizations', [PlatformOrganizationController::class, 'store'])->middleware('password.confirm')->name('organizations.store');
            Route::patch('/organizations/{organization}', [PlatformOrganizationController::class, 'update'])->middleware('password.confirm')->name('organizations.update');
            Route::patch('/organizations/{organization}/status', [PlatformOrganizationController::class, 'status'])->middleware('password.confirm')->name('organizations.status');
        });

        Route::middleware(['tenant', 'tenant.member', 'organization.active'])->group(function (): void {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::get('/learners', [LearnerController::class, 'index'])->name('learners.index');
            Route::get('/learners/create', [LearnerController::class, 'create'])->name('learners.create');
            Route::post('/learners', [LearnerController::class, 'store'])->name('learners.store');
            Route::get('/learners/export', LearnerExportController::class)->middleware('throttle:10,1')->name('learners.export');
            Route::get('/learners/{learnerUuid}', [LearnerController::class, 'show'])->name('learners.show');
            Route::get('/learners/{learnerUuid}/edit', [LearnerController::class, 'edit'])->name('learners.edit');
            Route::patch('/learners/{learnerUuid}', [LearnerController::class, 'update'])->name('learners.update');
            Route::patch('/learners/{learnerUuid}/archive', [LearnerController::class, 'archive'])->name('learners.archive');
            Route::patch('/learners/{learnerUuid}/restore', [LearnerController::class, 'restore'])->name('learners.restore');
            Route::get('/learners/{learnerUuid}/photo', LearnerPhotoController::class)->middleware('throttle:60,1')->name('learners.photo');
            Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
            Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
            Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
            Route::get('/groups/{groupUuid}', [GroupController::class, 'show'])->name('groups.show');
            Route::get('/groups/{groupUuid}/edit', [GroupController::class, 'edit'])->name('groups.edit');
            Route::patch('/groups/{groupUuid}', [GroupController::class, 'update'])->name('groups.update');
            Route::patch('/groups/{groupUuid}/archive', [GroupController::class, 'archive'])->name('groups.archive');
            Route::patch('/groups/{groupUuid}/restore', [GroupController::class, 'restore'])->name('groups.restore');
            Route::post('/groups/{groupUuid}/learners', [GroupController::class, 'attach'])->name('groups.learners.attach');
            Route::delete('/groups/{groupUuid}/learners/{learnerUuid}', [GroupController::class, 'detach'])->name('groups.learners.detach');
            Route::get('/schedule', [CourseSessionController::class, 'index'])->name('schedule.index');
            Route::get('/schedule/create', [CourseSessionController::class, 'create'])->name('schedule.create');
            Route::post('/schedule', [CourseSessionController::class, 'store'])->name('schedule.store');
            Route::get('/schedule/{sessionUuid}/edit', [CourseSessionController::class, 'edit'])->name('schedule.edit');
            Route::patch('/schedule/{sessionUuid}', [CourseSessionController::class, 'update'])->name('schedule.update');
            Route::patch('/schedule/{sessionUuid}/cancel', [CourseSessionController::class, 'cancel'])->name('schedule.cancel');
            Route::get('/organization/settings', [OrganizationSettingsController::class, 'edit'])->name('organization.settings.edit');
            Route::patch('/organization/settings', [OrganizationSettingsController::class, 'update'])->middleware('password.confirm')->name('organization.settings.update');
            Route::get('/organization/users', [OrganizationUserController::class, 'index'])->name('organization.users.index');
            Route::patch('/organization/users/{membership}', [OrganizationUserController::class, 'update'])->name('organization.users.update');
            Route::delete('/organization/users/{membership}', [OrganizationUserController::class, 'destroy'])->name('organization.users.destroy');
            Route::post('/organization/invitations', [InvitationController::class, 'store'])->name('organization.invitations.store');
            Route::get('/organization/roles', [OrganizationRoleController::class, 'index'])->name('organization.roles.index');
            Route::post('/organization/roles', [OrganizationRoleController::class, 'store'])->middleware('password.confirm')->name('organization.roles.store');
            Route::patch('/organization/roles/{role}', [OrganizationRoleController::class, 'update'])->middleware('password.confirm')->name('organization.roles.update');
            Route::delete('/organization/roles/{role}', [OrganizationRoleController::class, 'destroy'])->middleware('password.confirm')->name('organization.roles.destroy');
        });
    });
});

Route::middleware('local.only')->get('/dev/ui', fn (): Response => Inertia::render('Dev/UiKit'))->name('dev.ui');
