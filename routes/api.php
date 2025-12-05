<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\UserController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/announcements/active', [AnnouncementController::class, 'active']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Members
    Route::apiResource('members', MemberController::class);
    Route::get('/members/{id}/contributions', [MemberController::class, 'memberContributions']);
    Route::post('/members/{id}/activate', [MemberController::class, 'activate']);
    Route::post('/members/{id}/deactivate', [MemberController::class, 'deactivate']);
    Route::get('/members/dashboard/stats', [MemberController::class, 'dashboardStats']);

    // Contributions
    Route::apiResource('contributions', ContributionController::class);
    Route::get('/contributions/report', [ContributionController::class, 'report']);

    // Events
    Route::apiResource('events', EventController::class);
    Route::get('/events/upcoming', [EventController::class, 'upcoming']);
    Route::get('/events/past', [EventController::class, 'past']);
    Route::get('/events/calendar', [EventController::class, 'calendar']);
    Route::post('/events/{eventId}/register', [EventController::class, 'register']);
    Route::post('/events/{eventId}/attendance', [EventController::class, 'attendance']);

    // Assets
    Route::apiResource('assets', AssetController::class);
    Route::post('/assets/{id}/assign', [AssetController::class, 'assign']);
    Route::post('/assets/{id}/unassign', [AssetController::class, 'unassign']);
    Route::post('/assets/{id}/maintenance', [AssetController::class, 'maintenance']);
    Route::get('/assets/types', [AssetController::class, 'types']);

    // Departments
    Route::apiResource('departments', DepartmentController::class);
    Route::post('/departments/{id}/add-member', [DepartmentController::class, 'addMember']);
    Route::post('/departments/{id}/remove-member', [DepartmentController::class, 'removeMember']);
    Route::get('/departments/{id}/members', [DepartmentController::class, 'members']);

    // Announcements
    Route::apiResource('announcements', AnnouncementController::class);
    Route::post('/announcements/{id}/publish', [AnnouncementController::class, 'publish']);
    Route::post('/announcements/{id}/unpublish', [AnnouncementController::class, 'unpublish']);

    // Users & Roles
    Route::apiResource('users', UserController::class);
    Route::post('/users/{id}/assign-roles', [UserController::class, 'assignRoles']);
    Route::post('/users/{id}/assign-permissions', [UserController::class, 'assignPermissions']);
    Route::post('/users/{id}/activate', [UserController::class, 'activate']);
    Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::get('/roles', [UserController::class, 'roles']);
    Route::get('/permissions', [UserController::class, 'permissions']);
});
