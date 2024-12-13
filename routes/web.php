<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CopyTradingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PammController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SelectOptionController;
use App\Http\Controllers\SubscriberController;
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
    Route::get('/getTopGroups', [DashboardController::class, 'getTopGroups'])->name('getTopGroups');
    Route::get('/getTotalDepositByDays', [DashboardController::class, 'getTotalDepositByDays'])->name('getTotalDepositByDays');

    Route::get('getMasters', [SelectOptionController::class, 'getMasters']);
    Route::get('getLeaders', [SelectOptionController::class, 'getLeaders']);
    Route::get('getSettlementPeriods', [SelectOptionController::class, 'getSettlementPeriods']);

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
        Route::delete('/deleteAccount', [TradingController::class, 'deleteAccount'])->name('member.deleteAccount');

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
        Route::get('/master_configuration/{meta_login}', [MasterController::class, 'viewMasterConfiguration'])->name('master.viewMasterConfiguration');
        Route::post('/updateMasterConfiguration', [MasterController::class, 'updateMasterConfiguration'])->name('master.updateMasterConfiguration');
//        Route::post('/updateMasterManagementFee', [MasterController::class, 'updateMasterManagementFee'])->name('master.updateMasterManagementFee');
        Route::post('/updateMasterSubscriptionPackage', [MasterController::class, 'updateMasterSubscriptionPackage'])->name('master.updateMasterSubscriptionPackage');
        Route::post('/addVisibleToLeaders', [MasterController::class, 'addVisibleToLeaders'])->name('master.addVisibleToLeaders');



        // New Master Listing
        Route::get('/master_listing', [MasterController::class, 'master_listing'])->name('master.master_listing');
        Route::get('/getMasters', [MasterController::class, 'getMasters'])->name('master.getMasters');
        Route::get('/getMasterOverview', [MasterController::class, 'getMasterOverview'])->name('master.getMasterOverview');
        Route::get('/getMasterAnalyticChartData', [MasterController::class, 'getMasterAnalyticChartData'])->name('master.getMasterAnalyticChartData');
        Route::get('/getMasterManagementFee', [MasterController::class, 'getMasterManagementFee'])->name('master.getMasterManagementFee');

        Route::post('/addMaster', [MasterController::class, 'addMaster'])->name('master.addMaster');
        Route::post('/updateMaster', [MasterController::class, 'updateMaster'])->name('master.updateMaster');
        Route::patch('/updateMasterManagementFee', [MasterController::class, 'updateMasterManagementFee'])->name('master.updateMasterManagementFee');
        Route::post('/updateTncFile', [MasterController::class, 'updateTncFile'])->name('master.updateTncFile');
    });

    /**
     * ==============================
     *         Subscriber
     * ==============================
     */
    Route::prefix('subscriber')->group(function () {
        Route::get('/pending_subscriber', [SubscriberController::class, 'pending_subscriber'])->name('subscriber.pending_subscriber');
        Route::get('/getPendingSubscribers', [SubscriberController::class, 'getPendingSubscribers'])->name('subscriber.getPendingSubscribers');

        Route::get('/subscribers', [SubscriberController::class, 'subscribers'])->name('subscriber.subscribers'); // delete after check
        Route::get('/getPendingSubscriptions', [SubscriberController::class, 'getPendingSubscriptions'])->name('subscriber.getPendingSubscriptions'); // delete after check

        Route::get('/getActiveSubscriber', [SubscriberController::class, 'getActiveSubscriber'])->name('subscriber.getActiveSubscriber');
        Route::get('/subscriptionHistory', [SubscriberController::class, 'subscriptionHistory'])->name('subscriber.subscriptionHistory');
        Route::get('/getHistorySubscriber', [SubscriberController::class, 'getHistorySubscriber'])->name('subscriber.getHistorySubscriber');
        Route::get('/getPendingSubscriptionRenewal', [SubscriberController::class, 'getPendingSubscriptionRenewal'])->name('subscriber.getPendingSubscriptionRenewal');
        // subscriber request
        Route::post('/approveSubscribe', [SubscriberController::class, 'approveSubscribe'])->name('subscriber.approveSubscribe');
        Route::post('/rejectSubscribe', [SubscriberController::class, 'rejectSubscribe'])->name('subscriber.rejectSubscribe');
        Route::post('/terminateSubscribe', [SubscriberController::class, 'terminateSubscribe'])->name('subscriber.terminateSubscribe');

        Route::get('/subscribersListing', [SubscriberController::class, 'subscribersListing'])->name('subscriber.subscribersListing');

        //switch master request
        Route::get('/switch_master', [SubscriberController::class, 'switch_master'])->name('subscriber.switch_master');
        Route::get('/getSwitchMasterData', [SubscriberController::class, 'getSwitchMasterData'])->name('subscriber.getSwitchMasterData');
        Route::post('/approveSwitchMaster', [SubscriberController::class, 'approveSwitchMaster'])->name('subscriber.approveSwitchMaster');
        Route::post('/rejectSwitchMaster', [SubscriberController::class, 'rejectSwitchMaster'])->name('subscriber.rejectSwitchMaster');

    });

    /**
     * ==============================
     *         Subscriptions
     * ==============================
     */
    Route::prefix('subscriptions')->group(function () {
        Route::get('/pending_renewal', [SubscriptionController::class, 'pending_renewal'])->name('subscription.pending_renewal');
        Route::get('/getPendingRenewalData', [SubscriptionController::class, 'getPendingRenewalData'])->name('subscription.getPendingRenewalData');
        Route::get('/subscription_listing', [SubscriptionController::class, 'subscription_listing'])->name('subscription.subscription_listing');
        Route::get('/getSubscriptionBatchData', [SubscriptionController::class, 'getSubscriptionBatchData'])->name('subscription.getSubscriptionBatchData');
        Route::get('/termination_fee', [SubscriptionController::class, 'termination_fee'])->name('subscription.termination_fee');
        Route::get('/getTerminationFeeData', [SubscriptionController::class, 'getTerminationFeeData'])->name('subscription.getTerminationFeeData');

        // subscription renewal
        Route::post('/approveRenewalSubscription', [SubscriptionController::class, 'approveRenewalSubscription'])->name('subscription.approveRenewalSubscription');
        Route::post('/rejectRenewalSubscription', [SubscriptionController::class, 'rejectRenewalSubscription'])->name('subscription.rejectRenewalSubscription');
    });

    /**
     * ==============================
     *         Copy Trading
     * ==============================
     */
    Route::prefix('copy_trading')->group(function () {
        // Pending
        Route::get('/pending', [CopyTradingController::class, 'pending'])->name('copy_trading.pending');
        Route::get('/getPendingSubscription', [CopyTradingController::class, 'getPendingSubscription'])->name('copy_trading.getPendingSubscription');

        Route::patch('/subscriptionApproval', [CopyTradingController::class, 'subscriptionApproval'])->name('copy_trading.subscriptionApproval');

        // Listing
        Route::get('/listing', [CopyTradingController::class, 'index'])->name('copy_trading.listing');
        Route::get('/getSubscriptionOverview', [CopyTradingController::class, 'getSubscriptionOverview'])->name('copy_trading.getSubscriptionOverview');
        Route::get('/getSubscriptionsData', [CopyTradingController::class, 'getSubscriptionsData'])->name('copy_trading.getSubscriptionsData');

        // Termination Report
        Route::get('/termination_report', [CopyTradingController::class, 'termination_report'])->name('copy_trading.termination_report');
        Route::get('/getTerminationOverview', [CopyTradingController::class, 'getTerminationOverview'])->name('copy_trading.getTerminationOverview');
        Route::get('/getTerminationReportData', [CopyTradingController::class, 'getTerminationReportData'])->name('copy_trading.getTerminationReportData');

        // Switch Master Report
        Route::get('/switch_master', [CopyTradingController::class, 'switch_master'])->name('copy_trading.switch_master');
        Route::get('/getSwitchMasterData', [CopyTradingController::class, 'getSwitchMasterData'])->name('copy_trading.getSwitchMasterData');
    });

    /**
     * ==============================
     *            PAMM
     * ==============================
     */
    Route::prefix('pamm')->group(function () {
        //pending pamm
        Route::get('/pending_pamm', [PammController::class, 'pending_pamm'])->name('pamm.pending_pamm');
        Route::get('/getPendingPammData', [PammController::class, 'getPendingPammData'])->name('subscription.getPendingPammData');
        Route::patch('/pammSubscriptionApproval', [PammController::class, 'pammSubscriptionApproval'])->name('pamm.pammSubscriptionApproval');

        // pamm listing
        Route::get('/pamm_listing', [PammController::class, 'pamm_listing'])->name('pamm.pamm_listing');
        Route::get('/getPammListingData', [PammController::class, 'getPammListingData'])->name('pamm.getPammListingData');
        Route::get('/getPammSubscriptionOverview', [PammController::class, 'getPammSubscriptionOverview'])->name('pamm.getPammSubscriptionOverview');

        // Termination Report
        Route::get('/termination_report', [CopyTradingController::class, 'termination_report'])->name('pamm.termination_report');
        Route::get('/getTerminationOverview', [CopyTradingController::class, 'getTerminationOverview'])->name('pamm.getTerminationOverview');
        Route::get('/getTerminationReportData', [CopyTradingController::class, 'getTerminationReportData'])->name('pamm.getTerminationReportData');
    });

    /**
     * ==============================
     *         Report
     * ==============================
     */
    Route::prefix('report')->group(function () {
        Route::get('/trading_rebate', [ReportController::class, 'trading_rebate'])->name('report.trading_rebate');
        Route::get('/getTradingRebate', [ReportController::class, 'getTradingRebate'])->name('report.getTradingRebate');
        Route::get('/performance_incentive', [ReportController::class, 'performance_incentive'])->name('report.performance_incentive');
        Route::get('/getPerformanceIncentive', [ReportController::class, 'getPerformanceIncentive'])->name('report.getPerformanceIncentive');

        //Daily register
        Route::get('/daily_register',[ReportController::class, 'daily_register'])->name('report.daily_register');
        Route::get('/getDailyRegisterData',[ReportController::class, 'getDailyRegisterData'])->name('report.getDailyRegisterData');
        Route::get('/getDailyChildRegisterData',[ReportController::class, 'getDailyChildRegisterData'])->name('report.getDailyChildRegisterData');
    });

    /**
     * ==============================
     *         Setting
     * ==============================
     */
    Route::prefix('setting')->middleware('role:super-admin')->group(function () {
        //account types
        Route::get('/account_type', [SettingController::class, 'account_type'])->name('setting.account_type');
        Route::get('/getAccountTypes', [SettingController::class, 'getAccountTypes'])->name('setting.getAccountTypes');
        Route::post('/updateAccountType', [SettingController::class, 'updateAccountType'])->name('setting.updateAccountType');

        //payment setting
        Route::get('/payment_setting', [SettingController::class, 'paymentSetting'])->name('setting.payment_setting');
        Route::get('/getCryptoNetworks', [SettingController::class, 'getCryptoNetworks'])->name('setting.getCryptoNetworks');
        Route::post('/addPaymentSetting', [SettingController::class, 'addPaymentSetting'])->name('setting.addPaymentSetting');
        Route::post('/updatePaymentSetting', [SettingController::class, 'updatePaymentSetting'])->name('setting.updatePaymentSetting');
        Route::delete('/deletePayment', [SettingController::class, 'deletePayment'])->name('setting.deletePayment');
        Route::get('/getPaymentHistory', [SettingController::class, 'getPaymentHistory'])->name('setting.getPaymentHistory');
        Route::get('/getPaymentActivity', [SettingController::class, 'getPaymentActivity'])->name('setting.getPaymentActivity');

        //payment gateway
        Route::get('/payment_gateway', [SettingController::class, 'payment_gateway'])->name('setting.payment_gateway');
        Route::get('/getLeadersSel', [SettingController::class, 'getLeadersSel'])->name('setting.getLeadersSel');
        Route::get('/getPaymentGateways', [SettingController::class, 'getPaymentGateways'])->name('setting.getPaymentGateways');
        Route::post('/addPaymentGateway', [SettingController::class, 'addPaymentGateway'])->name('setting.addPaymentGateway');
        Route::post('/updatePaymentGateway', [SettingController::class, 'updatePaymentGateway'])->name('setting.updatePaymentGateway');
        Route::delete('/deletePaymentGateway', [SettingController::class, 'deletePaymentGateway'])->name('setting.deletePaymentGateway');

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

        // bank withdrawal setting
        Route::get('/bank_withdrawal_setting', [SettingController::class, 'bankWithdrawalSetting'])->name('setting.bank_withdrawal_setting');
        Route::get('/getLeaders', [SettingController::class, 'getLeaders'])->name('setting.getLeaders');
        Route::post('/updateBankWithdrawalSetting', [SettingController::class, 'updateBankWithdrawalSetting'])->name('setting.updateBankWithdrawalSetting');
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
