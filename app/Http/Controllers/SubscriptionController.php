<?php

namespace App\Http\Controllers;

use App\Exports\PendingRenewalExport;
use App\Exports\SubscriptionExport;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\SubscriptionPenaltyLog;
use App\Models\SubscriptionRenewalRequest;
use App\Models\Transaction;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SubscriptionController extends Controller
{
    public function pending_renewal()
    {
        return Inertia::render('Subscription/PendingRenewal/PendingRenewal');
    }

    public function getPendingRenewalData(Request $request)
    {
        $authUser = Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $pendingRenewal = SubscriptionRenewalRequest::query()
            ->with(['user', 'subscription', 'subscription.master', 'subscription.master.tradingUser'])
            ->where('status', 'Pending');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $pendingRenewal->where(function ($q) use ($search) {
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

            $pendingRenewal->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('leader')) {
            $leader = $request->input('leader');
            $leaderUser = User::find($leader);
            if ($leaderUser) {
                $pendingRenewal->whereIn('user_id', $leaderUser->getChildrenIds());
            }
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $pendingRenewal->where('status', $status);
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

        if ($request->has('exportStatus')) {
            return Excel::download(new PendingRenewalExport($pendingRenewal), Carbon::now() . '-pending-renewal-report.xlsx');
        }

        $results = $pendingRenewal
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader() ?? null;
        });

        return response()->json([
            'subscribers' => $results,
        ]);
    }

    public function approveRenewalSubscription(Request $request)
    {
        $renewal_request = SubscriptionRenewalRequest::find($request->id);
        $subscription = Subscription::find($request->subscriptionId);

        $renewal_request->update([
            'status' => 'Success',
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        $subscription->update([
            'auto_renewal' => true,
        ]);

        $subscriber = Subscriber::where('subscription_id', $subscription->id)
            ->where('status', 'Expiring')
            ->first();

        $subscriber->update([
            'status' => 'Subscribing',
            'auto_renewal' => true,
        ]);

        $batches = SubscriptionBatch::where('subscriber_id', $subscriber->id)
            ->where('status', 'Expiring')
            ->get();

        foreach ($batches as $batch) {
            $batch->update([
                'status' => 'Active',
                'auto_renewal' => true,
            ]);
        }

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

        $reject_renewal->update([
            'status' => 'Rejected',
            'approval_date' => now(),
            'remarks' => $request->remarks,
            'handle_by' => Auth::user()->id,
        ]);

        return redirect()->back()
            ->with('title', 'Success rejrected')
            ->with('rejected', 'Rejected this subscription');
    }

    public function subscription_listing()
    {
        return Inertia::render('Subscription/SubscriptionListing');
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

    public function termination_fee()
    {
        return Inertia::render('Subscription/TerminationFee');
    }

    public function getTerminationFeeData(Request $request)
    {
        $authUser = Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'approval_date';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $subscription = SubscriptionBatch::query()
            ->with(['user', 'master', 'transaction', 'master.tradingUser', 'subscription_penalty'])
            ->where('status', 'Terminated');

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

        $results->each(function ($batch) {
            $batch->first_leader = $batch->user->getFirstLeader() ?? null;
            $batch->return_amount = $batch->subscription_penalty ? $batch->meta_balance - $batch->subscription_penalty->penalty_amount : $batch->meta_balance;
        });

        return response()->json([
            'subscribers' => $results,
            'totalSubscriptions' => $totalSubscriptions,
            'totalCopyTradeBalance' => $totalCopyTradeBalance,
        ]);
    }
}
