<?php

namespace App\Http\Controllers;

use App\Exports\PammSubscriptionExport;
use App\Models\Master;
use App\Models\PammSubscription;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\SubscriptionConfirmationNotification;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class PammController extends Controller
{
    public function pending_pamm()
    {
        return Inertia::render('Pamm/PendingPamm/PendingPamm');
    }

    public function getPendingPammData(Request $request)
    {
        $authUser = \Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $pendingSubscriber = PammSubscription::query()
            ->with(['user', 'master', 'master.user', 'transaction', 'master.tradingUser'])
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
            return Excel::download(new PammSubscriptionExport($pendingSubscriber), Carbon::now() . '-pending-pamm-report.xlsx');
        }

        $totalSubscriberQuery = clone $pendingSubscriber;
        $totalCopyTradeBalanceQuery = clone $pendingSubscriber;

        $results = $pendingSubscriber
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $totalSubscriber = $totalSubscriberQuery->count();
        $totalCopyTradeBalance = $totalCopyTradeBalanceQuery
            ->sum('subscription_amount');

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader()->name ?? '-';
        });

        return response()->json([
            'subscribers' => $results,
            'totalSubscriber' => $totalSubscriber,
            'totalCopyTradeBalance' => $totalCopyTradeBalance,
        ]);
    }

    public function approveSubscribePamm(Request $request)
    {
        $pamm_subscription = PammSubscription::find($request->subscription_id);

        $pamm_subscription->update([
            'approval_date' => now(),
            'status' => 'Active',
            'handle_by' => \Auth::id(),
        ]);

        $response = Http::post('https://api.luckyantmallvn.com/serverapi/pamm/subscription/join', $pamm_subscription);
        \Log::debug($response);
        $masterAccount = Master::find($pamm_subscription->master_id);
        $masterAccount->total_fund += $pamm_subscription->subscription_amount;
        $masterAccount->save();
        $master_response = \Http::post('https://api.luckyantmallvn.com/serverapi/pamm/strategy', $masterAccount);
        \Log::debug($master_response);

        return redirect()->back()
            ->with('title', 'Success approve')
            ->with('success', 'Approve this subscription');
    }

    public function rejectSubscribePamm(Request $request)
    {
        $request->validate([
            'remarks' => ['required'],
        ]);

        $pamm_subscription = PammSubscription::find($request->subscription_id);
        $user = $pamm_subscription->user;
        $wallet = Wallet::where('user_id', $user->id)
            ->where('type', 'cash_wallet')
            ->first();

        $pamm_subscription->update([
            'approval_date' => now(),
            'status' => 'Active',
            'handle_by' => \Auth::id(),
        ]);

        Transaction::create([
            'category' => 'wallet',
            'user_id' => $user->id,
            'to_wallet_id' => $wallet->id,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'transaction_type' => 'PammEsg',
            'amount' => $pamm_subscription->subscription_amount,
            'transaction_charges' => 0,
            'transaction_amount' => $pamm_subscription->subscription_amount,
            'status' => 'Success',
            'remarks' => 'Reject PAMM Subscription',
            'new_wallet_amount' => $wallet->balance - $pamm_subscription->subscription_amount,
        ]);

        $wallet->balance += $pamm_subscription->subscription_amount;
        $wallet->save();

        return redirect()->back()
            ->with('title', 'Success rejected')
            ->with('warning', 'Rejected this subscription');
    }

    public function pamm_listing()
    {
        return Inertia::render('Pamm/PammListing/PammListing');
    }

    public function getPammListingData(Request $request)
    {
        $authUser = \Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'approval_date';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $subscription = PammSubscription::query()
            ->with(['user', 'master', 'transaction', 'master.tradingUser', 'package']);

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

        if ($request->filled('product')) {
            $product = '%' . $request->input('product') . '%';
            $subscription->where('subscription_package_product', 'like', $product);
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
            return Excel::download(new PammSubscriptionExport($subscription), Carbon::now() . '-pamm_subscription-report.xlsx');
        }

        $totalSubscriptionQuery = clone $subscription;
        $totalCopyTradeBalanceQuery = clone $subscription;

        $results = $subscription
            ->orderBy($column == null ? 'approval_date' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $totalSubscriptions = $totalSubscriptionQuery->count();
        $totalCopyTradeBalance = $totalCopyTradeBalanceQuery->sum('subscription_amount');

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
