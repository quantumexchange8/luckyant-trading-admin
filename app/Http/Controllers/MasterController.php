<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use App\Models\MasterRequest;
use Carbon\Carbon;
use Inertia\Inertia;

class MasterController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Master/Master');
    }

    public function getMaster(Request $request, $type)
    {
        $query = MasterRequest::query()
            ->with(['user:id,name,email', 'trading_account'])
            ->where('status', $type);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                         ->orWhere('email', 'like', $search);
                })->orWhereHas('trading_account', function ($account) use ($search) {
                    $account->where('meta_login', 'like', $search);
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

        if ($request->filled('filter')) {
            $filter = $request->input('filter') ;
            $query->where(function ($q) use ($filter) {
                $q->where('status', $filter);
            });
        }

        $results = $query->latest()->paginate(10);

        // $results->each(function ($user_deposit) {
        //     $user_deposit->user->profile_photo_url = $user_deposit->user->getFirstMediaUrl('profile_photo');
        // });
        // dd($query);

        return response()->json([$type => $results]);
    }

    public function approveRequest(Request $request)
    {
        $masterRequest = MasterRequest::find($request->id);
        $tradingAccount = TradingAccount::find($masterRequest->trading_account_id);

        $masterRequest->update([
            'status' => 'Success'
        ]);

        Master::create([
            'user_id' => $masterRequest->user_id,
            'trading_account_id' => $masterRequest->trading_account_id,
            'meta_login' => $tradingAccount->meta_login,
        ]);

        return redirect()->back()
            ->with('title', 'Success approve')
            ->with('success', 'Successfully approved LOGIN: ' . $tradingAccount->meta_login . ' to MASTER');
    }
}
