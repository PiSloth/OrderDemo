<?php

use App\Http\Controllers\Api\Designs;
use App\Http\Controllers\Api\Psi\HashTagController;
use App\Http\Controllers\Api\Qualities;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\Users;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\Operation\IT\IssueAssignmentController;
use App\Http\Controllers\Operation\IT\IssueMessageController;
use App\Http\Controllers\Operation\IT\IssueStatusController;
use App\Http\Controllers\Psi\Api\ShapeController;
use App\Models\Design;
use App\IssueTracking\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/designs', Designs\Index::class)->name('designs.index');
Route::get('/qualities', Qualities\Index::class)->name('qualities.index');
Route::get('/users', Users\Index::class)->name('users.index');
Route::get('/suppliers', SupplierController::class)->name('suppliers');
Route::get('/shapes', ShapeController::class)->name('psi.shapes');
Route::get('/hashtags', HashTagController::class)->name('hashtag');

Route::middleware('auth:sanctum')->prefix('issues')->group(function () {
    Route::get('/', fn() => Issue::query()->with(['status', 'priority', 'importance', 'category'])->paginate(20));
    Route::patch('/{issue}/status', [IssueStatusController::class, 'update']);
    Route::patch('/{issue}/assignment', [IssueAssignmentController::class, 'update']);
    Route::post('/{issue}/messages', [IssueMessageController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Mobile Submitter App API Routes (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/mobile')->group(function () {
    // Public Auth
    Route::post('/login', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'login']);

    // Authenticated Mobile Submitter Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'profile']);
        Route::post('/auth/logout', [\App\Http\Controllers\Api\Mobile\MobileAuthController::class, 'logout']);

        // Submitter Task Management
        Route::get('/tasks', [\App\Http\Controllers\Api\Mobile\MobileTaskController::class, 'index']);
        Route::get('/tasks/{id}', [\App\Http\Controllers\Api\Mobile\MobileTaskController::class, 'show']);
        Route::post('/tasks/{id}/submit', [\App\Http\Controllers\Api\Mobile\MobileTaskController::class, 'submit']);

        // In-App Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\Mobile\MobileNotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [\App\Http\Controllers\Api\Mobile\MobileNotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\Mobile\MobileNotificationController::class, 'markAllAsRead']);
    });
});

