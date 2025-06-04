<?php

namespace App\Http\Controllers;

use App\Exports\PammSubscriptionExport;
use App\Models\Master;
use App\Models\PammSubscription;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\SubscriptionConfirmationNotification;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class PammController extends Controller
{
    public function pending_pamm()
    {
        return Inertia::render('Pamm/PendingPamm/PendingPamm', [
            'pendingSubscriptionsCount' => PammSubscription::where('status', 'Pending')->count(),
        ]);
    }

    public function getPendingPammData(Request $request, UserService $userService)
    {
        $pendingQuery = PammSubscription::query()
            ->with([
                'user:id,name,email,hierarchyList',
                'master:id,meta_login,type',
                'master.tradingUser:id,meta_login,name,company,account_type',
                'master.tradingUser.from_account_type',
            ])
            ->where('status', 'Pending');

        $authUser = Auth::user();

        $join_start_date = $request->query('joinStartDate');
        $join_end_date = $request->query('joinEndDate');

        if ($join_start_date && $join_end_date) {
            $start_date = Carbon::createFromFormat('Y-m-d', $join_start_date)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $join_end_date)->endOfDay();

            $pendingQuery->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $pendingQuery->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $pendingQuery->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $pendingQuery->whereIn('user_id', []);
        }

        if ($request->export == 'yes') {
            if ($request->master_meta_login) {
                $pendingQuery->where('master_meta_login', $request->master_meta_login);
            }

            if ($request->first_leader_id) {
                $first_leader = User::find($request->first_leader_id);
                $childrenIds = $first_leader->getChildrenIds();
                $pendingQuery->whereIn('user_id', $childrenIds);
            }

            return Excel::download(new PammSubscriptionExport($pendingQuery), Carbon::now() . '-pending-pamm-report.xlsx');
        }

        $subscribers = $pendingQuery->select([
            'id',
            'user_id',
            'meta_login',
            'subscription_amount',
            'master_id',
            'master_meta_login',
            'created_at',
        ])
            ->orderByDesc('created_at')
            ->get();

        // Extract all hierarchy IDs from users' hierarchyLists
        $userHierarchyLists = $subscribers->pluck('user.hierarchyList')
            ->filter()
            ->flatMap(fn($list) => explode('-', trim($list, '-')))
            ->unique()
            ->toArray();

        // Load all potential leaders in bulk
        $leaders = User::whereIn('id', $userHierarchyLists)
            ->where('leader_status', 1) // Only load users with leader_status == 1
            ->get()
            ->keyBy('id');

        // Attach the first leader details
        $subscribers->each(function ($subscribersQuery) use ($userService, $leaders) {
            $firstLeader = $userService->getFirstLeader($subscribersQuery->user?->hierarchyList, $leaders);

            $subscribersQuery->first_leader_id = $firstLeader?->id;
            $subscribersQuery->first_leader_name = $firstLeader?->name;
            $subscribersQuery->first_leader_email = $firstLeader?->email;
        });

        return response()->json([
            'pendingSubscribers' => $subscribers
        ]);
    }

    public function pammSubscriptionApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'remarks' => ['required_if:action,reject'],
        ])->setAttributeNames([
            'remarks' => trans('public.remarks'),
        ]);
        $validator->validate();

        $pamm_subscription = PammSubscription::find($request->subscription_id);

        $pamm_subscription->update([
            'status' => $request->action == 'approve' ? 'Active' : 'Rejected',
            'approval_date' => now(),
            'remarks' => $request->remarks,
            'handle_by' => Auth::id(),
        ]);

        if ($pamm_subscription->status == 'Active') {
            if ($pamm_subscription->type != 'StandardGroup') {
                // balance half from trading account
                $client_deal = [];

                try {
                    $client_deal = (new MetaFiveService())->createDeal($pamm_subscription->meta_login, $pamm_subscription->subscription_amount, '#' . $pamm_subscription->meta_login, dealAction::WITHDRAW);
                } catch (\Exception $e) {
                    \Log::error('Error fetching trading accounts: '. $e->getMessage());
                }

                Transaction::create([
                    'category' => 'trading_account',
                    'user_id' => $pamm_subscription->master->user_id,
                    'from_meta_login' => $pamm_subscription->meta_login,
                    'ticket' => $client_deal['deal_Id'],
                    'transaction_number' => RunningNumberService::getID('transaction'),
                    'transaction_type' => 'PurchaseProduct',
                    'fund_type' => 'RealFund',
                    'amount' => $pamm_subscription->subscription_amount,
                    'transaction_charges' => 0,
                    'transaction_amount' => $pamm_subscription->subscription_amount,
                    'status' => 'Success',
                    'comment' => $client_deal['conduct_Deal']['comment'],
                ]);
            }

            // fund to master
            $description = $pamm_subscription->meta_login ? 'Login #' . $pamm_subscription->meta_login : ('Client #' . $pamm_subscription->user_id);
            $deal = [];

            try {
                $deal = (new MetaFiveService())->createDeal($pamm_subscription->master_meta_login, $pamm_subscription->subscription_amount, $description, dealAction::DEPOSIT);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }

            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $pamm_subscription->master->user_id,
                'to_meta_login' => $pamm_subscription->master_meta_login,
                'ticket' => $deal['deal_Id'],
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'DepositCapital',
                'fund_type' => 'RealFund',
                'amount' => $pamm_subscription->subscription_amount,
                'transaction_charges' => 0,
                'transaction_amount' => $pamm_subscription->subscription_amount,
                'status' => 'Success',
                'comment' => $deal['conduct_Deal']['comment'],
            ]);

            $masterAccount = Master::find($pamm_subscription->master_id);
            $masterAccount->total_fund += $pamm_subscription->subscription_amount;
            $masterAccount->save();
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_{$request->action}_subscription_message"),
            'type' => 'success',
        ]);
    }

    public function pamm_listing()
    {
        return Inertia::render('Pamm/PammListing/PammListing', [
            'subscriptionsCount' => PammSubscription::whereNot('status', 'Pending')->count(),
        ]);
    }

    public function getPammListingData(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = PammSubscription::with([
                'user:id,name,email,hierarchyList',
                'master:id,meta_login',
                'master.tradingUser:id,meta_login,name,company,account_type',
                'master.tradingUser.from_account_type',
            ]);

            $authUser = Auth::user();

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        });
                    })
                        ->orWhere('meta_login', 'like', '%' . $keyword . '%')
                        ->orWhere('subscription_number', 'like', '%' . $keyword . '%');
                });
            }

            if (!empty($data['filters']['start_approval_date']['value']) && !empty($data['filters']['end_approval_date']['value'])) {
                $start_approval_date = Carbon::parse($data['filters']['start_approval_date']['value'])->addDay()->startOfDay();
                $end_approval_date = Carbon::parse($data['filters']['end_approval_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('approval_date', [$start_approval_date, $end_approval_date]);
            }

            if (!empty($data['filters']['start_terminate_date']['value']) && !empty($data['filters']['end_terminate_date']['value'])) {
                $start_terminate_date = Carbon::parse($data['filters']['start_terminate_date']['value'])->addDay()->startOfDay();
                $end_terminate_date = Carbon::parse($data['filters']['end_terminate_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('termination_date', [$start_terminate_date, $end_terminate_date]);
            }

            if (!empty($data['filters']['start_expired_date']['value']) && !empty($data['filters']['end_expired_date']['value'])) {
                $start_expired_date = Carbon::parse($data['filters']['start_expired_date']['value'])->addDay()->startOfDay();
                $end_expired_date = Carbon::parse($data['filters']['end_expired_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('expired_date', [$start_expired_date, $end_expired_date]);
            }

            if ($data['filters']['fund_type']['value']) {
                switch ($data['filters']['fund_type']['value']) {
                    case 'demo_fund':
                        $query->where('demo_fund', '>', 0);
                        break;

                    case 'real_fund':
                        $query->where('real_fund', '>', 0);
                        break;
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

            if ($data['filters']['master_meta_login']['value']) {
                $query->where('master_meta_login', $data['filters']['master_meta_login']['value']['meta_login']);
            }

            if ($data['filters']['status']['value']) {
                $query->where('status', $data['filters']['status']['value']);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('approval_date');
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

            if ($request->exportStatus) {
                return Excel::download(new PammSubscriptionExport($query), Carbon::now() . '-pamm-report.xlsx');
            }

            $subscriptions = $query->paginate($data['rows']);

            // Extract all hierarchy IDs from users' hierarchyLists
            $userHierarchyLists = $subscriptions->pluck('user.hierarchyList')
                ->filter()
                ->flatMap(fn($list) => explode('-', trim($list, '-')))
                ->unique()
                ->toArray();

            // Load all potential leaders in bulk
            $leaders = User::whereIn('id', $userHierarchyLists)
                ->where('leader_status', 1) // Only load users with leader_status == 1
                ->get()
                ->keyBy('id');

            // Attach the first leader details
            $subscriptions->each(function ($subscriptionQuery) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($subscriptionQuery->user?->hierarchyList, $leaders);

                $subscriptionQuery->first_leader_id = $firstLeader?->id;
                $subscriptionQuery->first_leader_name = $firstLeader?->name;
                $subscriptionQuery->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $subscriptions,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function getPammSubscriptionOverview()
    {
        $authUser = Auth::user();

        $subscriptionQuery = PammSubscription::where('status', 'Active');

        // Apply filtering based on roles and leader status
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

        // Current month and last month
        $endOfMonth = \Illuminate\Support\Carbon::now()->endOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Current month active subscribers
        $current_month_active_subscriber = (clone $subscriptionQuery)
            ->whereDate('approval_date', '<=', $endOfMonth)
            ->distinct('meta_login')
            ->count();

        // Current month active fund
        $current_month_active_fund = (clone $subscriptionQuery)
            ->whereDate('approval_date', '<=', $endOfMonth)
            ->sum('subscription_amount');

        // Last month active subscribers
        $last_month_active_subscriber = (clone $subscriptionQuery)
            ->whereDate('approval_date', '<=', $endOfLastMonth)
            ->distinct('meta_login')
            ->count();

        // Last month active fund
        $last_month_active_fund = (clone $subscriptionQuery)
            ->whereDate('approval_date', '<=', $endOfLastMonth)
            ->sum('subscription_amount');

        // Comparison % of active subscribers vs last month
        $last_month_active_subscriber_comparison = $current_month_active_subscriber - $last_month_active_subscriber;

        // Comparison % of active fund vs last month
        $last_month_active_fund_comparison = $last_month_active_fund > 0
            ? (($current_month_active_fund - $last_month_active_fund) / $last_month_active_fund) * 100
            : ($current_month_active_fund > 0 ? 100 : 0);

        // Get and format top 3 users by total deposit
        $topThreeUser = (clone $subscriptionQuery)
            ->select('user_id', DB::raw('SUM(subscription_amount) as total_fund'))
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
}
