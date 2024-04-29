<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TradingController;
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

Route::middleware(['auth', 'role:super-admin|admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /**
     * ==============================
     *           Members
     * ==============================
     */

    Route::prefix('member')->group(function () {
        Route::post('/addMember', [MemberController::class, 'addMember'])->name('member.addMember');
        Route::get('/member_listing', [MemberController::class, 'index'])->name('member.member_listing');
        Route::get('/getMemberDetails', [MemberController::class, 'getMemberDetails'])->name('member.getMemberDetails');
        Route::get('/member_details/{id}', [MemberController::class, 'viewMemberDetails'])->name('member.viewMemberDetails');
        Route::post('/editMember', [MemberController::class, 'editMember'])->name('member.edit_member');
        Route::post('/payment_account', [MemberController::class, 'paymentAccount'])->name('member.payment_account');
        Route::patch('/advanceEditMember', [MemberController::class, 'advanceEditMember'])->name('member.advanceEdit_member');
        Route::get('/getAllUsers', [MemberController::class, 'getAllUsers'])->name('member.getAllUsers');
        Route::get('/getAllLeaders', [MemberController::class, 'getAllLeaders'])->name('member.getAllLeaders');
        Route::get('/refreshTradingAccountsData', [MemberController::class, 'refreshTradingAccountsData'])->name('member.refreshTradingAccountsData');
        Route::get('/member_affiliates/{id}', [MemberController::class, 'affiliate_tree'])->name('member.affiliate_tree');
        Route::get('/getTreeData/{id}', [MemberController::class, 'getTreeData'])->name('member.getTreeData');
        Route::post('/wallet_adjustment', [MemberController::class, 'wallet_adjustment'])->name('member.wallet_adjustment');
        Route::post('/validateKyc', [MemberController::class, 'validateKyc'])->name('member.validateKyc');
        Route::post('/verifyMember', [MemberController::class, 'verifyMember'])->name('member.verify_member');
        Route::get('/impersonate/{user}', [MemberController::class, 'impersonate'])->name('member.impersonate');
        Route::get('/affiliate_listing', [MemberController::class, 'affiliate_listing'])->name('member.affiliate_listing');
        Route::get('/getAffiliateSummaries', [MemberController::class, 'getAffiliateSummaries'])->name('member.getAffiliateSummaries');

        // live trading
        Route::get('/live_trading', [TradingController::class, 'liveTrading'])->name('member.live_trading');
        Route::get('/getTradingAccount', [TradingController::class, 'getTradingAccount'])->name('member.getTradingAccount');
        Route::post('/edit_leverage', [TradingController::class, 'edit_leverage'])->name('member.edit_leverage');
        Route::post('/change_password', [TradingController::class, 'change_password'])->name('member.change_password');
        Route::post('/balanceAdjustment', [TradingController::class, 'balanceAdjustment'])->name('member.balanceAdjustment');

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
        Route::get('/getTransactionHistory', [TransactionController::class, 'getTransactionHistory'])->name('transaction.getTransactionHistory');
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
        Route::post('/edit_details', [AnnouncementController::class, 'editAnnoucement'])->name('announcement.edit_details');
        Route::post('/updateStatus', [AnnouncementController::class, 'updateStatus'])->name('announcement.updateStatus');
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
     *         Subscription
     * ==============================
     */
    Route::prefix('subscription')->group(function () {
        Route::get('/subscribers', [SubscriptionController::class, 'subscribers'])->name('subscription.subscribers');
        Route::get('/getPendingSubscriptions', [SubscriptionController::class, 'getPendingSubscriptions'])->name('subscription.getPendingSubscriptions');
        Route::get('/getActiveSubscriber', [SubscriptionController::class, 'getActiveSubscriber'])->name('subscription.getActiveSubscriber');
        Route::get('/subscriptionHistory', [SubscriptionController::class, 'subscriptionHistory'])->name('subscription.subscriptionHistory');
        Route::get('/getHistorySubscriber', [SubscriptionController::class, 'getHistorySubscriber'])->name('subscription.getHistorySubscriber');
        Route::get('/getPendingSubscriptionRenewal', [SubscriptionController::class, 'getPendingSubscriptionRenewal'])->name('subscription.getPendingSubscriptionRenewal');
        // subscriber request
        Route::post('/approveSubscribe', [SubscriptionController::class, 'approveSubscribe'])->name('subscription.approveSubscribe');
        Route::post('/rejectSubscribe', [SubscriptionController::class, 'rejectSubscribe'])->name('subscription.rejectSubscribe');
        Route::post('/terminateSubscribe', [SubscriptionController::class, 'terminateSubscribe'])->name('subscription.terminateSubscribe');
        // subscription renewal
        Route::post('/approveRenewalSubscription', [SubscriptionController::class, 'approveRenewalSubscription'])->name('subscription.approveRenewalSubscription');
        Route::post('/rejectRenewalSubscription', [SubscriptionController::class, 'rejectRenewalSubscription'])->name('subscription.rejectRenewalSubscription');

        Route::get('/subscribersListing', [SubscriptionController::class, 'subscribersListing'])->name('subscription.subscribersListing');
    });

    /**
     * ==============================
     *         Report
     * ==============================
     */
    Route::prefix('report')->group(function () {
        Route::get('/trading_rebate', [ReportController::class, 'trading_rebate'])->name('report.trading_rebate');
        Route::get('/getTradingRebate', [ReportController::class, 'getTradingRebate'])->name('report.getTradingRebate');
    });

    /**
     * ==============================
     *         Setting
     * ==============================
     */
    Route::prefix('setting')->middleware('role:super-admin')->group(function () {
        //payment setting
        Route::get('/payment_setting', [SettingController::class, 'paymentSetting'])->name('setting.payment_setting');
        Route::get('/getCryptoNetworks', [SettingController::class, 'getCryptoNetworks'])->name('setting.getCryptoNetworks');
        Route::post('/addPaymentSetting', [SettingController::class, 'addPaymentSetting'])->name('setting.addPaymentSetting');
        Route::post('/updatePaymentSetting', [SettingController::class, 'updatePaymentSetting'])->name('setting.updatePaymentSetting');
        Route::delete('/deletePayment', [SettingController::class, 'deletePayment'])->name('setting.deletePayment');
        Route::get('/getPaymentHistory', [SettingController::class, 'getPaymentHistory'])->name('setting.getPaymentHistory');
        Route::get('/getPaymentActivity', [SettingController::class, 'getPaymentActivity'])->name('setting.getPaymentActivity');

        //master setting
        Route::get('/master_setting', [SettingController::class, 'masterSetting'])->name('setting.master_setting');
        Route::post('/updateMasterSetting', [SettingController::class, 'updateMasterSetting'])->name('setting.updateMasterSetting');

        // tnc setting
        Route::get('/tnc_setting', [SettingController::class, 'tncSetting'])->name('setting.tnc_setting');
        Route::get('/getTncSetting', [SettingController::class, 'getTncSetting'])->name('setting.getTncSetting');
        Route::post('/addTnCSetting', [SettingController::class, 'addTnCSetting'])->name('setting.addTnCSetting');
        Route::put('/editTnCSetting/{id}', [SettingController::class, 'editTnCSetting'])->name('setting.editTnCSetting');

        // tnc setting
        Route::get('/leverage_setting', [SettingController::class, 'leverageSetting'])->name('setting.leverage_setting');
        Route::get('/getLeverageSetting', [SettingController::class, 'getLeverageSetting'])->name('setting.getLeverageSetting');
        Route::post('/addLeverageSetting', [SettingController::class, 'addLeverageSetting'])->name('setting.addLeverageSetting');
        Route::put('/editLeverageSetting/{id}', [SettingController::class, 'editLeverageSetting'])->name('setting.editLeverageSetting');

    });

    /**
     * ==============================
     *          Admin
     * ==============================
     */
    Route::prefix('admin')->group(function () {
        Route::get('/admin_listing', [AdminController::class, 'admin'])->name('admin.admin_listing');
        Route::get('/getAdminUsers', [AdminController::class, 'getAdminUsers'])->name('admin.getAdminUsers');

        Route::post('/assign_user', [AdminController::class, 'assign_user'])->name('admin.assign_user');
        Route::post('/remove_admin', [AdminController::class, 'remove_admin'])->name('admin.remove_admin');
    });
});

Route::get('/components/buttons', function () {
    return Inertia::render('Components/Buttons');
})->middleware(['auth', 'verified'])->name('components.buttons');

require __DIR__ . '/auth.php';
