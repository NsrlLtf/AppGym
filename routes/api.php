<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\SesiGymController;
use App\Http\Controllers\Api\TransaksiMemberController;
use App\Http\Controllers\Api\WebhookController;

Route::prefix('v1')->group(function () {

    // Route Autentikasi
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
    // Route untuk Superadmin dan Admin (Pendaftaran Admin dan Member) DONE
    Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->prefix('auth')->group(function () {
        Route::post('/register/admin', [AuthController::class, 'registerAdminUser']);
        Route::post('/register/member', [MemberController::class, 'register']);
    });

    // Route untuk Superadmin (Get admin DONE)
    Route::middleware(['auth:sanctum', 'role:superadmin'])->prefix('superadmin')->group(function () {
        Route::prefix('admins')->group(function () {
            Route::get('/', [AuthController::class, 'getAllAdmins']);
            Route::put('/{id}', [AuthController::class, 'updateAdmin']);
            Route::delete('/{id}', [AuthController::class, 'deleteAdmin']);
        });

        //Webhook Management (webhooks store,index,logs/1 DONE )
        Route::prefix('webhooks')->group(function () {
            Route::get('/', [WebhookController::class, 'index']);
            Route::post('/', [WebhookController::class, 'store']);
            Route::get('/{webhook}', [WebhookController::class, 'show']);
            Route::put('/{webhook}', [WebhookController::class, 'update']);
            Route::delete('/{webhook}', [WebhookController::class, 'destroy']);
            Route::get('/logs', [WebhookController::class, 'logs']);
            Route::get('/logs/{webhookId}', [WebhookController::class, 'webhookLogs']);
            Route::post('/test/{webhook}', [WebhookController::class, 'test']);
        });
    });

    // Route untuk Admin dan Superadmin (get, get{1}, DONE)
    Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->prefix('admin')->group(function () {
        Route::get('/members', [MemberController::class, 'getAllMembers']);
        Route::get('/members/{id}', [MemberController::class, 'getMember']);
        Route::put('/members/{id}', [MemberController::class, 'updateMember']);
        Route::delete('/members/{id}', [MemberController::class, 'deleteMember']);

        Route::prefix('reports')->group(function () {
            Route::get('/membership', [MemberController::class, 'membershipReport']);
            Route::get('/gym-usage', [SesiGymController::class, 'gymUsageReport']);
            Route::get('/revenue', [TransaksiMemberController::class, 'revenueReport']);
        });

        Route::post('/gym-sessions/force-checkout/{sessionId}', [SesiGymController::class, 'forceCheckOut']);
    });

    // Route untuk Member (get profile DONE)
    Route::middleware(['auth:sanctum'])->prefix('members')->group(function () {
        Route::get('/profile', [MemberController::class, 'getProfile']);
        Route::put('/profile', [MemberController::class, 'updateProfile']);
        Route::get('/activity-report/{memberId}', [MemberController::class, 'memberActivityReport']);
        Route::get('/transactions', [TransaksiMemberController::class, 'memberTransactionHistory']);
    });

    // Route untuk Gym Sessions ( occupany & history Done )
    Route::middleware(['auth:sanctum'])->prefix('gym-sessions')->group(function () {
        Route::get('/history', [SesiGymController::class, 'sessionHistory']);
        Route::get('/occupancy', [SesiGymController::class, 'getCurrentOccupancy']);
    });

    // Route untuk Transaksi Membership
    Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->prefix('membership')->group(function () {
        Route::post('/renew', [TransaksiMemberController::class, 'renewMembership']);
        Route::get('/packages', [TransaksiMemberController::class, 'getMembershipPackages']);
    });

    // Route untuk RFID Device with API Key (DONE)
    Route::middleware(['api.key'])->group(function () {
        Route::post('/device/check-in', [SesiGymController::class, 'checkIn']);
        Route::post('/device/check-out', [SesiGymController::class, 'checkOut']);
    });

    
    // Fallback route ( Jika mendapatkan endpoint yang tidak ada )
    Route::fallback(function () {
        return response()->json([
            'message' => 'Endpoint tidak ditemukan',
            'status' => 404
        ], 404);
    });
});
