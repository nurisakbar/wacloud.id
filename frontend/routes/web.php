<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Auth::routes();

// Google OAuth routes
Route::get('/auth/google', [App\Http\Controllers\Auth\GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [App\Http\Controllers\Auth\GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/google/phone', [App\Http\Controllers\Auth\GoogleAuthController::class, 'showPhoneInput'])->name('auth.google.phone')->middleware('auth');
Route::post('/auth/google/phone', [App\Http\Controllers\Auth\GoogleAuthController::class, 'savePhone'])->name('auth.google.phone.save')->middleware('auth');

// Redirect root to login, or home if already authenticated
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

// Keep landing page accessible via /landing if needed
Route::get('/landing', [App\Http\Controllers\LandingController::class, 'index'])->name('landing');

// Public Documentation Page
Route::get('/docs', [App\Http\Controllers\DocumentationController::class, 'index'])->name('docs.index');

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    
    // WhatsApp Sessions - with rate limiting for creation
    Route::resource('sessions', App\Http\Controllers\SessionController::class);
    Route::post('/sessions', [App\Http\Controllers\SessionController::class, 'store'])
        ->middleware('throttle:5,1') // Max 5 requests per minute
        ->name('sessions.store');
    Route::get('/sessions/{session}/pair', [App\Http\Controllers\SessionController::class, 'pair'])->name('sessions.pair');
    Route::get('/sessions/{session}/check-status', [App\Http\Controllers\SessionController::class, 'checkStatus'])
        ->middleware('throttle:30,1') // Max 30 status checks per minute
        ->name('sessions.check-status');
    Route::post('/sessions/{session}/update-webhook', [App\Http\Controllers\SessionController::class, 'updateWebhook'])
        ->name('sessions.updateWebhook');
    Route::post('/sessions/{session}/refresh-qr', [App\Http\Controllers\SessionController::class, 'refreshQrCode'])
        ->middleware('throttle:10,1') // Max 10 QR refreshes per minute
        ->name('sessions.refresh-qr');
    Route::post('/sessions/{session}/stop', [App\Http\Controllers\SessionController::class, 'stop'])->name('sessions.stop');
    
    // Debug routes
    Route::get('/debug/session', [App\Http\Controllers\SessionDebugController::class, 'debug'])->name('debug.session');
    Route::post('/debug/session/restart', [App\Http\Controllers\SessionDebugController::class, 'restart'])->name('debug.session.restart');
    
    // Messages - DataTables route must be before resource route
    Route::get('messages/data', [App\Http\Controllers\MessageController::class, 'index'])->name('messages.data');
    Route::post('messages/bulk', [App\Http\Controllers\MessageController::class, 'storeBulk'])->name('messages.storeBulk');
    Route::post('messages/toggle-auto-sync', [App\Http\Controllers\MessageController::class, 'toggleAutoSync'])->name('messages.toggleAutoSync');
    Route::post('messages/sync-incoming', [App\Http\Controllers\MessageController::class, 'syncIncoming'])->name('messages.syncIncoming');
    Route::post('messages/update-pending-status', [App\Http\Controllers\MessageController::class, 'updatePendingStatus'])->name('messages.updatePendingStatus');
    Route::resource('messages', App\Http\Controllers\MessageController::class)->except(['edit', 'update']);
    
    // Webhooks
    Route::resource('webhooks', App\Http\Controllers\WebhookController::class);
    Route::post('/webhooks/{webhook}/test', [App\Http\Controllers\WebhookController::class, 'test'])->name('webhooks.test');
    
    // Templates
    Route::resource('templates', App\Http\Controllers\TemplateController::class);
    
    // API Keys - Only 1 per user, can be regenerated
    Route::get('/api-keys', [App\Http\Controllers\ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys/regenerate', [App\Http\Controllers\ApiKeyController::class, 'regenerate'])->name('api-keys.regenerate');
    
    // Contacts & Groups
    Route::get('/contacts', [App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{session}', [App\Http\Controllers\ContactController::class, 'show'])->name('contacts.show');
    Route::get('/groups', [App\Http\Controllers\ContactController::class, 'groups'])->name('groups.index');
    Route::get('/groups/{session}', [App\Http\Controllers\ContactController::class, 'groupShow'])->name('groups.show');
    
    // Billing
    Route::get('/billing', [App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/subscribe', [App\Http\Controllers\BillingController::class, 'subscribe'])->name('billing.subscribe');
    
    // Analytics
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
    
    // API Documentation
    Route::get('/api-docs', [App\Http\Controllers\ApiDocumentationController::class, 'index'])->name('api-docs.index');
    Route::get('/api-docs/{module}', [App\Http\Controllers\ApiDocumentationController::class, 'detail'])->name('api-docs.detail');
    
    // Profile
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
    // Quota Management
    Route::get('/quota', [App\Http\Controllers\QuotaController::class, 'index'])->name('quota.index');
    Route::get('/quota/create', [App\Http\Controllers\QuotaController::class, 'create'])->name('quota.create');
    Route::post('/quota/purchase', [App\Http\Controllers\QuotaController::class, 'purchase'])->name('quota.purchase');
    Route::get('/quota/purchase/{purchase}/confirm-payment', [App\Http\Controllers\QuotaController::class, 'showConfirmPayment'])->name('quota.confirm-payment');
    Route::post('/quota/purchase/{purchase}/confirm-payment', [App\Http\Controllers\QuotaController::class, 'confirmPayment'])->name('quota.confirm-payment.store');
    Route::post('/quota/purchase/{purchase}/complete', [App\Http\Controllers\QuotaController::class, 'completePurchase'])->name('quota.complete');
    Route::get('/quota/payment/{purchase}/success', [App\Http\Controllers\QuotaController::class, 'paymentSuccess'])->name('quota.payment.success');
    Route::get('/quota/payment/{purchase}/failure', [App\Http\Controllers\QuotaController::class, 'paymentFailure'])->name('quota.payment.failure');
    
    // Payment Management
    Route::post('/payment/create-invoice', [App\Http\Controllers\PaymentController::class, 'createInvoice'])->name('payment.create-invoice');
    Route::get('/payment/{purchase}/check-status', [App\Http\Controllers\PaymentController::class, 'checkStatus'])->name('payment.check-status');
    Route::get('/payment/{purchase}/success', [App\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/{purchase}/failure', [App\Http\Controllers\PaymentController::class, 'failure'])->name('payment.failure');
    Route::get('/payment/{purchase}/status', [App\Http\Controllers\PaymentController::class, 'status'])->name('payment.status');
    
    // Referral
    Route::get('/referral', [App\Http\Controllers\ReferralController::class, 'index'])->name('referral.index');
    
    // Admin routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/pricing', [App\Http\Controllers\Admin\PricingController::class, 'index'])->name('pricing.index');
        Route::put('/pricing', [App\Http\Controllers\Admin\PricingController::class, 'update'])->name('pricing.update');
        Route::get('/referral-settings', [App\Http\Controllers\Admin\ReferralSettingController::class, 'index'])->name('referral-settings.index');
        Route::put('/referral-settings', [App\Http\Controllers\Admin\ReferralSettingController::class, 'update'])->name('referral-settings.update');
        Route::get('/quota-purchases', [App\Http\Controllers\Admin\QuotaPurchaseController::class, 'index'])->name('quota-purchases.index');
        Route::get('/quota-purchases/{quotaPurchase}', [App\Http\Controllers\Admin\QuotaPurchaseController::class, 'show'])->name('quota-purchases.show');
        Route::post('/quota-purchases/{quotaPurchase}/approve', [App\Http\Controllers\Admin\QuotaPurchaseController::class, 'approve'])->name('quota-purchases.approve');
        Route::post('/quota-purchases/{quotaPurchase}/reject', [App\Http\Controllers\Admin\QuotaPurchaseController::class, 'reject'])->name('quota-purchases.reject');
        
        // Users Management
        Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
        
        // Log Viewer - only accessible by admin
        Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->name('logs.index');
    });
});

// Webhook receiver (public endpoint)
Route::post('/webhook/receive/{session}', [App\Http\Controllers\WebhookController::class, 'receive'])->name('webhook.receive');

// Xendit webhook (public endpoint)
Route::post('/webhook/xendit', [App\Http\Controllers\QuotaController::class, 'webhook'])->name('webhook.xendit');

