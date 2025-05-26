<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplicationController;
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
use App\Http\Controllers\WorldPoolController;
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
    // Select Options
    Route::get('getMasters', [SelectOptionController::class, 'getMasters']);
    Route::get('getLeaders', [SelectOptionController::class, 'getLeaders']);
    Route::get('getSettlementPeriods', [SelectOptionController::class, 'getSettlementPeriods']);
    Route::get('getLeverages', [SelectOptionController::class, 'getLeverages']);
    Route::get('getAccountTypes', [SelectOptionController::class, 'getAccountTypes']);
    Route::get('getLeveragesByAccountType', [SelectOptionController::class, 'getLeveragesByAccountType']);
    Route::get('getCountries', [SelectOptionController::class, 'getCountries']);
    Route::get('getRanks', [SelectOptionController::class, 'getRanks']);
    Route::get('getUsers', [SelectOptionController::class, 'getUsers']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/getTopGroups', [DashboardController::class, 'getTopGroups'])->name('getTopGroups');
    Route::get('/getTotalDepositByDays', [DashboardController::class, 'getTotalDepositByDays'])->name('getTotalDepositByDays');

    /**
     * ==============================
     *           Members
     * ==============================
     */

    Route::prefix('member')->group(function () {
        // Member Listing
        Route::post('/addMember', [MemberController::class, 'addMember'])->name('member.addMember');
        Route::get('/member_listing', [MemberController::class, 'index'])->name('member.member_listing');
        Route::get('/getMemberListingData', [MemberController::class, 'getMemberListingData'])->name('member.getMemberListingData');
        Route::get('/member_details/{id}', [MemberController::class, 'viewMemberDetails'])->name('member.viewMemberDetails');
        Route::get('/getExtraBonus', [MemberController::class, 'getExtraBonus'])->name('member.getExtraBonus');
        Route::get('checkExportStatus', [MemberController::class, 'checkExportStatus'])->name('member.checkExportStatus');

        Route::delete('/export/delete', [MemberController::class, 'deleteReport'])->name('member.deleteReport');

        Route::post('/updateExtraBonus', [MemberController::class, 'updateExtraBonus'])->name('member.updateExtraBonus');
        Route::post('/updateLeaderStatus', [MemberController::class, 'updateLeaderStatus'])->name('member.updateLeaderStatus');

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
        Route::get('/impersonate/{user_id}', [MemberController::class, 'impersonate'])->name('member.impersonate');
        Route::get('/affiliate_listing', [MemberController::class, 'affiliate_listing'])->name('member.affiliate_listing');
        Route::get('/getAffiliateSummaries', [MemberController::class, 'getAffiliateSummaries'])->name('member.getAffiliateSummaries');

        //Member Details Update
        Route::post('/editMember', [MemberController::class, 'editMember'])->name('member.edit_member');
        Route::put('/updateMemberRank', [MemberController::class, 'updateMemberRank'])->name('member.updateMemberRank');
        Route::put('/updateMemberGroup', [MemberController::class, 'updateMemberGroup'])->name('member.updateMemberGroup');
        Route::put('/updateMemberPassword', [MemberController::class, 'updateMemberPassword'])->name('member.updateMemberPassword');

        // Member Fund
        Route::get('/member_fund', [MemberController::class, 'member_fund'])->name('member.member_fund');
        Route::get('/getMemberFundData', [MemberController::class, 'getMemberFundData'])->name('member.getMemberFundData');
    });

    /**
     * ==============================
     *           Accounts
     * ==============================
     */
    Route::prefix('account')->group(function () {
        // Account Listing
        Route::get('/account_listing', [TradingController::class, 'account_listing'])->name('account.account_listing');
        Route::get('/getTradingAccount', [TradingController::class, 'getTradingAccount'])->name('account.getTradingAccount');
        Route::get('/getAccountByMetaLogin', [TradingController::class, 'getAccountByMetaLogin'])->name('account.getAccountByMetaLogin');

        Route::post('/edit_leverage', [TradingController::class, 'edit_leverage'])->name('account.edit_leverage');
        Route::post('/change_password', [TradingController::class, 'change_password'])->name('account.change_password');
        Route::post('/balanceAdjustment', [TradingController::class, 'balanceAdjustment'])->name('account.balanceAdjustment');
        Route::delete('/deleteAccount', [TradingController::class, 'deleteAccount'])->name('account.deleteAccount');

        // Pending
        Route::get('/pending', [TradingController::class, 'account_pending'])->name('account.account_pending');
        Route::get('/getAccountPendingData', [TradingController::class, 'getAccountPendingData'])->name('account.getAccountPendingData');

        Route::patch('/accountPendingApproval', [TradingController::class, 'accountPendingApproval'])->name('account.accountPendingApproval');

        // Transaction Report
        Route::get('transaction_report', [TradingController::class, 'transaction_report'])->name('account.transaction_report');
        Route::get('getAccountTransactionHistory', [TradingController::class, 'getAccountTransactionHistory'])->name('account.getAccountTransactionHistory');
    });

    /**
     * ==============================
     *         Application
     * ==============================
     */
    Route::prefix('application')->group(function () {
        Route::get('application_listing', [ApplicationController::class, 'index'])->name('application.application_listing');
        Route::get('getApplicationData', [ApplicationController::class, 'getApplicationData'])->name('application.getApplicationData');
        Route::get('getApplicantsData', [ApplicationController::class, 'getApplicantsData'])->name('application.getApplicantsData');

        // Pending Application
        Route::get('pending_application', [ApplicationController::class, 'pending_application'])->name('application.pending_application');
        Route::get('getPendingApplications', [ApplicationController::class, 'getPendingApplications'])->name('application.getPendingApplications');

        Route::post('updateApplicationApproval', [ApplicationController::class, 'updateApplicationApproval'])->name('application.updateApplicationApproval');

        Route::post('addApplication', [ApplicationController::class, 'addApplication'])->name('application.addApplication');
        Route::post('updateApplication', [ApplicationController::class, 'updateApplication'])->name('application.updateApplication');
        Route::put('updateStatus', [ApplicationController::class, 'updateStatus'])->name('application.updateStatus');
        Route::delete('deleteApplication', [ApplicationController::class, 'deleteApplication'])->name('application.deleteApplication');
    });

    /**
     * ==============================
     *          World Pool
     * ==============================
     */
    Route::prefix('world_pool')->group(function () {
        Route::get('allocation', [WorldPoolController::class, 'index'])->name('world_pool.world_pool_allocation');
        Route::get('getAllocationData', [WorldPoolController::class, 'getAllocationData'])->name('world_pool.getAllocationData');

        Route::post('allocateWorldPool', [WorldPoolController::class, 'allocateWorldPool'])->name('world_pool.allocateWorldPool');
        Route::post('updateWorldPool', [WorldPoolController::class, 'updateWorldPool'])->name('world_pool.updateWorldPool');
    });

    /**
     * ==============================
     *          Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('/pendingTransaction', [TransactionController::class, 'pendingTransaction'])->name('transaction.pending_transaction');
        Route::get('/transactionHistory', [TransactionController::class, 'transactionHistory'])->name('transaction.transaction_history');
        Route::get('/getPendingTransaction', [TransactionController::class, 'getPendingTransaction'])->name('transaction.getPendingTransaction');
        Route::post('/approveTransaction', [TransactionController::class, 'approveTransaction'])->name('transaction.approveTransaction');
        Route::post('/rejectTransaction', [TransactionController::class, 'rejectTransaction'])->name('transaction.rejectTransaction');
        Route::get('/getTransactionHistory', [TransactionController::class, 'getTransactionHistory'])->name('transaction.getTransactionHistory');
        Route::get('/getBalanceHistory/{type}', [TransactionController::class, 'getBalanceHistory'])->name('transaction.getBalanceHistory');
        Route::get('checkExportStatus', [TransactionController::class, 'checkExportStatus'])->name('transaction.checkExportStatus');

        Route::delete('/export/delete', [TransactionController::class, 'deleteReport'])->name('transaction.deleteReport');
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
        Route::get('/new_announcement', [AnnouncementController::class, 'create'])->name('announcement.create');
        Route::get('/getAnnouncement', [AnnouncementController::class, 'getAnnouncement'])->name('getAnnouncement');
        Route::get('/edit_announcement/{id}', [AnnouncementController::class, 'edit'])->name('announcement.edit_announcement');

        Route::post('/addAnnouncement', [AnnouncementController::class, 'addAnnouncement'])->name('addAnnouncement');
        Route::post('/updateAnnouncement', [AnnouncementController::class, 'updateAnnouncement'])->name('announcement.updateAnnouncement');
        Route::put('/updateStatus', [AnnouncementController::class, 'updateStatus'])->name('announcement.updateStatus');

        Route::delete('/deleteAnnouncement', [AnnouncementController::class, 'deleteAnnouncement'])->name('announcement.deleteAnnouncement');
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

        // Profit Sharing
        Route::get('/profit_sharing', [ReportController::class, 'profit_sharing'])->name('report.profit_sharing');
        Route::get('/getProfitSharingData', [ReportController::class, 'getProfitSharingData'])->name('report.getProfitSharingData');
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
        Route::get('/getSettingPaymentMethods', [SettingController::class, 'getSettingPaymentMethods'])->name('setting.getSettingPaymentMethods');
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
