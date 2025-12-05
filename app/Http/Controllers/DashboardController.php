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
use App\Http\Controllers\DashboardController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/announcements/active', [AnnouncementController::class, 'active']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Dashboard - permission checked in controller
    //Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
   // Route::get('/dashboard/charts', [DashboardController::class, 'charts']);

    // Members - with route middleware
    Route::middleware(['permission:view members'])->group(function () {
        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/{id}', [MemberController::class, 'show']);
        Route::get('/members/{id}/contributions', [MemberController::class, 'memberContributions']);
        Route::get('/members/dashboard/stats', [MemberController::class, 'dashboardStats']);
    });

    Route::middleware(['permission:create members'])->post('/members', [MemberController::class, 'store']);
    Route::middleware(['permission:edit members'])->group(function () {
        Route::put('/members/{id}', [MemberController::class, 'update']);
        Route::post('/members/{id}/activate', [MemberController::class, 'activate']);
        Route::post('/members/{id}/deactivate', [MemberController::class, 'deactivate']);
    });
    Route::middleware(['permission:delete members'])->delete('/members/{id}', [MemberController::class, 'destroy']);

    // Contributions - with route middleware
    Route::middleware(['permission:view contributions'])->group(function () {
        Route::get('/contributions', [ContributionController::class, 'index']);
        Route::get('/contributions/{id}', [ContributionController::class, 'show']);
        Route::get('/contributions/report', [ContributionController::class, 'report']);
        Route::get('/members/{memberId}/contributions', [ContributionController::class, 'memberContributions']);
    });

    Route::middleware(['permission:create contributions'])->post('/contributions', [ContributionController::class, 'store']);
    Route::middleware(['permission:edit contributions'])->put('/contributions/{id}', [ContributionController::class, 'update']);
    Route::middleware(['permission:delete contributions'])->delete('/contributions/{id}', [ContributionController::class, 'destroy']);

    // Events - with route middleware
    Route::middleware(['permission:view events'])->group(function () {
        Route::get('/events', [EventController::class, 'index']);
        Route::get('/events/{id}', [EventController::class, 'show']);
        Route::get('/events/upcoming', [EventController::class, 'upcoming']);
        Route::get('/events/past', [EventController::class, 'past']);
        Route::get('/events/calendar', [EventController::class, 'calendar']);
    });

    Route::middleware(['permission:create events'])->post('/events', [EventController::class, 'store']);
    Route::middleware(['permission:edit events'])->put('/events/{id}', [EventController::class, 'update']);
    Route::middleware(['permission:delete events'])->delete('/events/{id}', [EventController::class, 'destroy']);
    
    Route::middleware(['permission:manage event registrations'])->group(function () {
        Route::post('/events/{eventId}/register', [EventController::class, 'register']);
        Route::post('/events/{eventId}/attendance', [EventController::class, 'attendance']);
    });

    // Assets - with route middleware
    Route::middleware(['permission:view assets'])->group(function () {
        Route::get('/assets', [AssetController::class, 'index']);
        Route::get('/assets/{id}', [AssetController::class, 'show']);
        Route::get('/assets/types', [AssetController::class, 'types']);
    });

    Route::middleware(['permission:create assets'])->post('/assets', [AssetController::class, 'store']);
    Route::middleware(['permission:edit assets'])->group(function () {
        Route::put('/assets/{id}', [AssetController::class, 'update']);
        Route::post('/assets/{id}/maintenance', [AssetController::class, 'maintenance']);
    });
    Route::middleware(['permission:delete assets'])->delete('/assets/{id}', [AssetController::class, 'destroy']);
    Route::middleware(['permission:assign assets'])->group(function () {
        Route::post('/assets/{id}/assign', [AssetController::class, 'assign']);
        Route::post('/assets/{id}/unassign', [AssetController::class, 'unassign']);
    });

    // Departments - with route middleware
    Route::middleware(['permission:view departments'])->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/departments/{id}', [DepartmentController::class, 'show']);
        Route::get('/departments/{id}/members', [DepartmentController::class, 'members']);
    });

    Route::middleware(['permission:create departments'])->post('/departments', [DepartmentController::class, 'store']);
    Route::middleware(['permission:edit departments'])->group(function () {
        Route::put('/departments/{id}', [DepartmentController::class, 'update']);
        Route::post('/departments/{id}/add-member', [DepartmentController::class, 'addMember']);
        Route::post('/departments/{id}/remove-member', [DepartmentController::class, 'removeMember']);
    });
    Route::middleware(['permission:delete departments'])->delete('/departments/{id}', [DepartmentController::class, 'destroy']);

    // Announcements - with route middleware
    Route::middleware(['permission:view announcements'])->group(function () {
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
    });

    Route::middleware(['permission:create announcements'])->post('/announcements', [AnnouncementController::class, 'store']);
    Route::middleware(['permission:edit announcements'])->group(function () {
        Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
        Route::post('/announcements/{id}/publish', [AnnouncementController::class, 'publish']);
        Route::post('/announcements/{id}/unpublish', [AnnouncementController::class, 'unpublish']);
    });
    Route::middleware(['permission:delete announcements'])->delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    // Users & Roles - with route middleware
    Route::middleware(['permission:view users'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::get('/roles', [UserController::class, 'roles']);
        Route::get('/permissions', [UserController::class, 'permissions']);
    });

    Route::middleware(['permission:create users'])->post('/users', [UserController::class, 'store']);
    Route::middleware(['permission:edit users'])->group(function () {
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::post('/users/{id}/assign-roles', [UserController::class, 'assignRoles']);
        Route::post('/users/{id}/assign-permissions', [UserController::class, 'assignPermissions']);
        Route::post('/users/{id}/activate', [UserController::class, 'activate']);
        Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
    });
    Route::middleware(['permission:delete users'])->delete('/users/{id}', [UserController::class, 'destroy']);
});