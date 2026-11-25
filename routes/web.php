<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Organizer;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/health', HealthController::class)->name('health');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

// Landing page for a scanned ticket QR (no personal data).
Route::get('/t/{code}', [TicketController::class, 'publicLanding'])->name('tickets.public');

// Signed server-to-server callback (CSRF-exempt, authenticated by HMAC).
Route::post('/webhooks/payments', PaymentWebhookController::class)->name('webhooks.payments');

/*
|--------------------------------------------------------------------------
| Guests
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.store');
});

/*
|--------------------------------------------------------------------------
| Signed-in users
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Booking and checkout
    Route::post('/events/{event}/orders', [BookingController::class, 'store'])->middleware('throttle:booking')->name('orders.store');
    Route::get('/orders/{order}', [BookingController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pay', [BookingController::class, 'pay'])->middleware('throttle:booking')->name('orders.pay');
    Route::post('/orders/{order}/cancel', [BookingController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{order}/pdf', [TicketController::class, 'pdf'])->name('orders.pdf');

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    // Organizer area
    Route::middleware('role:organizer')->prefix('organizer')->name('organizer.')->group(function () {
        Route::get('/', Organizer\DashboardController::class)->name('dashboard');
        Route::get('/events/create', [Organizer\EventController::class, 'create'])->name('events.create');
        Route::post('/events', [Organizer\EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [Organizer\EventController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [Organizer\EventController::class, 'update'])->name('events.update');
        Route::post('/events/{event}/publish', [Organizer\EventController::class, 'publish'])->name('events.publish');
        Route::delete('/events/{event}', [Organizer\EventController::class, 'destroy'])->name('events.destroy');
        Route::get('/events/{event}/attendees', [Organizer\AttendeeController::class, 'index'])->name('events.attendees');
        Route::get('/events/{event}/attendees.csv', [Organizer\AttendeeController::class, 'export'])->name('events.export');
        Route::get('/events/{event}/scan', [Organizer\CheckInController::class, 'scan'])->name('events.scan');
        Route::post('/events/{event}/scan', [Organizer\CheckInController::class, 'verify'])->middleware('throttle:checkin')->name('events.verify');
    });

    // Admin area
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::patch('/users/{user}/role', [Admin\DashboardController::class, 'updateRole'])->name('users.role');
        Route::post('/events/{event}/toggle', [Admin\DashboardController::class, 'toggleEvent'])->name('events.toggle');
    });
});
