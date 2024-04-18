<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Master;
use App\Models\Wallet;
use App\Models\Subscriber;
use App\Models\Transaction;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Exports\SubscriberExport;
use App\Services\MetaFiveService;
use App\Models\CopyTradeTransaction;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SubscriptionHistoryExport;
use App\Models\SubscriptionRenewalRequest;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SubscriptionConfirmationNotification;

class SubscriptionController extends Controller
{
    //
    public function subscribers()
    {
        return Inertia::render('Subscription/Subscription');
    }

    public function subscriptionHistory()
    {
        return Inertia::render('Subscription/SubscriptionListing');
    }

    public function getPendingSubscriptions(Request $request)
    {
        $pendingSubscriptions = Subscription::query()
            ->with(['user:id,name,email', 'master', 'master.tradingUser', 'tradingUser'])
            ->where('status', 'Pending');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $pendingSubscriptions->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                })
                ->orWhereHas('master', function ($master) use ($search) {
                    $master->where('meta_login', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $pendingSubscriptions->whereBetween('created_at', [$start_date, $end_date]);
        }

        $results = $pendingSubscriptions->latest()->paginate(10);

        $results->each(function ($transaction) {
            $transaction->user->profile_photo_url = $transaction->user->getFirstMediaUrl('profile_photo');
        });

        return response()->json($results);
    }

    public function approveSubscribe(Request $request)
    {
        $subscription = Subscription::find($request->subscriptionId);
        $user = User::find($request->userId);
        $cashWallet = $user->wallets()->where('type', 'cash_wallet')->first();
        $master = Master::find($subscription->master_id);

        $connection = (new MetaFiveService())->getConnectionStatus();
        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        try {
            (new MetaFiveService())->getUserInfo(TradingAccount::where('meta_login', $subscription->meta_login)->get());
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());

            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $trading_account = TradingAccount::where('meta_login', $subscription->meta_login)->first();

        $subscription->update([
            'meta_balance' => $trading_account->balance,
            'status' => 'Active',
            'next_pay_date' => now()->addDays($master->roi_period + 1)->startOfDay()->toDateString(),
            'expired_date' => now()->addDays($master->roi_period + 1)->startOfDay(),
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        Subscriber::create([
            'user_id' => $user->id,
            'trading_account_id' => $trading_account->id,
            'meta_login' => $trading_account->meta_login,
            'initial_meta_balance' => $trading_account->balance,
            'master_id' => $master->id,
            'master_meta_login' => $master->meta_login,
            'subscription_id' => $subscription->id,
            'status' => 'Subscribing',
            'approval_date' => now(),
        ]);

        CopyTradeTransaction::create([
            'user_id' => $user->id,
            'trading_account_id' => $trading_account->id,
            'meta_login' => $trading_account->meta_login,
            'subscription_id' => $subscription->id,
            'master_id' => $master->id,
            'master_meta_login' => $master->meta_login,
            'amount' => $trading_account->balance,
            'real_fund' => abs($trading_account->demo_fund - $trading_account->balance),
            'demo_fund' => $trading_account->demo_fund,
            'type' => 'Deposit',
            'status' => 'Success',
        ]);

        if ($request->transactionId) {
            $transaction = Transaction::find($request->transactionId);

            $transaction->update([
                'status' => 'Success',
                'transaction_amount' => $subscription->subscription_fee,
                'new_wallet_amount' => $cashWallet->balance - $subscription->subscription_fee
            ]);

            $cashWallet->update([
                'balance' => $cashWallet->balance - $subscription->subscription_fee
            ]);
        }

        Notification::route('mail', $user->email)->notify(new SubscriptionConfirmationNotification($subscription));

        return redirect()->back()
            ->with('title', 'Success approve')
            ->with('success', 'Approve this subscription');
    }

    public function rejectSubscribe(Request $request)
    {

        $request->validate([
            'remarks' => ['required'],
        ]);

        $reject = Subscriber::find($request->id);
        $subscriptionId = Subscription::find($request->subscriptionId);

        $reject->update([
            'status' => 'Rejected'
        ]);

        $subscriptionId->update([
            'status' => 'Rejected',
            'approval_date' => now(),
            'remarks' => $request->remarks,
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->transactionId) {
            $transactionId = Transaction::find($request->transactionId);
            $transactionId->update([
                'status' => 'Rejected',
                'remarks' => $request->remarks,
                'handle_by' => Auth::user()->id,
            ]);
        }

        return redirect()->back()
            ->with('title', 'Success rejrected')
            ->with('rejected', 'Rejected this subscription');
    }

    public function terminateSubscribe(Request $request)
    {


        $request->validate([
            'remarks' => ['required'],
        ]);

        $terminate = Subscriber::find($request->id);
        $subscriptionId = Subscription::find($request->subscriptionId);

        $terminate->update([
            'status' => 'Terminated',
        ]);

        $subscriptionId->update([
            'status' => 'Terminated',
            'termination_date' => now(),
            'remarks' => $request->remarks,
            'handle_by' => Auth::user()->id,
        ]);
        if ($request->transactionId) {
            $transactionId = Transaction::find($request->transactionId);

            $transactionId->update([
                'status' => 'Terminated',
                'remarks' => $request->remarks,
                'handle_by' => Auth::user()->id,
            ]);
        }

        return redirect()->back()
            ->with('title', 'Success terminated')
            ->with('terminated', 'Terminated this subscription');
    }

    public function approveRenewalSubscription(Request $request)
    {
        $renewal_request = SubscriptionRenewalRequest::find($request->id);
        $subscriptionId = Subscription::find($request->subscriptionId);

        $renewal_request->update([
            'status' => 'Success',
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        $subscriptionId->update([
            'status' => 'Active',
            'next_pay_date' => now()->addDays($masterPeriod->roi_period + 1)->startOfDay()->toDateString(),
            'expired_date' => now()->addDays($masterPeriod->roi_period + 1)->startOfDay(),
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        return redirect()->back()
            ->with('title', 'Success approve')
            ->with('success', 'Approve this subscription');
    }

    public function rejectRenewalSubscription(Request $request)
    {
        $request->validate([
            'remarks' => ['required'],
        ]);

        $reject_renewal = SubscriptionRenewalRequest::find($request->id);
        $subscriptionId = Subscription::find($request->subscriptionId);

        $reject_renewal->update([
            'status' => 'Rejected',
            'approval_date' => now(),
            'remarks' => $request->remarks,
            'handle_by' => Auth::user()->id,
        ]);

        $subscriptionId->update([
            'status' => 'Rejected',
            'approval_date' => now(),
            'remarks' => $request->remarks,
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->transactionId) {
            $transactionId = Transaction::find($request->transactionId);
            $transactionId->update([
                'status' => 'Rejected',
                'remarks' => $request->remarks,
                'handle_by' => Auth::user()->id,
            ]);
        }

        return redirect()->back()
            ->with('title', 'Success rejrected')
            ->with('rejected', 'Rejected this subscription');
    }

    public function getActiveSubscriber(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $activeSubscriber = Subscriber::query()
            ->with(['user', 'master', 'master.user', 'transaction', 'subscription', 'master.tradingUser']);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $activeSubscriber->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
                })
                ->orWhereHas('master', function ($master) use ($search) {
                    $master->where('meta_login', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $activeSubscriber->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('leader')) {
            $leader = $request->input('leader');
            $leaderUser = User::find($leader);
            if ($leaderUser) {
                $activeSubscriber->whereIn('user_id', $leaderUser->getChildrenIds());
            }
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $activeSubscriber->where('status', $status);
        }

        if ($request->has('exportStatus')) {
            return Excel::download(new SubscriberExport($activeSubscriber), Carbon::now() . '-subscribers-report.xlsx');
        }

        $totalSubscriberQuery = clone $activeSubscriber;
        $totalCopyTradeBalanceQuery = clone $activeSubscriber;

        if ($column == 'subscription_meta_balance') {
            $results = $activeSubscriber->join('subscriptions', 'subscribers.subscription_id', '=', 'subscriptions.id')
                ->orderBy('subscriptions.meta_balance', $sortOrder)
                ->paginate($request->input('paginate', 10));
        } else {
            $results = $activeSubscriber
                ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
                ->paginate($request->input('paginate', 10));
        }

        $totalSubscriber = $totalSubscriberQuery->count();
        $totalSubscribingSubscriber = $totalSubscriberQuery->where('status', 'Subscribing')->count();
        $totalCopyTradeBalance = $totalCopyTradeBalanceQuery
            ->where('status', 'Subscribing')
            ->with('subscription')
            ->get()
            ->sum(function ($subscriber) {
                return $subscriber->subscription->meta_balance;
            });

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader() ?? null;
        });

        return response()->json([
            'subscribers' => $results,
            'totalSubscriber' => $totalSubscriber,
            'totalSubscribingSubscriber' => $totalSubscribingSubscriber,
            'totalCopyTradeBalance' => $totalCopyTradeBalance,
        ]);
    }

    public function getHistorySubscriber(Request $request)
    {
        $historySubscriber = Subscription::query()
            ->with(['user', 'master', 'master.user', 'transaction', 'master.tradingUser'])
            ->whereNot('status', 'Pending');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $historySubscriber->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
                })
                ->orWhereHas('master', function ($master) use ($search) {
                    $master->where('meta_login', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $historySubscriber->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('filter')) {
            $filter = $request->input('filter') ;
            $historySubscriber->where(function ($q) use ($filter) {
                $q->where('status', $filter);
            });
        }

        if ($request->filled('leader')) {
            $leader = $request->input('leader');
            $leaderUser = User::find($leader);
            if ($leaderUser) {
                $historySubscriber->whereIn('user_id', $leaderUser->getChildrenIds());
            }
        }

        if ($request->has('exportStatus')) {
            return Excel::download(new SubscriptionHistoryExport($historySubscriber), Carbon::now() . '-' . 'Subscription_History-report.xlsx');
        }

        $results = $historySubscriber->latest()->paginate(10);

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader() ?? null;
        });

        return response()->json($results);
    }

    public function getPendingSubscriptionRenewal(Request $request)
    {
        $pendingRenewal = SubscriptionRenewalRequest::query()
            ->with(['user', 'subscription', 'subscription.master', 'subscription.master.user'])
            ->where('status', 'Pending');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $pendingRenewal->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
                })
                ->orWhereHas('master', function ($master) use ($search) {
                    $master->where('meta_login', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $pendingRenewal->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('filter')) {
            $filter = $request->input('filter') ;
            $pendingRenewal->where(function ($q) use ($filter) {
                $q->where('status', $filter);
            });
        }

        $results = $pendingRenewal->latest()->paginate(10);

        return response()->json($results);
    }

    public function subscribersListing()
    {
        return Inertia::render('Subscription/SubscriberListing');
    }
}
