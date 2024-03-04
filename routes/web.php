<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\SettingController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /**
     * ==============================
     *           Members
     * ==============================
     */

    Route::prefix('member')->group(function () {
        Route::get('/member_listing', [MemberController::class, 'index'])->name('member.member_listing');
        Route::get('/getMemberDetails', [MemberController::class, 'getMemberDetails'])->name('member.getMemberDetails');
        Route::get('/member_details/{id}', [MemberController::class, 'viewMemberDetails'])->name('member.viewMemberDetails');
        Route::post('/editMember', [MemberController::class, 'editMember'])->name('member.edit_member');
        Route::post('/payment_account', [MemberController::class, 'paymentAccount'])->name('member.payment_account');
        Route::patch('/advanceEditMember', [MemberController::class, 'advanceEditMember'])->name('member.advanceEdit_member');
        Route::get('/getAllUsers', [MemberController::class, 'getAllUsers'])->name('member.getAllUsers');
        Route::get('/refreshTradingAccountsData', [MemberController::class, 'refreshTradingAccountsData'])->name('member.refreshTradingAccountsData');
        Route::get('/member_affiliates/{id}', [MemberController::class, 'affiliate_tree'])->name('member.affiliate_tree');
        Route::get('/getTreeData/{id}', [MemberController::class, 'getTreeData'])->name('member.getTreeData');
        Route::post('/wallet_adjustment', [MemberController::class, 'wallet_adjustment'])->name('member.wallet_adjustment');
        Route::post('/verifyMember', [MemberController::class, 'verifyMember'])->name('member.verify_member');

        Route::get('/impersonate/{user}', [MemberController::class, 'impersonate'])->name('member.impersonate');
    });

    /**
     * ==============================
     *          Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('/pendingTransaction', [TransactionController::class, 'pendingTransaction'])->name('transaction.pending_transaction');
        Route::get('/transactionHistory', [TransactionController::class, 'transactionHistory'])->name('transaction.transaction_history');
        Route::get('/getPendingTransaction/{type}', [TransactionController::class, 'getPendingTransaction'])->name('transaction.getPendingTransaction');
        Route::post('/approveTransaction', [TransactionController::class, 'approveTransaction'])->name('transaction.approveTransaction');
        Route::post('/rejectTransaction', [TransactionController::class, 'rejectTransaction'])->name('transaction.rejectTransaction');
        Route::get('/getTransactionHistory/{type}', [TransactionController::class, 'getTransactionHistory'])->name('transaction.getTransactionHistory');
        Route::get('/getBalanceHistory/{type}', [TransactionController::class, 'getBalanceHistory'])->name('transaction.getBalanceHistory');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /**
     * ==============================
     *         Announcement
     * ==============================
     */
    Route::prefix('announcement')->group(function () {
        Route::get('/listing', [AnnouncementController::class, 'index'])->name('announcement.announcement_listing');
        Route::get('/getAnnouncement', [AnnouncementController::class, 'getAnnouncement'])->name('getAnnouncement');
        Route::post('/addAnnouncement', [AnnouncementController::class, 'addAnnouncement'])->name('addAnnouncement');
    });

    /**
     * ==============================
     *         Master
     * ==============================
     */
    Route::prefix('master')->group(function () {
        // Master Request
        Route::get('/master_request', [MasterController::class, 'index'])->name('master.master_request');
        Route::get('/getMaster/{type}', [MasterController::class, 'getMaster'])->name('master.getMaster');
        Route::get('/getMasterHistory', [MasterController::class, 'getMasterHistroy'])->name('master.getMasterHistroy');
        Route::post('/approveRequest', [MasterController::class, 'approveRequest'])->name('master.approveRequest');
        Route::post('/rejectRequest', [MasterController::class, 'rejectRequest'])->name('master.rejectRequest');
        // Master Listing
        Route::get('/getMasterListing', [MasterController::class, 'getMasterListing'])->name('master.getMasterListing');
        Route::get('/getAllMaster', [MasterController::class, 'getAllMaster'])->name('master.getAllMaster');
        Route::get('/master_configuration/{id}', [MasterController::class, 'viewMasterConfiguration'])->name('master.viewMasterConfiguration');
        Route::post('/updateMasterConfiguration', [MasterController::class, 'updateMasterConfiguration'])->name('master.updateMasterConfiguration');
    });

    /**
     * ==============================
     *         Setting
     * ==============================
     */
    Route::prefix('setting')->group(function () {
        //payment setting
        Route::get('/payment_setting', [SettingController::class, 'paymentSetting'])->name('setting.payment_setting');
        Route::post('/updatePaymentSetting', [SettingController::class, 'updatePaymentSetting'])->name('setting.updatePaymentSetting');
        Route::get('/getPaymentHistory', [SettingController::class, 'getPaymentHistory'])->name('setting.getPaymentHistory');
    
        //master setting
        Route::get('/master_setting', [SettingController::class, 'masterSetting'])->name('setting.master_setting');
        Route::post('/updateMasterSetting', [SettingController::class, 'updateMasterSetting'])->name('setting.updateMasterSetting');
    });
});

Route::get('/components/buttons', function () {
    return Inertia::render('Components/Buttons');
})->middleware(['auth', 'verified'])->name('components.buttons');

require __DIR__ . '/auth.php';
