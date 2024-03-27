<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use App\Models\MasterRequest;
use App\Models\User;
use App\Http\Requests\MasterConfigurationRequest;
use Carbon\Carbon;
use Inertia\Inertia;
use Auth;
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

    public function getMasterHistroy(Request $request)
    {

        $masterHistory = MasterRequest::query()
            ->with(['user:id,name,email', 'trading_account'])
            ->whereIn('status', ['Success', 'Rejected']);

        if ($request->filled('filter')) {
            $filter = $request->input('filter');
            $masterHistory->where(function ($q) use ($filter) {
                $q->where('status', $filter);
            });
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $masterHistory->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
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

            $masterHistory->whereBetween('created_at', [$start_date, $end_date]);
        }
        
        $results = $masterHistory->latest()->paginate(10);
        
        return response()->json($results);
    }

    public function approveRequest(Request $request)
    {
        $masterRequest = MasterRequest::find($request->id);
        $tradingAccount = TradingAccount::find($masterRequest->trading_account_id);

        if ($masterRequest->sharing_profit) {
            $profit = $masterRequest->sharing_profit;
            $total = 100;

            $defualt = ( $total - $profit) / 2;
        }

        $masterRequest->update([
            'status' => 'Success',
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        $master = Master::create([
            'user_id' => $masterRequest->user_id,
            'trading_account_id' => $masterRequest->trading_account_id,
            'meta_login' => $tradingAccount->meta_login,
            'min_join_equity' => $masterRequest->min_join_equity,
            'sharing_profit' => $masterRequest->sharing_profit ?? '60',
            'market_profit' => $defualt ?? '20',
            'company_profit' => $defualt ?? '20',
            'subscription_fee' => $masterRequest->subscription_fee,
            'roi_period' => $masterRequest->roi_period,
        ]);
    
        return redirect()->route('master.viewMasterConfiguration', ['id' => $master->id])
            ->with('title', 'Success approve')
            ->with('success', 'Successfully approved LOGIN: ' . $tradingAccount->meta_login . ' to MASTER');
    }

    public function rejectRequest(Request $request)
    {

        $masterRequest = MasterRequest::find($request->id);
        $tradingAccount = TradingAccount::find($masterRequest->trading_account_id);

        $masterRequest->update([
            'status' => 'Rejected',
            'remarks' => $request->remarks,
            'approval_date' => now(),
            'handle_by' => Auth::user()->id,
        ]);

        return redirect()->back()
            ->with('title', 'Success reject')
            ->with('success', 'Successfully rejected LOGIN: ' . $tradingAccount->meta_login . ' to MASTER');
    }

    public function getMasterListing()
    {
        return Inertia::render('Master/MasterListing/MasterListing');
    }

    public function getAllMaster(Request $request)
    {

        $master = Master::query()->with(['trading_account', 'user']);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $master->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                })->orWhereHas('trading_account', function ($account) use ($search) {
                    $account->where('meta_login', 'like', $search);
                });
            });
        }

        $results = $master->latest()->paginate(10);

        return response()->json($results);
    }

    public function viewMasterConfiguration($id)
    {

        $masterConfigurations = Master::find($id);
        

        return Inertia::render('Master/Configuration/MasterConfiguration', [
            'masterConfigurations' => $masterConfigurations,
            'subscriberCount' => $masterConfigurations->subscribers->count(),
        ]);
    }

    public function updateMasterConfiguration(MasterConfigurationRequest $request)
    {
        
        $master = Master::find($request->master_id);

        $master->update([
            'min_join_equity' => $request->min_join_equity,
            'sharing_profit' => $request->sharing_profit,
            'market_profit' => $request->market_profit,
            'company_profit' => $request->company_profit,
            'subscription_fee' => $request->subscription_fee,
            'signal_status' => $request->signal_status,
            'estimated_monthly_returns' => $request->eta_montly_return,
            'estimated_lot_size' => $request->eta_lot_size,
            'extra_fund' => $request->extra_fund,
            'total_fund' => $request->total_fund,
            'roi_period' => $request->roi_period,
            'total_subscribers' => $request->total_subscriber,
            'max_drawdown' => $request->max_drawdown,
            'management_fee' => $request->management_fee,
        ]);

        if ($master->min_join_equity != null &&
            $master->sharing_profit != null &&
            $master->subscription_fee != null &&
            $master->subscription_fee != null) {
            $master->update([
                'status' => 'Active',
            ]);
        }

        return redirect()->back()
            ->with('title', 'Success configure setting')
            ->with('success', 'Successfully configure requirements to follow Master Account for LOGIN: ' . $master->meta_login);
    }
}
