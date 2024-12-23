<?php

namespace App\Http\Controllers;

use App\Exports\MasterExport;
use App\Models\Group;
use App\Models\Master;
use App\Models\MasterLeader;
use App\Models\MasterManagementFee;
use App\Models\MasterSubscriptionPackage;
use App\Models\MasterToLeader;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\TradingAccount;
use App\Services\MetaFiveService;
use App\Services\SelectOptionService;
use DB;
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
            'signal_status' => $request->signal_status ?? 1,
            'estimated_monthly_returns' => $request->eta_montly_return,
            'estimated_lot_size' => $request->eta_lot_size,
            'join_period' => $request->join_period,
            'total_fund' => $request->total_fund,
            'roi_period' => $request->roi_period,
            'total_subscribers' => $request->total_subscriber,
            'max_drawdown' => $request->max_drawdown,
            'is_public' => $request->is_public,
            'delivery_requirement' => $request->delivery_requirement,
        ]);

        if ($master->min_join_equity != null &&
            $master->sharing_profit != null &&
            $master->subscription_fee != null &&
            $master->subscription_fee != null) {
            $master->update([
                'status' => 'Active',
            ]);
        }

        if ($request->hasFile('en_tnc_pdf')) {
            $master->clearMediaCollection('en_tnc_pdf');
            $master->addMedia($request->en_tnc_pdf)->toMediaCollection('en_tnc_pdf');
        }

        if ($request->hasFile('cn_tnc_pdf')) {
            $master->clearMediaCollection('cn_tnc_pdf');
            $master->addMedia($request->cn_tnc_pdf)->toMediaCollection('cn_tnc_pdf');
        }

        if ($request->hasFile('en_tree_pdf')) {
            $master->clearMediaCollection('en_tree_pdf');
            $master->addMedia($request->en_tree_pdf)->toMediaCollection('en_tree_pdf');
        }

        if ($request->hasFile('cn_tree_pdf')) {
            $master->clearMediaCollection('cn_tree_pdf');
            $master->addMedia($request->cn_tree_pdf)->toMediaCollection('cn_tree_pdf');
        }

//        if ($master->category == 'pamm') {
//            $masterData = $master->toArray();
//            $response = \Http::post('https://api.luckyantmallvn.com/serverapi/pamm/strategy', $masterData);
//            \Log::debug($response);
//        }

        return redirect()->back()
            ->with('title', 'Success configure setting')
            ->with('success', 'Successfully configure requirements to follow Master Account for LOGIN: ' . $master->meta_login);
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

        foreach ($master->masterSubscriptionPackage as $item) {
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

            MasterLeader::where('master_id', $request->master_id)->delete();
            if ($leaders) {

                foreach ($leaders as $leader) {

                    MasterLeader::create([
                        'master_id' => $request->master_id,
                        'leader_id' => $leader['value'],
                    ]);
                }
            }

            return redirect()->route('master.getMasterListing')
                ->with('title', 'Success update')
                ->with('success', 'Successfully updated visible to leaders');
        }
    }

    public function master_listing()
    {
        return Inertia::render('Master/MasterListing/MasterListing', [
            'mastersCount' => Master::count(),
        ]);
    }

    public function getMasters(Request $request)
    {
        // fetch limit with default
        $limit = $request->input('limit', 12);

        // Fetch parameter from request
        $search = $request->input('search', '');
        $sortType = $request->input('sortType');
        $leaders = $request->input('leaders');
        $tag = $request->input('tag', '');
        $masterType = $request->input('category', '');
        $strategy_type = $request->input('strategy_type', '');
        $pamm_type = $request->input('pamm_type', '');
        $status = $request->input('status', '');

        // Fetch paginated masters
        $mastersQuery = Master::query()
            ->with([
                'tradingUser:id,meta_login,name',
                'leaders:user_id,name',
                'media'
            ])
            ->withCount([
                'active_copy_trades',
                'active_pamm'
            ])
            ->withSum('active_copy_trades', 'subscribe_amount')
            ->withSum('active_pamm', 'subscription_amount')
            ->withSum('close_trades', 'trade_profit')
            ->addSelect([
                'latest_profit' => DB::table('trade_histories')
                    ->select('trade_profit')
                    ->whereColumn('trade_histories.meta_login','masters.meta_login')
                    ->latest('created_at')
                    ->limit(1),
            ]);

        // Apply search parameter to multiple fields
        if (!empty($search)) {
            $keyword = '%' . $search . '%';
            $mastersQuery->where(function ($q) use ($keyword) {
                $q->whereHas('tradingUser', function ($account) use ($keyword) {
                    $account->where('meta_login', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword)
                        ->orWhere('company', 'like', $keyword);
                });
            });
        }

        // Apply sorting dynamically
        if (in_array($sortType, ['latest', 'largest_fund', 'most_investors'])) {
            switch ($sortType) {
                case 'latest':
                    $mastersQuery->orderBy('created_at', 'desc');
                    break;

                case 'largest_fund':
                    $mastersQuery->orderByDesc(DB::raw('COALESCE(active_copy_trades_sum_subscribe_amount, 0) + COALESCE(active_pamm_sum_subscription_amount, 0)'));
                    break;

                case 'most_investors':
                    $mastersQuery->orderByDesc(DB::raw('COALESCE(active_copy_trades_count, 0) + COALESCE(active_pamm_count, 0)'));
                    break;

                default:
                    return response()->json(['error' => 'Invalid filter'], 400);
            }
        }

        // // Apply leaders filter
        if (!empty($leaders)) {
            $mastersQuery->whereHas('leaders', function ($query) use ($leaders) {
                $query->whereIn('user_id', [$leaders]);
            });
        }

        // // Apply adminUser filter
        // if (!empty($adminUser)) {
        //     dd($request->all());
        // }

        // Apply tag filter
        if (!empty($tag)) {
            $tags = explode(',', $tag);

            foreach ($tags as $tag) {
                if ($tag == 'no_min_investment') {
                    $mastersQuery->where('min_join_equity', 0);
                } elseif ($tag == 'lock_free') {
                    $mastersQuery->where(function ($query) {
                        $query->where('join_period', 0)
                            ->orWhereNull('join_period');
                    });
                } elseif ($tag == 'zero_fee') {
                    $mastersQuery->where(function ($query) {
                        $query->where('sharing_profit', 0)
                            ->orWhereNull('sharing_profit');
                    });
                } else {
                    return response()->json(['error' => 'Invalid filter'], 400);
                }
            }
        }

        // Apply master type filter
        if (!empty($masterType)) {
            $mastersQuery->where('category', $masterType);
        }

        // Apply strategy type filter
        if (!empty($strategy_type)) {
            $mastersQuery->where('strategy_type', $strategy_type);
        }

        // Apply pamm type filter
        if (!empty($pamm_type)) {
            $mastersQuery->where('type', $pamm_type);
        }

        // Apply status filter
        if (!empty($status)) {
            $mastersQuery->where('status', $status);
        }

        $authUser = Auth::user();

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $mastersQuery->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $mastersQuery->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $mastersQuery->whereIn('user_id', []);
        }

        // Get total count of masters
        $totalRecords = $mastersQuery->count();

        // Fetch paginated results
        $masters = $mastersQuery->paginate($limit);

        // Format masters
        $formattedMasters = $masters->map(function ($master) {
            $master->leaders_names = $master->leaders->pluck('name')->join(', ');
            return $master;
        });

        return response()->json([
            'masters' => $formattedMasters,
            'totalRecords' => $totalRecords,
            'currentPage' => $masters->currentPage(),
        ]);
    }

    public function addMaster(Request $request)
    {
        $form_step = $request->step;

        $rules = [
            'leader' => ['required'],
            'category' => ['required'],
            'type' => ['required_if:category,pamm'],
            'strategy_type' => ['required'],
            'max_fund_percentage' => ['required_if:strategy_type,Alpha'],
            'estimated_monthly_return' => ['required'],
            'estimated_lot_size' => ['required'],
            'max_drawdown' => ['required'],
            'total_fund' => ['nullable'],
            'total_subscribers' => ['nullable'],
        ];

        $attributeNames = [
            'leader' => trans('public.leader'),
            'category' => trans('public.type'),
            'type' => trans('public.pamm_type'),
            'strategy_type' => trans('public.strategy_type'),
            'max_fund_percentage' => trans('public.max_fund_percentage'),
            'estimated_monthly_return' => trans('public.estimated_monthly_returns'),
            'estimated_lot_size' => trans('public.estimated_lot_size'),
            'max_drawdown' => trans('public.max_drawdown'),
            'total_fund' => trans('public.total_fund'),
            'total_subscribers' => trans('public.total_subscribers'),
        ];

        switch ($form_step) {
            case 1:
                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();
                return back();

            case 2:
                $rules['min_investment'] = ['required'];
                $rules['sharing_profit'] = ['required'];
                $rules['market_profit'] = ['required'];
                $rules['company_profit'] = ['required'];
                $rules['join_period'] = ['nullable'];
                $rules['roi_period'] = ['nullable'];
                $rules['delivery_requirement'] = ['nullable'];
                $rules['leaders'] = ['required'];
                $rules['can_top_up'] = ['required'];
                $rules['can_revoke'] = ['required'];

                $attributeNames['min_investment'] = trans('public.min_investment');
                $attributeNames['sharing_profit'] = trans('public.shared');
                $attributeNames['market_profit'] = trans('public.market');
                $attributeNames['company_profit'] = trans('public.company');
                $attributeNames['join_period'] = trans('public.join_period');
                $attributeNames['roi_period'] = trans('public.roi_period');
                $attributeNames['delivery_requirement'] = trans('public.delivery_requirement');
                $attributeNames['leaders'] = trans('public.visible_to');
                $attributeNames['can_top_up'] = trans('public.top_up');
                $attributeNames['can_revoke'] = trans('public.revoke');

                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();
                return back();

            default:
                $rules['management_fee'] = ['nullable'];
                $attributeNames['management_fee'] = trans('public.management_fee');

                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();
                break;
        }

        $user = $request->leader;

        $master = Master::create([
            'user_id' => $user['id'],
            'category' => $request->category,
            'type' => $request->type ?? 'CopyTrade',
            'strategy_type' => $request->strategy_type,
            'min_join_equity' => $request->min_investment,
            'sharing_profit' => $request->sharing_profit,
            'market_profit' => $request->market_profit,
            'company_profit' => $request->company_profit,
            'subscription_fee' => $request->subscription_fee ?? 0,
            'signal_status' => $request->signal_status ?? 1,
            'estimated_monthly_returns' => $request->estimated_monthly_return,
            'estimated_lot_size' => $request->estimated_lot_size,
            'join_period' => $request->join_period,
            'roi_period' => $request->roi_period,
            'total_subscribers' => $request->total_subscribers,
            'total_fund' => $request->total_fund,
            'max_drawdown' => $request->max_drawdown,
            'is_public' => $request->is_public ?? 1,
            'can_top_up' => $request->can_top_up,
            'can_revoke' => $request->can_revoke,
            'delivery_requirement' => $request->delivery_requirement ?? 0,
            'status' => 'Active',
        ]);

        if ($master->strategy_type == 'Alpha') {
            $master->max_fund_percentage = $request->max_fund_percentage;
        }

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans('public.connection_error'),
                'message' => trans('public.meta_trader_connection_error'),
                'type' => 'error',
            ]);
        }

        $userModel = User::find($user['id']);
        $group = Group::firstWhere('display', $master->strategy_type);
        $metaAccount = $metaService->createUser($userModel, $group, 500);
        $trading_account = TradingAccount::firstWhere('meta_login', $metaAccount['login']);

        $master->trading_account_id = $trading_account->id;
        $master->meta_login = $metaAccount['login'];
        $leaders = $request->leaders;

        if ($leaders) {
            foreach ($leaders as $leader) {
                MasterToLeader::create([
                    'master_id' => $master->id,
                    'user_id' => $leader['id'],
                ]);
            }
        }
        $master->save();

        $managementFees = $request->input('management_fee', []);

        foreach ($managementFees as $fee) {
            MasterManagementFee::create([
                'master_id' => $master->id,
                'meta_login' => $master->meta_login,
                'penalty_days' => $fee['days'],
                'penalty_percentage' => $fee['percentage'],
            ]);
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_create_master_success'),
            'type' => 'success',
        ]);
    }

    public function updateMaster(MasterConfigurationRequest $request)
    {
        $master = Master::find($request->master_id);

        $master->update([
            'category' => $request->category,
            'type' => $request->type,
            'strategy_type' => $request->strategy_type,
            'min_join_equity' => $request->min_investment,
            'sharing_profit' => $request->sharing_profit,
            'market_profit' => $request->market_profit,
            'company_profit' => $request->company_profit,
            'subscription_fee' => $request->subscription_fee ?? 0,
            'signal_status' => $request->signal_status ?? 1,
            'estimated_monthly_returns' => $request->estimated_monthly_returns,
            'estimated_lot_size' => $request->estimated_lot_size,
            'join_period' => $request->join_period,
            'roi_period' => $request->roi_period,
            'total_subscribers' => $request->total_subscribers,
            'max_drawdown' => $request->max_drawdown,
            'is_public' => $request->is_public,
            'can_top_up' => $request->can_top_up,
            'can_revoke' => $request->can_revoke,
            'delivery_requirement' => $request->delivery_requirement,
        ]);

        if ($master->category == 'copy_trade') {
            $master->total_fund = $request->total_fund;
            $master->save();
        }

        if ($master->strategy_type == 'Alpha') {
            $master->max_fund_percentage = $request->max_fund_percentage;
            $master->save();
        }

        $leaders = $request->leaders;

        if ($leaders) {
            $existingLeaders = MasterToLeader::where('master_id', $master->id)
                ->pluck('user_id')
                ->toArray();

            $incomingLeaderIds = collect($leaders)->pluck('id')->toArray();

            if (!empty(array_diff($existingLeaders, $incomingLeaderIds)) || !empty(array_diff($incomingLeaderIds, $existingLeaders))) {
                MasterToLeader::where('master_id', $master->id)->delete();

                foreach ($leaders as $leader) {
                    MasterToLeader::create([
                        'master_id' => $master->id,
                        'user_id' => $leader['id'],
                    ]);
                }
            }
        } else {
            MasterToLeader::where('master_id', $master->id)->delete();
        }

        if ($request->master_logo) {
            $master->clearMediaCollection('master_logo');
            $master->addMedia($request->master_logo)->toMediaCollection('master_logo');
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_master_message"),
            'type' => 'success',
        ]);
    }

    public function getMasterOverview()
    {
        $authUser = Auth::user();

        $mastersQuery = Master::query();
        $subscriberQuery = Subscriber::where('status', 'Subscribing');

        // Apply filtering based on role and leader status
        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $mastersQuery->whereIn('user_id', $childrenIds);
            $subscriberQuery->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $mastersQuery->whereIn('user_id', $childrenIds);
            $subscriberQuery->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $mastersQuery->whereIn('user_id', []);
            $subscriberQuery->whereIn('user_id', []);
        }

        // Current month and last month
        $endOfMonth = \Illuminate\Support\Carbon::now()->endOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Current month total masters
        $current_month_total_master = (clone $mastersQuery)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->count();

        // Current month active subscribers
        $current_month_active_subscribers = (clone $subscriberQuery)
            ->whereDate('approval_date', '<=', $endOfMonth)
            ->count();

        // Last month total masters
        $last_month_total_master = (clone $mastersQuery)
            ->whereDate('created_at', '<=', $endOfLastMonth)
            ->count();

        // Last month active subscribers
        $last_month_active_subscribers = (clone $subscriberQuery)
            ->whereDate('approval_date', '<=', $endOfLastMonth)
            ->count();

        // Comparison: total masters vs last month
        $last_month_total_master_comparison = $current_month_total_master - $last_month_total_master;

        // Comparison: total subscribers vs last month
        $last_month_active_subscribers_comparison = $current_month_active_subscribers - $last_month_active_subscribers;

        return response()->json([
            'currentMonthTotalMaster' => $current_month_total_master,
            'lastMonthMasterComparison' => $last_month_total_master_comparison,
            'currentTotalSubscribers' => $current_month_active_subscribers,
            'lastMonthSubscribersComparison' => $last_month_active_subscribers_comparison,
        ]);
    }

    public function getMasterAnalyticChartData()
    {
        $mastersQuery = Master::select([
            'id',
            'meta_login'
        ])
            ->with('tradingUser:name,meta_login')
            ->withCount([
                'active_copy_trades',
                'active_pamm'
            ]);

        $authUser = Auth::user();

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $mastersQuery->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $mastersQuery->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $mastersQuery->whereIn('user_id', []);
        }

        $masters = $mastersQuery->get()
            ->filter(function ($master) {
                // Calculate total subscribers and filter only those with total > 0
                $totalSubscribers =
                    ($master->active_copy_trades_count ?? 0) +
                    ($master->active_pamm_count ?? 0);
                return $totalSubscribers > 0;
            });

        // Initialize chart data
        $chartData = [
            'labels' => [], // Labels will hold master names
            'datasets' => [],
        ];

        $symbolCount = []; // To store the sum of active copy trades and active PAMM counts
        $backgroundColors = [];

        foreach ($masters as $master) {
            // Label: master trading user name
            $chartData['labels'][] = $master->tradingUser->name ?? "Unknown";

            // Data: sum of active copy trades and active PAMM counts
            $totalSubscribers =
                ($master->active_copy_trades_count ?? 0) +
                ($master->active_pamm_count ?? 0);
            $symbolCount[] = $totalSubscribers;

            // Generate a random color
            $randomColor = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
            $backgroundColors[] = $randomColor;
        }

        $dataset = [
            'data' => $symbolCount,
            'backgroundColor' => $backgroundColors,
            'offset' => 5,
            'borderColor' => 'transparent'
        ];

        // Add dataset to the chart data
        $chartData['datasets'][] = $dataset;

        return response()->json($chartData);
    }

    public function getMasterManagementFee(Request $request)
    {
        $fee = MasterManagementFee::where('master_id', $request->master_id)
            ->get();

        return response()->json($fee);
    }

    public function updateMasterManagementFee(Request $request)
    {
        $managementFees = $request->input('management_fee', []);

        $errors = [];

        foreach ($managementFees as $index => $fee) {
            // Validate 'days' field
            if (empty($fee['days'])) {
                $errors["management_fee.{$index}.days"] = trans('validation.required', ['attribute' => trans('public.days')]);
            }

            // Validate 'percentage' field
            if (empty($fee['percentage'])) {
                $errors["management_fee.{$index}.percentage"] = trans('validation.required', ['attribute' => trans('public.fee_percentage')]);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $master = Master::with('masterManagementFee')->findOrFail($request->input('master_id'));

        // Check if the incoming data is the same as existing data
        $existingFees = $master->masterManagementFee->map(function ($fee) {
            return [
                'days' => $fee->penalty_days,
                'percentage' => $fee->penalty_percentage,
            ];
        });

        $incomingFees = collect($managementFees);

        // If incoming data matches the existing data, skip the function
        if ($existingFees->toArray() == $incomingFees->toArray()) {
            return back()->with('toast', [
                'title' => trans("public.info"),
                'message' => trans("public.toast_info_no_updated_message"),
                'type' => 'info',
            ]);
        }

        // Delete existing management fees
        foreach ($master->masterManagementFee as $item) {
            $item->delete();
        }

        // Save new management fees
        foreach ($managementFees as $fee) {
            MasterManagementFee::create([
                'master_id' => $master->id,
                'meta_login' => $master->meta_login,
                'penalty_days' => $fee['days'],
                'penalty_percentage' => $fee['percentage'],
            ]);
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_fee_message"),
            'type' => 'success',
        ]);
    }

    public function updateTncFile(Request $request)
    {
        $master = Master::find($request->master_id);

        $pamm_tnc_files = $request->tnc_pdf;
        $tree_tnc_files = $request->tree_pdf;

        if ($pamm_tnc_files) {
            foreach ($pamm_tnc_files as $locale => $pamm_tnc_file) {
                if ($pamm_tnc_file) {
                    $master->clearMediaCollection($locale . '_tnc_pdf');
                    $master->addMedia($pamm_tnc_file)->toMediaCollection($locale . '_tnc_pdf');
                }
            }
        }

        if ($tree_tnc_files) {
            foreach ($tree_tnc_files as $locale => $tree_tnc_file) {
                if ($tree_tnc_file) {
                    $master->clearMediaCollection($locale . '_tree_pdf');
                    $master->addMedia($tree_tnc_file)->toMediaCollection($locale . '_tree_pdf');
                }
            }
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_tnc_message"),
            'type' => 'success',
        ]);
    }
}
