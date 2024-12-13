<?php

namespace App\Http\Controllers;

use App\Exports\PerformanceIncentiveExport;
use App\Exports\TradeRebatesExport;
use App\Models\PerformanceIncentive;
use App\Models\TradeRebateHistory;
use App\Models\TradeRebateSummary;
use App\Models\User;
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

    public function getPerformanceIncentive(Request $request)
    {
        $authUser = \Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string

        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = PerformanceIncentive::query()
            ->with('user');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            });
        }

        if ($request->filled('leader')) {
            $leader = $request->input('leader');
            $leaderUser = User::find($leader);
            if ($leaderUser) {
                $query->whereIn('user_id', $leaderUser->getChildrenIds());
            }
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

        if ($request->has('exportStatus')) {
            return Excel::download(new PerformanceIncentiveExport($query), Carbon::now() . '-performance-incentive-report.xlsx');
        }

        $totalUserQuery = clone $query;

        $results = $query->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $totalTerminations = $totalUserQuery->distinct('user_id')->count();
        $totalPerformanceIncentives = $query->sum('personal_bonus_amt');

        $results->each(function ($performance) {
            $performance->first_leader = $performance->user->getFirstLeader()->name ?? '-';
        });

        return response()->json([
            'performanceIncentives' => $results,
            'totalUser' => $totalTerminations,
            'totalPerformanceIncentives' => $totalPerformanceIncentives,
        ]);
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
}
