<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventCategoryController as AdminEventCategoryController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\PricingController as AdminPricingController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\TopUpController as AdminTopUpController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WalletAdjustmentController as AdminWalletAdjustmentController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\BudgetItemController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoRequestController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventCommitteeController;
use App\Http\Controllers\EventDesignController;
use App\Http\Controllers\EventPackageController;
use App\Http\Controllers\EventTaskController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OutboundMessageController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PledgeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateLibraryController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/features', [PageController::class, 'features'])->name('features');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
Route::get('/upcoming', [EventController::class, 'upcoming'])->name('events.upcoming');
Route::post('/demos', [DemoRequestController::class, 'store'])->name('demos.store');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/register/verify', [PhoneVerificationController::class, 'show'])->name('register.verify');
    Route::post('/register/verify', [PhoneVerificationController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/register/verify/resend', [PhoneVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('register.verify.resend');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:6,1')->name('password.sms');
    Route::get('/reset-password', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::get('/i/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/i/{token}', [InvitationController::class, 'rsvp'])->name('invitations.rsvp');

Route::middleware('auth')->group(function (): void {
    Route::middleware('super-admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('search', AdminSearchController::class)->name('search');

        Route::get('users/create', static fn () => abort(404));
        Route::resource('users', AdminUserController::class)->except(['create']);
        Route::post('users/{user}/wallet', [AdminWalletAdjustmentController::class, 'store'])->name('users.wallet.store');

        Route::get('events', [AdminEventController::class, 'index'])->name('events.index');
        Route::get('events/{event}', [AdminEventController::class, 'show'])->name('events.show');
        Route::get('messages', [AdminMessageController::class, 'index'])->name('messages.index');

        Route::get('top-ups', [AdminTopUpController::class, 'index'])->name('top-ups.index');
        Route::post('top-ups/{topUp}/approve', [AdminTopUpController::class, 'approve'])->name('top-ups.approve');
        Route::post('top-ups/{topUp}/reject', [AdminTopUpController::class, 'reject'])->name('top-ups.reject');
        Route::get('transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');

        Route::resource('packages', AdminPackageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('pricing', [AdminPricingController::class, 'edit'])->name('pricing.edit');
        Route::put('pricing', [AdminPricingController::class, 'update'])->name('pricing.update');
        Route::resource('templates', AdminTemplateController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('event-categories', AdminEventCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::put('subscriptions', [AdminSubscriptionController::class, 'update'])->name('subscriptions.update');

        Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::post('settings/test-sms', [AdminSettingController::class, 'testSms'])->middleware('throttle:6,1')->name('settings.test-sms');
        Route::delete('settings/queue/failed', [AdminSettingController::class, 'flushFailedJobs'])->name('settings.queue-flush');
    });

    Route::get('/demos', [DemoRequestController::class, 'index'])
        ->middleware('profile-complete')
        ->name('demos.index');

    Route::middleware(['host', 'profile-complete'])->group(function (): void {
        Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/subscription', [SubscriptionController::class, 'store'])->name('subscription.store');
        Route::post('/subscription/payment', [SubscriptionController::class, 'payment'])->name('subscription.payment');
    });

    Route::middleware(['host', 'profile-complete', 'subscribed'])->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/templates', [TemplateLibraryController::class, 'index'])->name('template-library.index');
        Route::get('/check-in/{token}', [CheckInController::class, 'scan'])->name('check-ins.scan');

        Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
        Route::post('/wallet/top-ups', [WalletController::class, 'store'])->name('wallet.top-ups.store');
        Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');

        Route::resource('events', EventController::class);
        Route::post('events/{event}/package', [EventPackageController::class, 'store'])->name('events.package.store');
        Route::get('events/{event}/design', [EventDesignController::class, 'edit'])->name('events.design.edit');
        Route::put('events/{event}/design', [EventDesignController::class, 'update'])->name('events.design.update');

        Route::post('events/{event}/guests/import', [GuestController::class, 'import'])->name('events.guests.import');
        Route::get('events/{event}/guests/{guest}/edit', [GuestController::class, 'edit'])->name('events.guests.edit');
        Route::post('events/{event}/guests', [GuestController::class, 'store'])->name('events.guests.store');
        Route::put('events/{event}/guests/{guest}', [GuestController::class, 'update'])->name('events.guests.update');
        Route::delete('events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('events.guests.destroy');

        Route::post('events/{event}/committees', [EventCommitteeController::class, 'store'])->name('events.committees.store');
        Route::put('events/{event}/committees/{committee}', [EventCommitteeController::class, 'update'])->name('events.committees.update');
        Route::delete('events/{event}/committees/{committee}', [EventCommitteeController::class, 'destroy'])->name('events.committees.destroy');

        Route::post('events/{event}/pledges', [PledgeController::class, 'store'])->name('events.pledges.store');
        Route::patch('events/{event}/pledges/{pledge}', [PledgeController::class, 'update'])->name('events.pledges.update');
        Route::delete('events/{event}/pledges/{pledge}', [PledgeController::class, 'destroy'])->name('events.pledges.destroy');

        Route::post('events/{event}/budget-items', [BudgetItemController::class, 'store'])->name('events.budget-items.store');
        Route::delete('events/{event}/budget-items/{budgetItem}', [BudgetItemController::class, 'destroy'])->name('events.budget-items.destroy');

        Route::post('events/{event}/tasks', [EventTaskController::class, 'store'])->name('events.tasks.store');
        Route::patch('events/{event}/tasks/{task}', [EventTaskController::class, 'update'])->name('events.tasks.update');
        Route::delete('events/{event}/tasks/{task}', [EventTaskController::class, 'destroy'])->name('events.tasks.destroy');

        Route::post('events/{event}/vendors', [VendorController::class, 'store'])->name('events.vendors.store');
        Route::patch('events/{event}/vendors/{vendor}', [VendorController::class, 'update'])->name('events.vendors.update');
        Route::delete('events/{event}/vendors/{vendor}', [VendorController::class, 'destroy'])->name('events.vendors.destroy');

        Route::post('events/{event}/invitations', [OutboundMessageController::class, 'invitations'])->name('events.invitations.send');
        Route::post('events/{event}/reminders', [OutboundMessageController::class, 'reminders'])->name('events.reminders.send');
        Route::post('events/{event}/thanks', [OutboundMessageController::class, 'thanks'])->name('events.thanks.send');
        Route::post('events/{event}/check-ins', [CheckInController::class, 'store'])->name('events.check-ins.store');
    });
});
