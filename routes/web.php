<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Guest routes
Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
});

Route::post('/webhook/twilio', [\App\Http\Controllers\TwilioWebhookController::class, 'handle'])->name('webhook.twilio');

// Public QR Code Media endpoint for Twilio Content API & WhatsApp templates
Route::get('/qr/{code}', function ($code) {
    $cleanCode = strtoupper(preg_replace('/\.(png|jpg|jpeg)$/i', '', trim($code)));

    $member = \App\Models\ConferenceMember::where('unique_code', $cleanCode)
        ->orWhere('id', $cleanCode)
        ->first();

    if (!$member) {
        return response('QR not found', 404)->header('Content-Type', 'text/plain');
    }

    $uniqueCode = $member->unique_code ?: $cleanCode;
    $ticketUrl = url('/ticket/view/' . $uniqueCode);

    $imageBytes = \App\Services\QrCodeService::getOrGenerate($uniqueCode, $ticketUrl);

    if (!$imageBytes) {
        return response('Unable to generate QR image', 500)->header('Content-Type', 'text/plain');
    }

    return response($imageBytes, 200, [
        'Content-Type' => 'image/png',
        'Content-Disposition' => 'inline; filename="' . $uniqueCode . '.png"',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('qr.show');

// Emergency Fix and Migration runner for shared hosting
Route::get('/fixcache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        $clearOutput = \Illuminate\Support\Facades\Artisan::output();

        // Also clean up compiled views directory if exists
        $viewsPath = storage_path('framework/views');
        if (is_dir($viewsPath)) {
            foreach (glob($viewsPath . '/*.php') as $file) {
                @unlink($file);
            }
        }

        return response("<pre><h3>Migrations:</h3>{$migrateOutput}<h3>Cache Cleared:</h3>{$clearOutput}<h3>Status:</h3>All compiled Blade views, routes, and migrations updated successfully!</pre>");
    } catch (\Throwable $e) {
        return response("<pre style='color: red;'>Error: " . $e->getMessage() . "</pre>", 500);
    }
});

// Logout
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Volt::route('/', 'dashboard')->name('dashboard');

    // Profile & Password Update
    Volt::route('/profile', 'profile.edit')->name('profile.edit');

    // --- ADMIN ONLY ROUTES ---
    Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {

        // Members
        Route::prefix('members')->name('members.')->group(function () {
            Volt::route('/', 'members.index')->name('index');
            Volt::route('/create', 'members.create')->name('create');
            Volt::route('/{member}/edit', 'members.edit')->name('edit');
        });
        Volt::route('/scanner', 'scanner')->name('scanner');

        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Volt::route('/', 'users.index')->name('index');
            Volt::route('/create', 'users.create')->name('create');
            Volt::route('/{user}/edit', 'users.edit')->name('edit');
        });

        // Roles
        Volt::route('/roles', 'roles.index')->name('roles.index');

        // Packages
        Route::prefix('packages')->name('packages.')->group(function () {
            Volt::route('/', 'packages.index')->name('index');
            Volt::route('/create', 'packages.create')->name('create');
            Volt::route('/{package}/edit', 'packages.edit')->name('edit');
        });

        // Event Types
        Volt::route('/event-types', 'event-types.index')->name('event-types.index');

        // Registrations
        Route::prefix('registrations')->name('registrations.')->group(function () {
            Volt::route('/', 'registrations.index')->name('index');
            Volt::route('/create', 'registrations.create')->name('create');
            Volt::route('/{registration}/edit', 'registrations.edit')->name('edit');
        });

        // Payments
        Volt::route('/payments', 'payments.index')->name('payments.index');

        // Membership Payments
        Route::prefix('membership-payments')->name('membership-payments.')->group(function () {
            Volt::route('/', 'membership-payments.index')->name('index');
            Volt::route('/create', 'membership-payments.create')->name('create');
            Volt::route('/{membershipPayment}/edit', 'membership-payments.edit')->name('edit');
        });

        // Communities
        Route::prefix('communities')->name('communities.')->group(function () {
            Volt::route('/', 'communities.index')->name('index');
            Volt::route('/create', 'communities.create')->name('create');
            Volt::route('/{community}/edit', 'communities.edit')->name('edit');
        });

        // Community Categories
        Volt::route('/community-categories', 'community-categories.index')->name('community-categories.index');

        // News
        Route::prefix('news')->name('news.')->group(function () {
            Volt::route('/', 'news.index')->name('index');
            Volt::route('/create', 'news.create')->name('create');
            Volt::route('/{news}/edit', 'news.edit')->name('edit');
        });

        // News Categories
        Volt::route('/news-categories', 'news-categories.index')->name('news-categories.index');

        // Resources
        Route::prefix('resources')->name('resources.')->group(function () {
            Volt::route('/', 'resources.index')->name('index');
            Volt::route('/create', 'resources.create')->name('create');
            Volt::route('/{resource}/edit', 'resources.edit')->name('edit');
        });

        // WhatsApp Templates
        Route::prefix('whatsapp-templates')->name('whatsapp-templates.')->group(function () {
            Volt::route('/', 'whatsapp-templates.index')->name('index');
            Volt::route('/create', 'whatsapp-templates.create')->name('create');
            Volt::route('/{whatsappTemplate}/edit', 'whatsapp-templates.edit')->name('edit');
        });

        // Email Templates
        Route::prefix('email-templates')->name('email-templates.')->group(function () {
            Volt::route('/', 'email-templates.index')->name('index');
            Volt::route('/create', 'email-templates.create')->name('create');
            Volt::route('/{emailTemplate}/edit', 'email-templates.edit')->name('edit');
        });

        // Bot Communications
        Volt::route('/bot-communications', 'bot-communications.index')->name('bot-communications.index');
        Volt::route('/whatsapp-test', 'whatsapp-test')->name('whatsapp-test');

        // AI Configuration
        Volt::route('/ai-training', 'ai-training')->name('ai.training');
        Volt::route('/ai-chat-tester', 'ai-chat-tester')->name('ai.chat-tester');

    }); // End Admin Routes

    // --- SHARED / STAFF ROUTES ---

    // Events (Staff can only view index)
    Route::prefix('events')->name('events.')->group(function () {
        Volt::route('/', 'events.index')->name('index');
        Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {
            Volt::route('/create', 'events.create')->name('create');
            Volt::route('/{event}/edit', 'events.edit')->name('edit');
        });
    });

    // Conference Members (Staff can only view index)
    Route::prefix('conference-members')->name('conference-members.')->group(function () {
        Volt::route('/', 'conference-members.index')->name('index');
        Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {
            Volt::route('/create', 'conference-members.create')->name('create');
            Volt::route('/{conferenceMember}/edit', 'conference-members.edit')->name('edit');
        });
    });

    // Staff routes
    Volt::route('/staff/dashboard', 'staff.dashboard')->name('staff.dashboard');
    Volt::route('/staff/scanner', 'scanner')->name('staff.scanner');
    Volt::route('/staff/conference-management', 'staff.conference-management')->name('staff.conference_management');
});


