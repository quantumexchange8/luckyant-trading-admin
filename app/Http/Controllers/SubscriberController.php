<?php

namespace App\Http\Controllers;

use App\Models\Master;
use Auth;
use App\Exports\PendingSubscriberExport;
use App\Exports\SubscriberExport;
use App\Exports\SubscriptionExport;
use App\Exports\SubscriptionHistoryExport;
use App\Models\CopyTradeTransaction;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\SubscriptionRenewalRequest;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SubscriptionConfirmationNotification;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SubscriberController extends Controller
{
    public function pending_subscriber()
    {
        return Inertia::render('Subscriber/PendingSubscribers/PendingSubscribers');
    }

    public function subscribers()
    {
        return Inertia::render('Subscriber/Subscription');
    }

    public function subscriptionHistory()
    {
        return Inertia::render('Subscriber/SubscriptionListing');
    }

    public function getPendingSubscribers(Request $request)
    {
        $authUser = Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $pendingSubscriber = Subscriber::query()
            ->with(['user', 'master', 'master.user', 'transaction', 'subscription', 'master.tradingUser'])
            ->where('status', 'Pending');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $pendingSubscriber->where(function ($q) use ($search) {
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

            $pendingSubscriber->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('leader')) {
            $leader = $request->input('leader');
            $leaderUser = User::find($leader);
            if ($leaderUser) {
                $pendingSubscriber->whereIn('user_id', $leaderUser->getChildrenIds());
            }
        }

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $pendingSubscriber->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $pendingSubscriber->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $pendingSubscriber->whereIn('user_id', []);
        }

        if ($request->has('exportStatus')) {
            return Excel::download(new PendingSubscriberExport($pendingSubscriber), Carbon::now() . '-pending-subscribers-report.xlsx');
        }

        $totalSubscriberQuery = clone $pendingSubscriber;
        $totalCopyTradeBalanceQuery = clone $pendingSubscriber;

        if ($column == 'subscription_meta_balance') {
            $results = $pendingSubscriber->join('subscriptions', 'subscribers.subscription_id', '=', 'subscriptions.id')
                ->orderBy('subscriptions.meta_balance', $sortOrder)
                ->paginate($request->input('paginate', 10));
        } else {
            $results = $pendingSubscriber
                ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
                ->paginate($request->input('paginate', 10));
        }

        $totalSubscriber = $totalSubscriberQuery->count();
        $totalCopyTradeBalance = $totalCopyTradeBalanceQuery
            ->sum('initial_meta_balance');

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader()->name ?? null;
        });

        return response()->json([
            'subscribers' => $results,
            'totalSubscriber' => $totalSubscriber,
            'totalCopyTradeBalance' => $totalCopyTradeBalance,
        ]);
    }

    public function getPendingSubscriptions(Request $request)
    {
        $authUser = Auth::user();
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

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $pendingSubscriptions->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $pendingSubscriptions->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $pendingSubscriptions->whereIn('user_id', []);
        }

        $results = $pendingSubscriptions->latest()->paginate(10);

        $results->each(function ($transaction) {
            $transaction->user->profile_photo_url = $transaction->user->getFirstMediaUrl('profile_photo');
        });

        return response()->json($results);
    }

    public function approveSubscribe(Request $request)
    {
        $subscriber = Subscriber::find($request->subscriber_id);
        $user = User::find($request->userId);
        $cashWallet = $user->wallets()->where('type', 'cash_wallet')->first();

        $checkSubscribingAcc = Subscriber::where('meta_login', $subscriber->meta_login)
            ->where('status', 'Subscribing')
            ->first();

        if ($checkSubscribingAcc) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.try_again_later'));
        }

        $connection = (new MetaFiveService())->getConnectionStatus();
        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        try {
            (new MetaFiveService())->getUserInfo(TradingAccount::where('meta_login', $subscriber->meta_login)->get());
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());

            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $trading_account = TradingAccount::where('meta_login', $subscriber->meta_login)->first();

        $subscriber->update([
            'status' => 'Subscribing',
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        $subscription_number = RunningNumberService::getID('subscription');

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'trading_account_id' => $trading_account->id,
            'meta_login' => $trading_account->meta_login,
            'meta_balance' => $subscriber->initial_meta_balance,
            'master_id' => $subscriber->master_id,
            'type' => 'CopyTrade',
            'subscription_number' => $subscription_number,
            'subscription_period' => $subscriber->roi_period,
            'transaction_id' => $subscriber->transaction_id,
            'subscription_fee' => $subscriber->initial_subscription_fee,
            'next_pay_date' => now()->addDays($subscriber->roi_period)->endOfDay()->toDateString(),
            'expired_date' => now()->addDays($subscriber->roi_period)->endOfDay(),
            'status' => 'Active',
            'approval_date' => now(),
        ]);

        $subscriber->subscription_id = $subscription->id;
        $subscriber->save();

        CopyTradeTransaction::create([
            'user_id' => $user->id,
            'trading_account_id' => $trading_account->id,
            'meta_login' => $trading_account->meta_login,
            'master_id' => $subscriber->master_id,
            'master_meta_login' => $subscriber->master_meta_login,
            'amount' => $subscriber->initial_meta_balance,
            'real_fund' => abs($trading_account->demo_fund - $trading_account->balance),
            'demo_fund' => $trading_account->demo_fund,
            'type' => 'Deposit',
            'status' => 'Success',
        ]);

        $master = Master::with('masterManagementFee')->find($subscriber->master_id);

        $subscription_batch = SubscriptionBatch::create([
            'user_id' => $user->id,
            'trading_account_id' => $trading_account->id,
            'meta_login' => $trading_account->meta_login,
            'meta_balance' => $subscriber->initial_meta_balance,
            'real_fund' => abs($trading_account->demo_fund - $trading_account->balance),
            'demo_fund' => $trading_account->demo_fund ?? 0,
            'master_id' => $subscriber->master_id,
            'master_meta_login' => $subscriber->master_meta_login,
            'type' => 'CopyTrade',
            'subscriber_id' => $subscriber->id,
            'subscription_id' => $subscription->id,
            'subscription_number' => $subscription_number,
            'subscription_period' => $subscriber->roi_period,
            'transaction_id' => $subscriber->transaction_id,
            'subscription_fee' => $subscriber->initial_subscription_fee,
            'settlement_start_date' => now(),
            'settlement_date' => now()->addDays($master->masterManagementFee->sum('penalty_days'))->endOfDay(),
            'status' => 'Active',
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->transactionId) {
            $transaction = Transaction::find($request->transactionId);

            $transaction->update([
                'status' => 'Success',
                'transaction_amount' => $subscriber->initial_subscription_fee,
                'new_wallet_amount' => $cashWallet->balance - $subscriber->initial_subscription_fee
            ]);

            $cashWallet->update([
                'balance' => $cashWallet->balance - $subscriber->initial_subscription_fee
            ]);
        }

        Notification::route('mail', $user->email)->notify(new SubscriptionConfirmationNotification($subscription_batch));

        return redirect()->back()
            ->with('title', 'Success approve')
            ->with('success', 'Approve this subscription');
    }

    public function rejectSubscribe(Request $request)
    {
        $request->validate([
            'remarks' => ['required'],
        ]);

        $subscriber = Subscriber::find($request->subscriber_id);

        $subscriber->update([
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
            ->with('title', 'Success rejected')
            ->with('warning', 'Rejected this subscription');
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

    public function getActiveSubscriber(Request $request)
    {
        $authUser = Auth::user();
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

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $activeSubscriber->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $activeSubscriber->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $activeSubscriber->whereIn('user_id', []);
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
        $authUser = Auth::user();
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

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $historySubscriber->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $historySubscriber->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $historySubscriber->whereIn('user_id', []);
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
        $authUser = Auth::user();
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

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $pendingRenewal->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $pendingRenewal->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $pendingRenewal->whereIn('user_id', []);
        }

        $results = $pendingRenewal->latest()->paginate(10);

        return response()->json($results);
    }

    public function subscribersListing()
    {
        return Inertia::render('Subscriber/SubscriberListing');
    }

    public function getSubscriptionBatchData(Request $request)
    {
        $authUser = Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'approval_date';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $subscription = SubscriptionBatch::query()
            ->with(['user', 'master', 'transaction', 'master.tradingUser']);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $subscription->where(function ($q) use ($search) {
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

            $subscription->whereBetween('approval_date', [$start_date, $end_date]);
        }

        if ($request->filled('leader')) {
            $leader = $request->input('leader');
            $leaderUser = User::find($leader);
            if ($leaderUser) {
                $subscription->whereIn('user_id', $leaderUser->getChildrenIds());
            }
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $subscription->where('status', $status);
        }

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $subscription->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $subscription->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $subscription->whereIn('user_id', []);
        }

        if ($request->has('exportStatus')) {
            return Excel::download(new SubscriptionExport($subscription), Carbon::now() . '-subscription-report.xlsx');
        }

        $totalSubscriptionQuery = clone $subscription;
        $totalCopyTradeBalanceQuery = clone $subscription;

        $results = $subscription
            ->orderBy($column == null ? 'approval_date' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $totalSubscriptions = $totalSubscriptionQuery->count();
        $totalCopyTradeBalance = $totalCopyTradeBalanceQuery->sum('meta_balance');

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader() ?? null;
        });

        return response()->json([
            'subscribers' => $results,
            'totalSubscriptions' => $totalSubscriptions,
            'totalCopyTradeBalance' => $totalCopyTradeBalance,
        ]);
    }
}
