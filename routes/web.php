<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CardCategoryController;
use App\Http\Controllers\CardSaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\MessageBroadcastController;
use App\Http\Controllers\PhoneExclusionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WorkShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/verify-otp', [AuthController::class, 'showOtpForm'])->name('show.otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend.otp');
});

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/sellers', [\App\Http\Controllers\SellerController::class, 'index'])->name('sellers.index');
        Route::post('/sellers', [\App\Http\Controllers\SellerController::class, 'store'])->name('sellers.store');
        Route::patch('/sellers/{user}/status', [\App\Http\Controllers\SellerController::class, 'updateStatus'])->name('sellers.status');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::put('/account/budget', [AccountController::class, 'updateBudget'])->name('account.budget.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');

    Route::redirect('/', '/direct-sales')->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/automation', [DashboardController::class, 'toggleAutomation'])->name('dashboard.automation');
    Route::post('/dashboard/close-shift', [WorkShiftController::class, 'close'])->name('dashboard.close-shift');
    Route::get('/shifts', [WorkShiftController::class, 'index'])->name('shifts.index');
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::patch('/ledger/customers/{customer}', [LedgerController::class, 'updateAccount'])->name('ledger.customers.update');
    Route::post('/ledger/entries', [LedgerController::class, 'storeEntry'])->name('ledger.entries.store');
    Route::get('/phone-exclusions', [PhoneExclusionController::class, 'index'])->name('exclusions.index');
    Route::post('/phone-exclusions', [PhoneExclusionController::class, 'store'])->name('exclusions.store');
    Route::delete('/phone-exclusions/{phoneExclusion}', [PhoneExclusionController::class, 'destroy'])->name('exclusions.destroy');
    Route::get('/messages', [MessageBroadcastController::class, 'index'])->name('messages.index');
    Route::post('/messages/broadcast', [MessageBroadcastController::class, 'store'])->name('messages.broadcast');
    Route::delete('/messages/{message}', [MessageBroadcastController::class, 'destroy'])->name('messages.destroy');
    Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');
    Route::view('/help', 'help.index')->name('help.index');
    Route::resource('customers', CustomerController::class)->except(['create', 'show', 'edit']);
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::post('customers/{id}/recharge', [CustomerController::class, 'recharge'])->name('customers.recharge');

    Route::resource('vouchers', VoucherController::class)->only(['index', 'store']);
    Route::post('/categories', [VoucherController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [CardCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [VoucherController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('/vouchers/import', [VoucherController::class, 'import'])->name('vouchers.import');

    Route::get('/direct-sales', [TransferController::class, 'directSaleIndex'])->name('direct_sales.index');
    Route::post('/direct-sales', [TransferController::class, 'directSaleStore'])->name('direct_sales.store');
    Route::post('/direct-sales/transfer', [TransferController::class, 'directSaleStore'])->name('direct_sales.transfer');

    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::post('/transfers/store', [TransferController::class, 'store'])->name('transfers.store');
    Route::delete('/transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');
    Route::post('/transfers/{id}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
    Route::post('/transfers/{id}/reject', [TransferController::class, 'reject'])->name('transfers.reject');
    Route::post('/transfers/{transfer}/register-customer', [TransferController::class, 'registerDepositor'])
        ->name('transfers.register-customer');
    Route::post('/transfers/{transfer}/skip-customer', [TransferController::class, 'skipCustomerLink'])
        ->name('transfers.skip-customer');
    Route::get('/transfers-log', [TransferController::class, 'logIndex'])->name('transfers.log');

    Route::get('/reports/transfers', [ReportController::class, 'transfers'])->name('reports.transfers');
    Route::delete('/sales/{cardSale}', [CardSaleController::class, 'destroy'])->name('sales.destroy');
    Route::post('/sales/{cardSale}/resend', [CardSaleController::class, 'resend'])->name('sales.resend');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/simulate', [SettingsController::class, 'simulate'])->name('settings.simulate');
});
