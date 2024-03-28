<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Subscription;
use App\Models\SubscriptionRenewalRequest;
use App\Models\Transaction;
use App\Models\Subscriber;
use App\Models\Wallet;
use App\Models\Master;
use App\Models\User;
use App\Exports\SubscriptionHistoryExport;
use Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

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

    public function getPendingSubscriber(Request $request)
    {

        $pendingSubscriber = Subscriber::query()
            ->with(['user', 'master', 'master.user', 'subscription', 'tradingUser'])
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

        $results = $pendingSubscriber->latest()->paginate(10);

        return response()->json($results);
    }

    public function approveSubscribe(Request $request)
    {

        $subscribe = Subscriber::find($request->id);
        $subscriptionId = Subscription::find($request->subscriptionId);

        $cashWallet = Wallet::where('user_id', $request->userId)->where('type', 'cash_wallet')->first();

        $masterPeriod = Master::find($subscribe->master_id);
        
        $subscribe->update([
            'status' => 'Subscribing'
        ]);

        $subscriptionId->update([
            'status' => 'Active',
            'next_pay_date' => now()->addDays($masterPeriod->roi_period + 1)->startOfDay()->toDateString(),
            'expired_date' => now()->addDays($masterPeriod->roi_period + 1)->startOfDay(),
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);
        if ($request->transactionId) {
            $transactionId = Transaction::find($request->transactionId);

            $transactionId->update([
                'status' => 'Success',
                'transaction_amount' => $subscriptionId->subscription_fee,
                'new_wallet_amount' => $cashWallet->balance - $subscriptionId->subscription_fee
            ]);
        }

        $cashWallet->update([
            'balance' => $cashWallet->balance - $subscriptionId->subscription_fee
        ]);

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
        $activeSubscriber = Subscriber::query()
            ->with(['user', 'master', 'master.user', 'transaction', 'subscription', 'master.tradingUser'])
            ->where('status', 'Subscribing');

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

        $results = $activeSubscriber->latest()->paginate(10);

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader() ?? null;
        });

        return response()->json($results);
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
