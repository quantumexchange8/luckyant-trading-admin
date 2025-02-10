<?php

namespace App\Http\Controllers;

use App\Exports\AccountPendingExport;
use App\Exports\TransactionsExport;
use App\Http\Requests\BalanceAdjustmentRequest;
use App\Models\CopyTradeTransaction;
use App\Models\PammSubscription;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\Mt5DeleteLog;
use App\Models\Wallet;
use App\Notifications\AddTradingAccountNotification;
use App\Services\dealAction;
use App\Services\RunningNumberService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Models\TradingAccount;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TradingAccountExport;
use App\Services\SelectOptionService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ChangeTradingAccountPassowrdNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\MetaFiveService;
use App\Services\passwordType;

class TradingController extends Controller
{
    public function account_listing()
    {
        return Inertia::render('Account/AccountListing/AccountListing', [
            'accountsCount' => TradingAccount::count(),
        ]);
    }

    public function getTradingAccount(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = TradingAccount::with([
                'user:id,name,email,hierarchyList',
                'tradingUser:id,meta_login,name,company,acc_status',
                'accountType:id,name,slug'
            ])
                ->withSum('active_copy_trade', 'meta_balance')
                ->withSum('active_pamm', 'subscription_amount');

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($query) use ($keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    })->orWhereHas('tradingUser', function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('company', 'like', '%' . $keyword . '%');
                    })->orWhere('meta_login', 'like', '%' . $keyword . '%');
                });
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            // Filter by leaderId if provided
            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('leader_status', 1)
                    ->where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('user_id', $usersUnderLeader);
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if (!empty($data['filters']['type']['value'])) {
                $accountType = $data['filters']['type']['value'];
                $query->whereIn('account_type', $accountType);
            }

            if (!empty($data['filters']['fund_type']['value'])) {
                $fundType = $data['filters']['fund_type']['value'];

                if ($fundType == 'DemoFund') {
                    $query->where('demo_fund', '>', 0);
                } else {
                    $query->where('demo_fund', '<=', 0)
                        ->orWhereNull('demo_fund');
                }
            }

            if (!empty($data['filters']['status']['value'])) {
                $status = $data['filters']['status']['value'];

                if ($status == 'zero_balance') {
                    $query->where('balance', 0)
                        ->orWhereNull('balance');
                } else {
                    $query->whereHas('tradingUser', function ($q) use ($status) {
                        $q->where('acc_status', $status);
                    });
                }
            }

            $authUser = Auth::user();

            if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
                $childrenIds = $authUser->getChildrenIds();
                $childrenIds[] = $authUser->id;
                $query->whereIn('user_id', $childrenIds);
            } elseif ($authUser->hasRole('super-admin')) {
                // Super-admin logic, no need to apply whereIn
            } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
                $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
                $query->whereIn('user_id', $childrenIds);
            } else {
                // No applicable conditions, set whereIn to empty array
                $query->whereIn('user_id', []);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            // Export logic
            if ($request->has('exportStatus') && $request->exportStatus) {
                return Excel::download(new TradingAccountExport($query), now() . '-trading-accounts.xlsx');
            }

            $transactions = $query->paginate($data['rows']);

            $userHierarchyLists = $transactions->pluck('user.hierarchyList')
                ->filter()
                ->flatMap(fn($list) => explode('-', trim($list, '-')))
                ->unique()
                ->toArray();

            // Load all potential leaders in bulk
            if ($leaderId > 0) {
                $leaderQuery = User::where('id', $leaderId)
                    ->where('leader_status', 1);
            } else {
                $leaderQuery = User::whereIn('id', $userHierarchyLists)
                    ->where('leader_status', 1);
            }

            $leaders = $leaderQuery->get()->keyBy('id');

            $transactions->each(function ($account) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($account->user?->hierarchyList, $leaders);

                $account->first_leader_id = $firstLeader?->id;
                $account->first_leader_name = $firstLeader?->name;
                $account->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function edit_leverage(Request $request)
    {
        if (is_array($request->margin_leverage)) {
            $leverage = $request->margin_leverage['leverage']['value'];
        } else {
            $leverage = $request->margin_leverage;
        }

        $metaService = new MetaFiveService();
        $metaService->updateLeverage($request->meta_login, $leverage);

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.toast_success_update_leverage_message'),
            'type' => 'success',
        ]);
    }

    public function change_password(Request $request)
    {
        Validator::make($request->all(), [
            'meta_login' => ['required'],
            'master_password' => ['sometimes', 'string'],
            'investor_password' => ['sometimes', 'string'],
        ])->setAttributeNames([
            'meta_login' => trans('public.meta_login'),
            'master_password' => trans('public.master_password'),
            'investor_password' => trans('public.investor_password'),
        ])->validate();

        $user = User::find($request->user_id);
        $meta_login = $request->meta_login;
        $master_password = $request->master_password;
        $investor_password = $request->investor_password;
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans("public.connection_error"),
                'message' => trans('public.try_again_later'),
                'type' => 'warning',
            ]);
        }

        if ($master_password || $investor_password) {
            if ($master_password) {
                $metaService->changePassword($meta_login, 0, $master_password);
            }

            if ($investor_password) {
                $metaService->changePassword($meta_login, 1, $investor_password);
            }

            Notification::route('mail', $user->email)
                ->notify(new ChangeTradingAccountPassowrdNotification($user, $meta_login, $master_password, $investor_password));
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.toast_success_update_password_message'),
            'type' => 'success',
        ]);
    }

    public function balanceAdjustment(BalanceAdjustmentRequest $request)
    {
        $user = User::find($request->user_id);
        $meta_login = $request->meta_login;
        $amount = $request->amount;
        $transaction_type = $request->transaction_type;
        $fund_type = $request->fund_type;

        $connection = (new MetaFiveService())->getConnectionStatus();
        $metaService = new MetaFiveService();

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans("public.connection_error"),
                'message' => trans('public.try_again_later'),
                'type' => 'warning',
            ]);
        }

        $tradingAccount = TradingAccount::with('subscriber')->where('meta_login', $meta_login)->first();

        try {
            $metaService->getUserInfo(collect([$tradingAccount]));
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        if ($transaction_type == 'BalanceOut') {
            // Check if balance is sufficient
            if (!empty($tradingAccount->subscriber) &&
                !empty($tradingAccount->unsubscribe_date) &&
                $tradingAccount->subscriber->unsubscribe_date->greaterThan(Carbon::now()->subHours(24))
            ) {
                throw ValidationException::withMessages(['amount' => trans('public.terminatiion_message')]);
            }

            // Check if balance is sufficient
            if ($fund_type == 'DemoFund') {
                if ($tradingAccount->demo_fund < $amount || $amount <= 0) {
                    throw ValidationException::withMessages(['amount' => 'Insufficient Demo Fund']);
                }
            } else {
                if ($tradingAccount->balance - $tradingAccount->demo_fund < $amount || $amount <= 0) {
                    throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
                }
            }
        }

        $deal = [];

        try {
            $deal = (new MetaFiveService())->createDeal($meta_login, $amount, $request->description, $transaction_type == 'BalanceIn' ? dealAction::DEPOSIT : dealAction::WITHDRAW);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        Transaction::create([
            'category' => 'trading_account',
            'user_id' => $user->id,
            'to_meta_login' => $meta_login,
            'ticket' => $deal['deal_Id'],
            'transaction_number' => RunningNumberService::getID('transaction'),
            'transaction_type' => $request->transaction_type,
            'fund_type' => $fund_type,
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'Success',
            'comment' => $deal['conduct_Deal']['comment'],
        ]);

        if ($request->fund_type == 'DemoFund') {
            $tradingUser = TradingUser::where('meta_login', $meta_login)->first();
            $tradingAccount = TradingAccount::where('meta_login', $meta_login)->first();

            if ($transaction_type == 'BalanceIn') {
                $tradingUser->demo_fund += $amount;
                $tradingAccount->demo_fund += $amount;
            } else {
                $tradingUser->demo_fund -= $amount;
                $tradingAccount->demo_fund -= $amount;
            }
            $tradingUser->save();
            $tradingAccount->save();
        }

        $subscriber = Subscriber::with(['master:id,meta_login', 'tradingAccount'])
            ->where('user_id', $user->id)
            ->where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Subscribing'])
            ->first();

        if ($subscriber && $subscriber->status == 'Pending') {
            $subscriber->initial_meta_balance += $amount;
            $subscriber->save();
        } elseif ($subscriber && $subscriber->status == 'Subscribing') {
            $subscriber->subscribe_amount += $amount;
            $subscriber->save();

            $subscription = Subscription::with(['master:id,meta_login', 'tradingAccount'])
                ->where('user_id', $user->id)
                ->where('meta_login', $meta_login)
                ->where('master_id', $subscriber->master_id)
                ->where('status', 'Active')
                ->first();

            if ($subscription) {
                $subscription->meta_balance += $amount;
                $subscription->save();

                CopyTradeTransaction::create([
                    'user_id' => $user->id,
                    'trading_account_id' => $tradingAccount->id,
                    'meta_login' => $tradingAccount->meta_login,
                    'subscription_id' => $subscription->id,
                    'master_id' => $subscription->master->id,
                    'master_meta_login' => $subscription->master->meta_login,
                    'amount' => $tradingAccount->balance,
                    'real_fund' => abs($tradingAccount->demo_fund - $tradingAccount->balance),
                    'demo_fund' => $tradingAccount->demo_fund,
                    'type' => 'Deposit',
                    'status' => 'Success',
                ]);

                SubscriptionBatch::create([
                    'user_id' => $user->id,
                    'trading_account_id' => $subscriber->trading_account_id,
                    'meta_login' => $meta_login,
                    'meta_balance' => $amount,
                    'real_fund' => $fund_type == 'RealFund' ? $amount : 0,
                    'demo_fund' => $fund_type == 'DemoFund' ? $amount : 0,
                    'master_id' => $subscriber->master_id,
                    'master_meta_login' => $subscriber->master_meta_login,
                    'type' => 'CopyTrade',
                    'subscriber_id' => $subscriber->id,
                    'subscription_id' => $subscription->id,
                    'subscription_number' => $subscription->subscription_number,
                    'subscription_period' => $subscriber->roi_period,
                    'transaction_id' => $subscriber->transaction_id,
                    'subscription_fee' => $subscriber->initial_subscription_fee,
                    'settlement_start_date' => now(),
                    'settlement_date' => now()->addDays($subscriber->roi_period)->endOfDay(),
                    'status' => 'Active',
                    'approval_date' => now() < $subscription->approval_date ? now()->addDay() : now(),
                ]);
            }
        }

        $pamm_subscription = PammSubscription::where('user_id', $user->id)
            ->where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Active'])
            ->first();

        if ($pamm_subscription && $pamm_subscription->status == 'Pending') {
            $pamm_subscription->subscription_amount += $amount;
            $pamm_subscription->save();
        } elseif ($pamm_subscription && $pamm_subscription->status == 'Active') {
            PammSubscription::create([
                'user_id' => $user->id,
                'meta_login' => $meta_login,
                'master_id' => $pamm_subscription->master_id,
                'master_meta_login' => $pamm_subscription->master_meta_login,
                'subscription_amount' => $amount,
                'subscription_package_id' => $pamm_subscription->subscription_package_id ?? null,
                'subscription_package_product' => $pamm_subscription->subscription_package_product ?? null,
                'type' => $pamm_subscription->type,
                'strategy_type' => $pamm_subscription->strategy_type,
                'subscription_number' => RunningNumberService::getID('subscription'),
                'subscription_period' => $pamm_subscription->subscription_period,
                'settlement_period' => $pamm_subscription->settlement_period,
                'settlement_date' => now()->addDays($pamm_subscription->settlement_period)->endOfDay(),
                'expired_date' => $pamm_subscription->subscription_period > 0 ? now()->addDays($pamm_subscription->subscription_period)->endOfDay() : null,
                'max_out_amount' => $pamm_subscription->max_out_amount,
                'status' => 'Active',
                'delivery_address' => $pamm_subscription->delivery_address ?? null,
            ]);
        }

        return back()->with('toast', [
            'title' => trans("public.success_adjustment"),
            'message' => trans('public.toast_success_adjustment_message'),
            'type' => 'success',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $tradingAcc = TradingAccount::find($request->id);
        $tradingUser = TradingUser::find($request->trade_user);

        $request->validate([
            'remarks' => ['required'],
        ]);

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans("public.connection_error"),
                'message' => trans('public.try_again_later'),
                'type' => 'warning',
            ]);
        } else {
            if ($tradingAcc->balance > 0) {
                return back()->with('toast', [
                    'title' => trans("public.invalid_action"),
                    'message' => trans('public.toast_warning_delete_message'),
                    'type' => 'warning',
                ]);
            }

            $metaService->deleteAccount($request->meta_login);
            $tradingUser->update([
                'remarks' => $request->remarks . ' - by ID : ' . \Auth::user()->id,
                'acc_status' => 'Deleted',
            ]);

            Mt5DeleteLog::create([
                'user_id' => $tradingAcc->user_id,
                'trading_account_id' => $request->id,
                'meta_login' => $request->meta_login,
                'type' => 'manual',
                'account_created_at' => $tradingAcc->created_at,
                'account_balance' => abs($tradingAcc->demo_fund ?? 0 - $tradingAcc->balance),
                'remarks' => $request->remarks,
                'handle_by' => \Auth::user()->id,
            ]);
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.toast_success_delete_message'),
            'type' => 'warning',
        ]);
    }

    public function getAccountByMetaLogin(Request $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();
        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans("public.connection_error"),
                'message' => trans('public.try_again_later'),
                'type' => 'warning',
            ]);
        }

        $tradingAccount = TradingAccount::firstWhere('meta_login', $request->meta_login);
        $metaService->getUserInfo(collect([$tradingAccount]));

        $account = $tradingAccount;
        return response()->json($account);
    }

    public function account_pending()
    {
        $balanceInCount = Transaction::where([
            'category' => 'trading_account',
            'transaction_type' => 'BalanceIn',
            'status' => 'Processing',
        ])
            ->count();

        return Inertia::render('Account/AccountPending/AccountPending', [
            'pendingBalanceInCount' => $balanceInCount
        ]);
    }

    public function getAccountPendingData(Request $request, UserService $userService)
    {
        $query = Transaction::with([
            'user:id,name,email,hierarchyList',
            'from_wallet:id,type,name,wallet_address',
//            'to_wallet:id,type,name,wallet_address',
//            'from_meta_login:id,meta_login',
            'to_meta_login:id,meta_login,account_type',
            'to_meta_login.accountType:id,name,slug',
        ])
            ->where([
            'category' => 'trading_account',
            'transaction_type' => $request->transaction_type,
            'status' => 'Processing',
        ]);

        $authUser = Auth::user();

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $query->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $query->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $query->whereIn('user_id', []);
        }

        if ($request->first_leader_id) {
            $first_leader = User::find($request->first_leader_id);
            $childrenIds = $first_leader->getChildrenIds();
            $query->whereIn('user_id', $childrenIds);
        }

        if ($request->export == 'yes') {
            return Excel::download(new AccountPendingExport($query), Carbon::now() . '-pending-' . $request->transaction_type . '-report.xlsx');
        }

        $pendings = $query->select([
            'id',
            'user_id',
            'from_wallet_id',
            'to_meta_login',
            'transaction_number',
            'fund_type',
            'amount',
            'transaction_charges',
            'transaction_amount',
            'created_at',
        ])
            ->orderByDesc('created_at')
            ->get();

        // Extract all hierarchy IDs from users' hierarchyLists
        $userHierarchyLists = $pendings->pluck('user.hierarchyList')
            ->filter()
            ->flatMap(fn($list) => explode('-', trim($list, '-')))
            ->unique()
            ->toArray();

        // Load all potential leaders in bulk
        $leaders = User::whereIn('id', $userHierarchyLists)
            ->where('leader_status', 1) // Only load users with leader_status == 1
            ->get()
            ->keyBy('id');

        // Attach the first leader details
        $pendings->each(function ($pending) use ($userService, $leaders) {
            $firstLeader = $userService->getFirstLeader($pending->user?->hierarchyList, $leaders);

            $pending->first_leader_id = $firstLeader?->id;
            $pending->first_leader_name = $firstLeader?->name;
            $pending->first_leader_email = $firstLeader?->email;
        });

        return response()->json([
            'pendings' => $pendings
        ]);
    }

    public function accountPendingApproval(Request $request)
    {
        Validator::make($request->all(), [
            'remarks' => ['required_if:action,reject'],
        ])->setAttributeNames([
            'remarks' => trans('public.remarks'),
        ])->validate();

        $transaction = Transaction::find($request->transaction_id);

        $checkSubscriptionAcc = Subscription::where('meta_login', $transaction->to_meta_login)
            ->where('status', 'Active')
            ->first();

        $checkSubscriptionBatchAcc = SubscriptionBatch::where('meta_login', $transaction->to_meta_login)
            ->where('status', 'Active')
            ->first();

        $checkPammAcc = PammSubscription::where('meta_login', $transaction->to_meta_login)
            ->where('status', 'Active')
            ->first();

        if ($checkPammAcc || $checkSubscriptionAcc || $checkSubscriptionBatchAcc) {
            return back()->with('toast', [
                'title' => trans("public.invalid_action"),
                'message' => trans("public.try_again_later"),
                'type' => 'warning',
            ]);
        }

        $wallet = Wallet::find($transaction->from_wallet_id);

        if ($request->action == 'approve') {
            $connection = (new MetaFiveService())->getConnectionStatus();

            $trading_account = TradingAccount::firstWhere('meta_login', $transaction->to_meta_login);

            if ($connection == 0) {
                try {
                    $comment = 'Deposit to trading account';
                    $deal = (new MetaFiveService())->createDeal($trading_account->meta_login, $transaction->amount, $comment, dealAction::DEPOSIT);

                    $dealId = $deal['deal_Id'] ?? null;

                    $transaction->update([
                        'ticket' => $dealId,
                        'status' => 'Success',
                        'approval_at' => now(),
                        'comment' => $comment,
                        'remarks' => 'Admin approved',
                        'handle_by' => Auth::id(),
                    ]);

                    $wallet->update([
                        'balance' => $wallet->balance - $transaction->amount
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error fetching trading accounts: ' . $e->getMessage());
                }
            } else {
                return back()->with('toast', [
                    'title' => trans("public.connection_error"),
                    'message' => trans("public.try_again_later"),
                    'type' => 'warning',
                ]);
            }
        } else {
            $transaction->update([
                'status' => 'Rejected',
                'approval_at' => now(),
                'remarks' => $request->remarks,
                'handle_by' => Auth::id(),
                'new_wallet_amount' => $wallet->balance + $transaction->amount,
            ]);

            $wallet->update([
                'balance' => $wallet->balance + $transaction->amount
            ]);
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_{$request->action}_deposit_message"),
            'type' => 'success',
        ]);
    }

    public function transaction_report(Request $request)
    {
        return Inertia::render('Account/AccountTransaction/AccountTransaction');
    }

    public function getAccountTransactionHistory(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Transaction::with([
                'user',
                'from_wallet:id,type,name,wallet_address',
                'to_wallet:id,type,name,wallet_address',
                'from_meta_login:id,meta_login',
                'to_meta_login:id,meta_login',
            ])
                ->where('category', 'trading_account')
                ->where('transaction_type', $data['filters']['type']['value'])
                ->whereNot('status', 'Processing');

            if ($data['filters']['global']['value']) {
                $query->whereHas('user', function($q) use ($data) {
                    $q->where(function ($query) use ($data) {
                        $keyword = $data['filters']['global']['value'];

                        $query->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                });
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            // Filter by leaderId if provided
            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('leader_status', 1)
                    ->where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('user_id', $usersUnderLeader);
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if ($data['filters']['fund_type']['value']) {
                $query->where('fund_type', $data['filters']['fund_type']['value']);
            }

            if ($data['filters']['status']['value']) {
                $query->where('status', $data['filters']['status']['value']);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            $authUser = Auth::user();

            if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
                $childrenIds = $authUser->getChildrenIds();
                $childrenIds[] = $authUser->id;
                $query->whereIn('user_id', $childrenIds);
            } elseif ($authUser->hasRole('super-admin')) {
                // Super-admin logic, no need to apply whereIn
            } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
                $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
                $query->whereIn('user_id', $childrenIds);
            } else {
                // No applicable conditions, set whereIn to empty array
                $query->whereIn('user_id', []);
            }

            // Export logic
            if ($request->has('exportStatus') && $request->exportStatus) {
                return Excel::download(new TransactionsExport($query), now() . '-'. $data['filters']['type']['value'] . 'report.xlsx');
            }

            // Calculate totals before pagination
            $totalAmount = (clone $query)->sum('amount');
            $successAmount = (clone $query)->where('status', 'Success')->sum('amount');
            $rejectedAmount = (clone $query)->where('status', 'Rejected')->sum('amount');

            $transactions = $query->paginate($data['rows']);

            $userHierarchyLists = $transactions->pluck('user.hierarchyList')
                ->filter()
                ->flatMap(fn($list) => explode('-', trim($list, '-')))
                ->unique()
                ->toArray();

            // Load all potential leaders in bulk
            if ($leaderId > 0) {
                $leaderQuery = User::where('id', $leaderId)
                    ->where('leader_status', 1);
            } else {
                $leaderQuery = User::whereIn('id', $userHierarchyLists)
                    ->where('leader_status', 1);
            }

            $leaders = $leaderQuery->get()->keyBy('id');

            $transactions->each(function ($transaction) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($transaction->user?->hierarchyList, $leaders);

                $transaction->first_leader_id = $firstLeader?->id;
                $transaction->first_leader_name = $firstLeader?->name;
                $transaction->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'totalAmount' => $totalAmount,
                'successAmount' => $successAmount,
                'rejectedAmount' => $rejectedAmount,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
