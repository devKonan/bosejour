<?php

use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomAvailabilityController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\HostProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminHostController;
use App\Http\Controllers\Admin\AdminAccommodationController;
use App\Http\Controllers\Admin\InspectionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/accommodations', [AccommodationController::class, 'index']);
Route::get('/accommodations/{id}', [AccommodationController::class, 'show'])->where('id', '[0-9]+');
Route::get('/accommodations/{id}/reviews', [ReviewController::class, 'index'])->where('id', '[0-9]+');

// Payment Methods (public - accessible sans authentification)
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);

// Webhook pour les notifications de paiement (sans authentification)
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Endpoints pour vérification RBAC (utilisés par NestJS)
        Route::get('/users/{id}/check-role/{role}', [AuthController::class, 'checkRole'])->where('id', '[0-9]+');
        Route::get('/users/{id}/check-permission/{permission}', [AuthController::class, 'checkPermission'])->where('id', '[0-9]+');
        Route::get('/users/{id}/roles', [AuthController::class, 'getUserRoles'])->where('id', '[0-9]+');
        Route::get('/users/{id}/permissions', [AuthController::class, 'getUserPermissions'])->where('id', '[0-9]+');

        // Accommodations (Host) - Must be before routes with {id} parameter
        Route::middleware('role:host')->group(function () {
            // Host Profile
            Route::get('/host/profile', [HostProfileController::class, 'show']);
        Route::post('/host/profile', [HostProfileController::class, 'update']); // POST pour FormData
        
        Route::get('/accommodations/my', [AccommodationController::class, 'myAccommodations'])->name('accommodations.my');
        Route::post('/accommodations', [AccommodationController::class, 'store']);
        Route::post('/accommodations/{id}/media', [AccommodationController::class, 'uploadMedia'])->where('id', '[0-9]+');
        Route::post('/accommodations/{id}/appointments', [AppointmentController::class, 'store'])->where('id', '[0-9]+');
        Route::put('/accommodations/{id}', [AccommodationController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/accommodations/{id}', [AccommodationController::class, 'destroy'])->where('id', '[0-9]+');

        // Rooms
        Route::get('/accommodations/{accommodationId}/rooms', [RoomController::class, 'index']);
        Route::get('/accommodations/{accommodationId}/rooms/{id}', [RoomController::class, 'show']);
        Route::post('/accommodations/{accommodationId}/rooms', [RoomController::class, 'store']);
        Route::put('/accommodations/{accommodationId}/rooms/{id}', [RoomController::class, 'update']);
        Route::delete('/accommodations/{accommodationId}/rooms/{id}', [RoomController::class, 'destroy']);

        // Room Availability
        Route::get('/accommodations/{accommodationId}/rooms/{roomId}/availability', [RoomAvailabilityController::class, 'index']);
        Route::get('/accommodations/{accommodationId}/rooms/{roomId}/calendar', [RoomAvailabilityController::class, 'getCalendar']);
        Route::post('/accommodations/{accommodationId}/rooms/{roomId}/availability', [RoomAvailabilityController::class, 'store']);
        Route::post('/accommodations/{accommodationId}/rooms/{roomId}/availability/bulk', [RoomAvailabilityController::class, 'bulkUpdate']);
        Route::put('/accommodations/{accommodationId}/rooms/{roomId}/availability/{id}', [RoomAvailabilityController::class, 'update']);
        Route::delete('/accommodations/{accommodationId}/rooms/{roomId}/availability/{id}', [RoomAvailabilityController::class, 'destroy']);
    });

    // Public room routes
    Route::get('/accommodations/{accommodationId}/rooms', [RoomController::class, 'index']);
    Route::get('/accommodations/{accommodationId}/rooms/{id}', [RoomController::class, 'show']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/host/overview', [BookingController::class, 'hostReservations'])->middleware('role:host');
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);

    // Payment Methods (détails nécessitent authentification)
    Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);

    // Payments
    Route::post('/bookings/{bookingId}/payment/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/{paymentId}/process', [PaymentController::class, 'process']);
    Route::post('/payments/{paymentId}/callback', [PaymentController::class, 'callback']);
    Route::get('/payments/{paymentId}', [PaymentController::class, 'show']);

    // Revenue
    Route::get('/revenue/admin', [RevenueController::class, 'adminRevenue'])->middleware('role:admin');
    Route::get('/revenue/host', [RevenueController::class, 'hostRevenue'])->middleware('role:host');
    Route::get('/revenue/commission-rate', [RevenueController::class, 'getCommissionRate']);
    Route::put('/revenue/commission-rate', [RevenueController::class, 'updateCommissionRate'])->middleware('role:admin');

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::get('/subscriptions/my', [SubscriptionController::class, 'mySubscriptions']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
    Route::put('/subscriptions/{id}', [SubscriptionController::class, 'update']);
    Route::post('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);

    // Analytics
    Route::prefix('analytics')->group(function () {
        // More specific routes first
        Route::get('/host/accommodation/{id}', [AnalyticsController::class, 'accommodationStats'])->middleware('role:host')->where('id', '[0-9]+');
        Route::get('/host', [AnalyticsController::class, 'hostDashboard'])->middleware('role:host');
        Route::get('/admin', [AnalyticsController::class, 'adminDashboard'])->middleware('role:admin');
        Route::get('/traveler', [AnalyticsController::class, 'travelerDashboard'])->middleware('role:user');
    });

    // Admin routes - Nouveau système RBAC
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        // Dashboard & Analytics
        Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats'])
            ->middleware('permission:admin.dashboard.read');
        Route::get('/dashboard/daily-activity', [AdminDashboardController::class, 'dailyActivity'])
            ->middleware('permission:admin.dashboard.read');
        Route::get('/dashboard/host-performance', [AdminDashboardController::class, 'hostPerformance'])
            ->middleware('permission:admin.dashboard.read');
        Route::get('/dashboard/accommodation-status', [AdminDashboardController::class, 'accommodationStatusDistribution'])
            ->middleware('permission:admin.dashboard.read');

        // Gestion des utilisateurs
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->middleware('permission:users.read');
            Route::get('/roles', [AdminUserController::class, 'roles'])->middleware('permission:users.read');
            Route::get('/hosts', [AdminUserController::class, 'hosts'])->middleware('permission:users.read');
            Route::post('/', [AdminUserController::class, 'store'])->middleware('permission:users.create');
            Route::get('/{id}', [AdminUserController::class, 'show'])->middleware('permission:users.read');
            Route::put('/{id}', [AdminUserController::class, 'update'])->middleware('permission:users.update');
            Route::post('/{id}/block', [AdminUserController::class, 'block'])->middleware('permission:users.block');
            Route::post('/{id}/unblock', [AdminUserController::class, 'unblock'])->middleware('permission:users.unblock');
            Route::post('/{id}/roles', [AdminUserController::class, 'assignRoles'])->middleware('permission:users.manage_roles');
            Route::get('/{id}/activity-logs', [AdminUserController::class, 'activityLogs'])->middleware('permission:users.read');
        });

        // Gestion des hôtes
        Route::prefix('hosts')->group(function () {
            Route::get('/', [AdminHostController::class, 'index'])->middleware('permission:hosts.read');
            Route::get('/{id}', [AdminHostController::class, 'show'])->middleware('permission:hosts.read');
            Route::post('/{id}/validate', [AdminHostController::class, 'validate'])->middleware('permission:hosts.validate');
            Route::post('/{id}/reject', [AdminHostController::class, 'reject'])->middleware('permission:hosts.reject');
            Route::post('/{id}/suspend', [AdminHostController::class, 'suspend'])->middleware('permission:hosts.suspend');
            Route::post('/{id}/remove-status', [AdminHostController::class, 'removeHostStatus'])->middleware('permission:hosts.remove_status');
            Route::post('/{id}/notes', [AdminHostController::class, 'addNote'])->middleware('permission:hosts.read');
            Route::get('/{id}/accommodations', [AdminHostController::class, 'accommodations'])->middleware('permission:hosts.read');
        });

        // Gestion des établissements
        Route::prefix('accommodations')->group(function () {
            Route::get('/', [AdminAccommodationController::class, 'index'])->middleware('permission:accommodations.read');
            Route::post('/', [AdminAccommodationController::class, 'store'])->middleware('permission:accommodations.create');
            Route::get('/{id}', [AdminAccommodationController::class, 'show'])->middleware('permission:accommodations.read');
            Route::post('/{id}/approve', [AdminAccommodationController::class, 'approve'])->middleware('permission:accommodations.approve');
            Route::post('/{id}/reject', [AdminAccommodationController::class, 'reject'])->middleware('permission:accommodations.reject');
            Route::post('/{id}/remove', [AdminAccommodationController::class, 'remove'])->middleware('permission:accommodations.remove');
            Route::post('/{id}/disable', [AdminAccommodationController::class, 'disable'])->middleware('permission:accommodations.disable');
            Route::post('/{id}/enable', [AdminAccommodationController::class, 'enable'])->middleware('permission:accommodations.update');
            Route::post('/{id}/media', [AccommodationController::class, 'uploadMedia'])->middleware('permission:accommodations.update');
            Route::post('/{id}/notes', [AdminAccommodationController::class, 'addNote'])->middleware('permission:accommodations.read');
            Route::get('/{id}/audit-logs', [AdminAccommodationController::class, 'auditLogs'])->middleware('permission:accommodations.read');
        });

        // Inspections (Contrôleurs)
        Route::prefix('inspections')->group(function () {
            Route::get('/', [InspectionController::class, 'index'])->middleware('permission:inspections.read');
            Route::post('/', [InspectionController::class, 'store'])->middleware('permission:inspections.create');
            Route::get('/accommodations', [InspectionController::class, 'accommodations'])->middleware('auth:sanctum');
            Route::get('/{id}', [InspectionController::class, 'show'])->middleware('permission:inspections.read');
            Route::get('/{id}/checklist', [InspectionController::class, 'generateChecklist'])->middleware('permission:inspections.read');
            Route::post('/{id}/start', [InspectionController::class, 'start'])->middleware('permission:inspections.update');
            Route::post('/{id}/pause', [InspectionController::class, 'pause'])->middleware('permission:inspections.update');
            Route::post('/{id}/responses', [InspectionController::class, 'addResponse'])->middleware('permission:inspections.update');
            Route::post('/{id}/complete', [InspectionController::class, 'complete'])->middleware('permission:inspections.complete');
            Route::post('/{id}/approve', [InspectionController::class, 'approve'])->middleware('permission:inspections.approve');
            Route::post('/{id}/reject', [InspectionController::class, 'reject'])->middleware('permission:inspections.reject');
            Route::get('/checklists/list', [InspectionController::class, 'checklists'])->middleware('permission:inspections.read');
        });

        // Routes legacy (rétrocompatibilité) - Désactivées car remplacées par les nouvelles routes RBAC
        // Route::middleware('role:admin')->group(function () {
        //     Route::get('/dashboard', [AdminController::class, 'dashboard']);
        //     Route::get('/accommodations', [AdminController::class, 'accommodations']);
        //     Route::put('/accommodations/{id}/approve', [AdminController::class, 'approveAccommodation'])->where('id', '[0-9]+');
        //     Route::put('/accommodations/{id}/reject', [AdminController::class, 'rejectAccommodation'])->where('id', '[0-9]+');
        //     Route::get('/users', [AdminController::class, 'users']);
        //     Route::get('/appointments', [\App\Http\Controllers\AppointmentController::class, 'index']);
        // });
    });
});

