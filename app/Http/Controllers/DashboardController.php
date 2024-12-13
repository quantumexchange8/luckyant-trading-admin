<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\PammSubscription;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $authUser = \Auth::user();
        $announcements = Announcement::query()
                ->where('type', 'login')
                ->with('media')
                ->latest()
                ->first();

        $pendingDeposits = Transaction::where('category', 'wallet')->where('transaction_type', 'Deposit')->where('status', 'Processing');
        $pendingWithdrawals = Transaction::where('category', 'wallet')->where('transaction_type', 'Withdrawal')->where('status', 'Processing');
        $pendingSubscribers = Subscriber::where('status', 'Pending');
        $pendingPamm = PammSubscription::where('status', 'Pending');
        $pendingKyc = User::whereNot('role', 'admin')->where('kyc_approval', 'Pending');
        $dailyRegister = User::whereDate('created_at', Carbon::today());

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $pendingDeposits->whereIn('user_id', $childrenIds);
            $pendingWithdrawals->whereIn('user_id', $childrenIds);
            $pendingSubscribers->whereIn('user_id', $childrenIds);
            $pendingPamm->whereIn('user_id', $childrenIds);
            $pendingKyc->whereIn('id', $childrenIds);
            $dailyRegister->whereIn('id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $pendingDeposits->whereIn('user_id', $childrenIds);
            $pendingWithdrawals->whereIn('user_id', $childrenIds);
            $pendingSubscribers->whereIn('user_id', $childrenIds);
            $pendingPamm->whereIn('user_id', $childrenIds);
            $pendingKyc->whereIn('id', $childrenIds);
            $dailyRegister->whereIn('id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $pendingDeposits->whereIn('user_id', []);
            $pendingWithdrawals->whereIn('user_id', []);
            $pendingSubscribers->whereIn('user_id', []);
            $pendingPamm->whereIn('user_id', []);
            $pendingKyc->whereIn('id', []);
            $dailyRegister->whereIn('id', []);
        }

        return Inertia::render('Dashboard', [
            'announcements' => $announcements,
            'pendingDeposits' => number_format($pendingDeposits->sum('transaction_amount'), 2, '.', ''),
            'pendingWithdrawals' => number_format($pendingWithdrawals->sum('transaction_amount'), 2, '.', ''),
            'pendingSubscribers' => $pendingSubscribers->count(),
            'pendingPamm' => $pendingKyc->count(),
            'pendingKyc' => $pendingKyc->count(),
            'dailyRegister' => $dailyRegister->count(),
        ]);
    }

    public function getTopGroups()
    {
        $leaders = User::where('leader_status', 1)->get();
        $group_sales = [];

        foreach ($leaders as $leader) {
            $children_ids = $leader->getChildrenIds();

            $totals = SubscriptionBatch::query()
                ->where('status', 'Active')
                ->whereIn('user_id', $children_ids)
                ->selectRaw('SUM(meta_balance) as total_meta_balance, SUM(real_fund) as total_real_fund, SUM(demo_fund) as total_demo_fund')
                ->first();

            $total_meta_balance = $totals->total_meta_balance;
            $total_real_fund = $totals->total_real_fund;
            $total_demo_fund = $totals->total_demo_fund;

            $group_sales[] = [
                'name' => $leader->name,
                'email' => $leader->email,
                'total_meta_balance' => $total_meta_balance,
                'total_real_fund' => $total_real_fund,
                'total_demo_fund' => $total_demo_fund,
                'total_children' => count($children_ids)
            ];
        }

        // Sort the groups by total_meta_balance in descending order
        usort($group_sales, function ($a, $b) {
            return $b['total_meta_balance'] <=> $a['total_meta_balance'];
        });

        // Get the top 5 groups
        $top_5_groups = array_slice($group_sales, 0, 5);

        // Add rank to each group
        foreach ($top_5_groups as $index => $group) {
            $top_5_groups[$index]['rank'] = $index + 1;
        }

        return response()->json($top_5_groups);
    }

    public function getTotalDepositByDays(Request $request)
    {
        $authUser = \Auth::user();

        $baseQuery = Transaction::query()
            ->where('status', '=', 'Success')
            ->where('category', '=', 'trading_account')
            ->where('fund_type', 'RealFund')
            ->whereIn('transaction_type', ['BalanceIn']);

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $baseQuery->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $baseQuery->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $baseQuery->whereIn('user_id', []);
        }

        // Clone the base query for total deposit calculation
        $totalDeposit = (clone $baseQuery)->sum('transaction_amount');

        // Apply month and year filtering if provided
        $filteredQuery = $baseQuery->when($request->filled('month'), function ($query) use ($request) {
            $month = $request->input('month');
            $year = $request->input('year');
            $query->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);
        });

        // Calculate total month deposit using the filtered query
        $totalMonthDeposit = (clone $filteredQuery)->sum('transaction_amount');

        // Generate chart results using the filtered query
        $chartResults = $filteredQuery->select(
            DB::raw('DAY(created_at) as day'),
            'transaction_type',
            DB::raw('SUM(transaction_amount) as amount')
        )
            ->groupBy('day', 'transaction_type')
            ->get();

        // Get unique type to create datasets
        $uniqueTransactionType = $chartResults->whereIn('transaction_type', ['BalanceIn'])->pluck('transaction_type')->unique();
        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;

        // Initialize the chart data structure
        $chartData = [
            'labels' => range(1, cal_days_in_month(CAL_GREGORIAN, $month, $year)), // Generate an array of days
            'datasets' => [],
        ];

        $backgroundColors = ['BalanceIn' => '#12B76A', 'Withdrawal' => '#FF2D55'];

        // Loop through each unique type and create a dataset
        foreach ($uniqueTransactionType as $transactionType) {
            $transactionData = $chartResults->where('transaction_type', $transactionType);

            $dataset = [
                'label' => 'Balance In',
                'data' => array_map(function ($day) use ($transactionData) {
                    return $transactionData->firstWhere('day', $day)->amount ?? 0;
                }, $chartData['labels']),
                'borderColor' => $backgroundColors[$transactionType],
                'borderWidth' => 2,
                'pointStyle' => false,
                'fill' => true,
                'tension' => 0.1,
            ];

            $chartData['datasets'][] = $dataset;
        }

        return response()->json([
            'chartData' => $chartData,
            'totalDeposit' => $totalDeposit,
            'totalMonthDeposit' => $totalMonthDeposit
        ]);
    }
}
