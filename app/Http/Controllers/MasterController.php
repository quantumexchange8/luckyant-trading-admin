<?php

namespace App\Http\Controllers;

use App\Exports\MasterExport;
use App\Models\Master;
use App\Models\MasterLeader;
use App\Models\MasterManagementFee;
use App\Models\MasterSubscriptionPackage;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\TradingAccount;
use App\Services\SelectOptionService;
use Illuminate\Http\Request;
use App\Models\MasterRequest;
use App\Models\User;
use App\Http\Requests\MasterConfigurationRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Auth;
use Maatwebsite\Excel\Facades\Excel;

class MasterController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Master/Master');
    }

    public function getMaster(Request $request, $type)
    {
        $authUser = Auth::user();

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

        $results = $query->latest()->paginate(10);

        // $results->each(function ($user_deposit) {
        //     $user_deposit->user->profile_photo_url = $user_deposit->user->getFirstMediaUrl('profile_photo');
        // });
        // dd($query);

        return response()->json([$type => $results]);
    }

    public function getMasterHistroy(Request $request)
    {
        $authUser = Auth::user();

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

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $masterHistory->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $masterHistory->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $masterHistory->whereIn('user_id', []);
        }

        $results = $masterHistory->latest()->paginate(10);

        return response()->json($results);
    }

    public function approveRequest(Request $request)
    {
        $masterRequest = MasterRequest::with('user:id,is_public')->find($request->id);
        $tradingAccount = TradingAccount::find($masterRequest->trading_account_id);

        if ($masterRequest->sharing_profit) {
            $profit = $masterRequest->sharing_profit;
            $total = 100;

            $default = ( $total - $profit) / 2;
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
            'market_profit' => $default ?? '20',
            'company_profit' => $default ?? '20',
            'subscription_fee' => $masterRequest->subscription_fee ?? 0,
            'roi_period' => $masterRequest->roi_period,
            'is_public' => $masterRequest->user->is_public,
        ]);

        return redirect()->route('master.viewMasterConfiguration', ['meta_login' => $master->meta_login])
            ->with('title', 'Success approve')
            ->with('success', 'Successfully approved LOGIN: ' . $tradingAccount->meta_login . ' to MASTER');
    }

    public function rejectRequest(Request $request)
    {

        $masterRequest = MasterRequest::find($request->id);
        $tradingAccount = TradingAccount::find($masterRequest->trading_account_id);

        $request->validate([
            'remarks' => ['required'],
        ]);

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
        $authUser = Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $masters = Master::query()->with(['trading_account', 'user', 'tradingUser']);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $masters->where(function ($q) use ($search) {
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

            $masters->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('publicStatus')) {
            $publicStatus = $request->input('publicStatus');
            $masters->where('is_public', $publicStatus);
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $masters->where('status', $status);
        }

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $masters->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $masters->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $masters->whereIn('user_id', []);
        }

        if ($request->has('exportStatus')) {
            return Excel::download(new MasterExport($masters), Carbon::now() . '-master-report.xlsx');
        }

        $totalMasterQuery = clone $masters;
        $totalPublicMasterQuery = clone $masters;

        $results = $masters
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $totalSubscriptions = $totalMasterQuery->count();
        $totalPublicMaster = $totalPublicMasterQuery->where('is_public', true)->count();
        $totalPrivateMaster = $totalMasterQuery->where('is_public', false)->count();

        $results->each(function ($user) {
            $user->first_leader = $user->user->getFirstLeader() ?? null;
        });

        return response()->json([
            'masters' => $results,
            'totalMasters' => $totalSubscriptions,
            'totalPublicMaster' => $totalPublicMaster,
            'totalPrivateMaster' => $totalPrivateMaster,
        ]);
        $authUser = Auth::user();
        $master = Master::query()->with(['trading_account', 'user', 'tradingUser']);

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

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $master->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $master->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $master->whereIn('user_id', []);
        }

        $results = $master->latest()->paginate(10);

        return response()->json($results);
    }

    public function viewMasterConfiguration($meta_login)
    {
        $masterConfigurations = Master::with(['tradingUser:id,meta_login,name,company', 'user', 'masterManagementFee', 'masterSubscriptionPackage'])
            ->where('meta_login', $meta_login)
            ->first();

        $totalFundSize = Subscription::where('master_id', $masterConfigurations->id)
            ->where('status', 'Active')
            ->sum('meta_balance');

        $subscriberQuery = Subscriber::where('status', 'Subscribing');
        $totalSubscribers = $subscriberQuery->count();
        $totalSubscribersBasedOnMaster = $subscriberQuery->where('master_meta_login', $meta_login)
            ->count();

        $masterConfigurations->user->profile_photo_url = $masterConfigurations->user->getFirstMediaUrl('profile_photo');
        $masterConfigurations->total_fund_size = $totalFundSize;
        $masterConfigurations->subscribe_percentage = ($totalSubscribersBasedOnMaster ?? 0 / $totalSubscribers) * 100;

        return Inertia::render('Master/Configuration/MasterConfiguration', [
            'masterConfigurations' => $masterConfigurations,
            'subscriberCount' => $masterConfigurations->subscribers->count(),
            'settlementPeriodSel' => (new SelectOptionService())->getSettlementPeriods(),
        ]);
    }

    public function updateMasterConfiguration(MasterConfigurationRequest $request)
    {
        $master = Master::find($request->master_id);

        $master->update([
            'category' => $request->category,
            'type' => $request->type,
            'min_join_equity' => $request->min_join_equity,
            'sharing_profit' => $request->sharing_profit,
            'market_profit' => $request->market_profit,
            'company_profit' => $request->company_profit,
            'subscription_fee' => $request->subscription_fee,
            'signal_status' => $request->signal_status,
            'estimated_monthly_returns' => $request->eta_montly_return,
            'estimated_lot_size' => $request->eta_lot_size,
            'join_period' => $request->join_period,
            'total_fund' => $request->total_fund,
            'roi_period' => $request->roi_period,
            'total_subscribers' => $request->total_subscriber,
            'max_drawdown' => $request->max_drawdown,
            'is_public' => $request->is_public,
        ]);

        if ($master->min_join_equity != null &&
            $master->sharing_profit != null &&
            $master->subscription_fee != null &&
            $master->subscription_fee != null) {
            $master->update([
                'status' => 'Active',
            ]);
        }

        if ($master->category == 'pamm') {
            $masterData = $master->toArray();
            $response = \Http::post('https://api.luckyantmallvn.com/serverapi/pamm/strategy', $masterData);
            \Log::debug($response);
        }

        return redirect()->back()
            ->with('title', 'Success configure setting')
            ->with('success', 'Successfully configure requirements to follow Master Account for LOGIN: ' . $master->meta_login);
    }

    public function updateMasterManagementFee(Request $request)
    {
        $managementDays = $request->management_days;
        $managementFees = $request->management_fees;

        $errors = [];

        // Validate management days
        foreach ($managementDays as $index => $day) {
            if (empty($day)) {
                $errors["management_fees.{$index}"] = trans('validation.required', ['attribute' => 'Management Period']);
            }
        }

        // Validate management fees
        foreach ($managementFees as $index => $fee) {
            if (empty($fee)) {
                $errors["management_fees.{$index}"] = trans('validation.required', ['attribute' => 'Fee Percentage']);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $master = Master::with('masterManagementFee')->find($request->master_id);

        foreach ($master->masterManagementFee as $item) {
            $item->delete();
        }

        foreach ($managementFees as $index => $fee) {
            MasterManagementFee::create([
                'master_id' => $master->id,
                'meta_login' => $master->meta_login,
                'penalty_percentage' => $fee,
                'penalty_days' => $managementDays[$index] // Set the corresponding days
            ]);
        }

        return redirect()->back()
            ->with('title', 'Success update')
            ->with('success', 'Successfully updated the management fees');
    }

    public function updateMasterSubscriptionPackage(Request $request)
    {
        $amounts = $request->amounts;
        $max_out_amounts = $request->max_out_amounts ?? 0;

        $errors = [];

        // Validate amounts
        foreach ($amounts as $index => $amount) {
            if (empty($amount)) {
                $errors["amounts.$index"] = trans('validation.required', ['attribute' => 'Amount']);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $master = Master::with('masterSubscriptionPackage')->find($request->master_id);

        foreach ($master->masterSubscriptionPackage() as $item) {
            $item->delete();
        }

        foreach ($max_out_amounts as $index => $max_amount) {
            MasterSubscriptionPackage::create([
                'master_id' => $master->id,
                'meta_login' => $master->meta_login,
                'label' => $amounts[$index],
                'amount' => $amounts[$index],
                'max_out_amount' => $max_amount
            ]);
        }

        return redirect()->back()
            ->with('title', 'Success update')
            ->with('success', 'Successfully updated the subscription packages');
    }

    public function addVisibleToLeaders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'master_id' => ['required'],
            'leader_ids' => ['nullable'],
        ])->setAttributeNames([
            'master_id' => 'Master',
            'leader_ids' => 'Leaders',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $leaders = $request->leader_ids;

            if ($leaders) {
                MasterLeader::where('master_id', $request->master_id)->delete();

                foreach ($leaders as $leader) {

                    MasterLeader::create([
                        'master_id' => $request->master_id,
                        'leader_id' => $leader['value'],
                    ]);
                }
            } else {
                MasterLeader::where('master_id', $request->master_id)->delete();
            }

            return redirect()->route('master.getMasterListing')
                ->with('title', 'Success update')
                ->with('success', 'Successfully updated visible to leaders');
        }
    }
}
