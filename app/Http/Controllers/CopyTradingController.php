<?php

namespace App\Http\Controllers;

use App\Exports\SubscriptionExport;
use App\Jobs\ExportCopyTradingJob;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CopyTradingController extends Controller
{
    public function index()
    {
        return Inertia::render('CopyTrading/Listing/CopyTradeListing', [
            'subscriptionBatchesCount' => SubscriptionBatch::count(),
        ]);
    }

    public function getSubscriptionOverview()
    {
        // current month
        $endOfMonth = \Illuminate\Support\Carbon::now()->endOfMonth();

        // last month
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $subscriptionQuery = Subscription::where('status', 'Active');

        // current month active subscribers
        $current_month_active_subscriber = Subscriber::where('status', 'Subscribing')
            ->whereDate('approval_date', '<=', $endOfMonth)
            ->count();

        // current month active fund
        $current_month_active_fund = (clone $subscriptionQuery)
            ->whereDate('approval_date', '<=', $endOfMonth)
            ->sum('meta_balance');

        // last month active subscribers
        $last_month_active_subscriber =  Subscriber::where('status', 'Subscribing')
            ->whereDate('approval_date', '<=', $endOfLastMonth)
            ->count();

        // last month active fund
        $last_month_active_fund =  (clone $subscriptionQuery)
            ->whereDate('approval_date', '<=', $endOfLastMonth)
            ->sum('meta_balance');

        // comparison % of success deposit vs last month
        $last_month_active_subscriber_comparison = $current_month_active_subscriber - $last_month_active_subscriber;

        // comparison % of success deposit vs last month
        $last_month_active_fund_comparison = $last_month_active_fund > 0
            ? (($current_month_active_fund - $last_month_active_fund) / $last_month_active_fund) * 100
            : ($current_month_active_fund > 0 ? 100 : 0);

        // Get and format top 3 users by total deposit
        $topThreeUser = Subscription::select('user_id', DB::raw('SUM(meta_balance) as total_fund'))
            ->where('status', 'Active')
            ->groupBy('user_id')
            ->orderByDesc('total_fund')
            ->take(3)
            ->with(['user:id,name', 'user.media'])
            ->get()
            ->map(function ($subscription) {
                $subscription->profile_photo = $subscription->user->getFirstMediaUrl('profile_photo');
                return $subscription;
            });

        return response()->json([
            'currentMonthActiveSubscriber' => $current_month_active_subscriber,
            'lastMonthSubscriberComparison' => $last_month_active_subscriber_comparison,
            'currentActiveFund' => $current_month_active_fund,
            'lastMonthActiveFundComparison' => $last_month_active_fund_comparison,
            'topThreeUser' => $topThreeUser,
        ]);
    }

    public function getSubscriptionsData(Request $request)
    {
        $subscriptionQuery = SubscriptionBatch::with([
            'user:id,name,email,hierarchyList',
            'master:id,meta_login',
            'master.tradingUser:id,meta_login,name,company'
        ]);

        $authUser = Auth::user();

        $join_start_date = $request->query('joinStartDate');
        $join_end_date = $request->query('joinEndDate');

        if ($join_start_date && $join_end_date) {
            $start_date = Carbon::createFromFormat('Y-m-d', $join_start_date)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $join_end_date)->endOfDay();

            $subscriptionQuery->whereBetween('approval_date', [$start_date, $end_date]);
        }

        $terminate_start_date = $request->query('terminateStartDate');
        $terminate_end_date = $request->query('terminateEndDate');

        if ($terminate_start_date && $terminate_end_date) {
            $start_date = Carbon::createFromFormat('Y-m-d', $terminate_start_date)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $terminate_end_date)->endOfDay();

            $subscriptionQuery->whereBetween('termination_date', [$start_date, $end_date]);
        }

        if ($request->fundType) {
            switch ($request->fundType) {
                case 'demo_fund':
                    $subscriptionQuery->where('demo_fund', '>', 0);
                    break;

                case 'real_fund':
                    $subscriptionQuery->where('real_fund', '>', 0);
                    break;
            }
        }

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $subscriptionQuery->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $subscriptionQuery->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $subscriptionQuery->whereIn('user_id', []);
        }

        if ($request->export == 'yes') {
            if ($request->master_meta_login) {
                $subscriptionQuery->where('master_meta_login', $request->master_meta_login);
            }

            if ($request->first_leader_id) {
                $first_leader = User::find($request->first_leader_id);
                $childrenIds = $first_leader->getChildrenIds();
                $subscriptionQuery->whereIn('user_id', $childrenIds);
            }

            if ($request->status) {
                $subscriptionQuery->where('status', $request->status);
            }

            return Excel::download(new SubscriptionExport($subscriptionQuery), Carbon::now() . '-copy-trading-report.xlsx');
        }

        $subscription = $subscriptionQuery->latest()
            ->get()
            ->map(function ($subscriptionQuery) {
                $subscriptionQuery->first_leader = $subscriptionQuery->user?->getFirstLeader();

                return $subscriptionQuery;
            });

        return response()->json([
            'subscriptions' => $subscription
        ]);
    }
}
