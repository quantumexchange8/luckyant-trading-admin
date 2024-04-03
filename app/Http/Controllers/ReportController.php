<?php

namespace App\Http\Controllers;

use App\Exports\TradeRebatesExport;
use App\Models\TradeRebateHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string

        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = TradeRebateHistory::query()
            ->with('upline_user:id,name,email', 'user:id,name,email');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                })
                    ->orWhereHas('upline_user', function ($to_wallet) use ($search) {
                        $to_wallet->where('name', 'like', $search)
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

        if ($request->has('exportStatus')) {
            return Excel::download(new TradeRebatesExport($query), Carbon::now() . '-trade-rebates-report.xlsx');
        }

        $trade_rebates = $query->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json($trade_rebates);
    }
}
