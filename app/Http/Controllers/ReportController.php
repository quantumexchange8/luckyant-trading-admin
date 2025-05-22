<?php

namespace App\Http\Controllers;

use App\Exports\PerformanceIncentiveExport;
use App\Exports\ProfitSharingExport;
use App\Exports\TradeRebatesExport;
use App\Models\PammSubscription;
use App\Models\PerformanceIncentive;
use App\Models\Subscription;
use App\Models\SubscriptionProfitHistory;
use App\Models\TradeRebateHistory;
use App\Models\TradeRebateSummary;
use App\Models\TradingAccount;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function trading_rebate()
    {
        return Inertia::render('Report/TradingRebate/TradingRebate');
    }

    public function getTradingRebate(Request $request)
    {
        $authUser = \Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string

        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = TradeRebateSummary::query()
            ->with('upline_user:id,name,email', 'user:id,name,email')
            ->where('status', 'Approved');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('upline_user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

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

        $totalRebateQuery = clone $query;

        if ($request->has('exportStatus')) {
            return Excel::download(new TradeRebatesExport($query), Carbon::now() . '-trade-rebates-report.xlsx');
        }

        $trade_rebates = $query->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json([
            'result' => $trade_rebates,
            'totalRebateAmount' => $totalRebateQuery->sum('rebate'),
            'totalTradeLots' => $totalRebateQuery->sum('volume'),
        ]);
    }

    public function performance_incentive()
    {
        return Inertia::render('Report/PerformanceIncentive/PerformanceIncentive');
    }

    public function getPerformanceIncentive(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = PerformanceIncentive::with([
                'user',
                'subscription',
                'subscription.user',
                'pamm_subscription',
                'pamm_subscription.user',
            ]);

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($user) use ($keyword) {
                        $user->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%')
                            ->orWhere('username', 'like', '%' . $keyword . '%');
                    });
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = \Illuminate\Support\Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            $category = $data['filters']['category']['value'];

            if ($category) {
                if ($category == 'pamm') {
                    $query->where('category', 'pamm');
                } else {
                    $query->whereNot('category', 'pamm');
                }
            }

            $type = $data['filters']['type']['value'];

            if ($type) {
                if ($type == 'personal') {
                    $query->whereNotNull('meta_login');
                } else {
                    $query->whereNull('meta_login');
                }
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

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
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
                return Excel::download(new PerformanceIncentiveExport($query), Carbon::now() . '-performance-incentive-report.xlsx');
            }

            $total_user = (clone $query)->distinct('user_id')->count();
            $total_performance_incentive = (clone $query)->sum('personal_bonus_amt');

            $performanceIncentives = $query->paginate($data['rows']);

            $userHierarchyLists = $performanceIncentives->pluck('user.hierarchyList')
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

            $performanceIncentives->each(function ($transaction) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($transaction->user?->hierarchyList, $leaders);

                $transaction->first_leader_id = $firstLeader?->id;
                $transaction->first_leader_name = $firstLeader?->name;
                $transaction->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $performanceIncentives,
                'totalUser' => $total_user,
                'totalPerformanceIncentive' => (float) $total_performance_incentive,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function daily_register()
    {
        return Inertia::render('Report/DailyRegister/DailyRegister');
    }

    public function getDailyRegisterData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = User::query()
                ->where('leader_status', 1)
                ->select('id', 'name', 'email', 'leader_status', 'created_at');

            if ($data['filters']['global']['value']) {
                $query->where(function ($query) use ($data) {
                    $keyword = $data['filters']['global']['value'];
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
                });
            }

            $authUser = Auth::user();

            if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
                $childrenIds = $authUser->getChildrenIds();
                $childrenIds[] = $authUser->id;
                $query->whereIn('id', $childrenIds);
            } elseif ($authUser->hasRole('super-admin')) {
                // Super-admin logic, no need to apply whereIn
            } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
                $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
                $query->whereIn('id', $childrenIds);
            } else {
                // No applicable conditions, set whereIn to empty array
                $query->whereIn('id', []);
            }

            $leaders = $query->paginate($data['rows']);

            // Calculate metrics for each leader
            $leaders->getCollection()->transform(function ($leader) {
                $leader->total_downline = User::query()
                    ->where('hierarchyList', 'like', '%-' . $leader->id . '-%')
                    ->where('status', 'Active')
                    ->count();

                $leader->today_register = User::query()
                    ->where('hierarchyList', 'like', '%-' . $leader->id . '-%')
                    ->where('status', 'Active')
                    ->whereDate('created_at', today())
                    ->count();

                return $leader;
            });

            // Apply dynamic or default sorting
            $sortedLeaders = $leaders->getCollection();

            // Check if custom sorting is provided
            if (!empty($data['sortField'])) {
                $sortOrder = !empty($data['sortOrder']) && $data['sortOrder'] == 1 ? 'asc' : 'desc';

                $sortedLeaders = $sortedLeaders->sortBy(
                    $data['sortField'], SORT_REGULAR, $sortOrder === 'desc'
                );
            } else {
                // Default sorting by today_register descending
                $sortedLeaders = $sortedLeaders
                    ->sortByDesc('today_register')
                    ->sortByDesc('total_downline');
            }

            // Replace the collection with the sorted collection
            $leaders->setCollection($sortedLeaders->values());

            return response()->json([
                'success' => true,
                'data' => $leaders,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function getDailyChildRegisterData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            // Extract the leader ID from the request
            $leaderId = $data['filters']['leader_id']['value'];

            // Initialize the query to get the leader's children who have registered
            $query = User::query()
                ->where('hierarchyList', 'like', '%-' . $leaderId . '-%') // Filter
                ->selectRaw('DATE(created_at) as registration_date, COUNT(*) as total_registers') // Group by date and count registrations
                ->groupBy('registration_date') // Group by the created_at date
                ->when(!empty($data['filters']['global']['value']), function ($query) use ($data) {
                    $keyword = $data['filters']['global']['value'];
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
                });

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderBy('registration_date', 'desc');
            }

            $registrations = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $registrations,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function profit_sharing()
    {
        return Inertia::render('Report/ProfitSharing/ProfitSharing');
    }

    public function getProfitSharingData(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);
            $category = $data['filters']['category']['value'];

            $query = SubscriptionProfitHistory::with([
                'user',
            ])->where('category', $category);

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($user) use ($keyword) {
                        $user->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%')
                            ->orWhere('username', 'like', '%' . $keyword . '%');
                    })->orWhere('subscription_number', 'like', '%' . $keyword . '%');
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = \Illuminate\Support\Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
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

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
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
                return Excel::download(new ProfitSharingExport($query), Carbon::now() . '-profit-sharing-report.xlsx');
            }

            if ($category == 'pamm') {
                $active_capital = PammSubscription::where('status', 'Active')
                    ->sum('subscription_amount');
            } else {
                $active_capital = Subscription::where('status', 'Active')
                    ->sum('meta_balance');
            }

            $total_profit_sharing = (clone $query)->sum('profit_sharing_amt');

            $performanceIncentives = $query->paginate($data['rows']);

            $userHierarchyLists = $performanceIncentives->pluck('user.hierarchyList')
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

            $performanceIncentives->each(function ($transaction) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($transaction->user?->hierarchyList, $leaders);

                $transaction->first_leader_id = $firstLeader?->id;
                $transaction->first_leader_name = $firstLeader?->name;
                $transaction->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $performanceIncentives,
                'activeCapital' => (float) $active_capital,
                'totalProfitSharing' => (float) $total_profit_sharing,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
