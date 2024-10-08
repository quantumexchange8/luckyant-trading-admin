<?php

namespace App\Http\Controllers;

use App\Http\Requests\BalanceAdjustmentRequest;
use App\Models\CopyTradeTransaction;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\Mt5DeleteLog;
use App\Services\dealAction;
use App\Services\RunningNumberService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Models\TradingAccount;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TradingAccountExport;
use App\Services\SelectOptionService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ChangeTradingAccountPassowrdNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\MetaFiveService;
use App\Services\passwordType;

class TradingController extends Controller
{
    //
    public function liveTrading()
    {
        return Inertia::render('Member/LiveTrading', [
            'leverageSel' => (new SelectOptionService())->getActiveLeverageSelection(),
        ]);
    }

    public function getTradingAccount(Request $request)
    {
        $authUser = \Auth::user();
        // $connection = (new MetaFiveService())->getConnectionStatus();

        // if ($connection == 0) {
        //     try {
        //         (new MetaFiveService())->getUserInfo(TradingAccount::all());
        //     } catch (\Exception $e) {
        //         \Log::error('Error fetching trading accounts: '. $e->getMessage());
        //     }
        // }

        $tradingListing = TradingAccount::query()
        ->select([
            'trading_accounts.*',
            DB::raw('ABS(IFNULL(demo_fund, 0) - balance) AS real_fund')
        ])
        ->with(['user', 'accountType', 'tradingUser']);

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $tradingListing->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $tradingListing->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $tradingListing->whereIn('user_id', []);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $tradingListing->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('username', 'like', $search);
                })
                ->orWhereHas('tradingUser', function ($tradingUser) use ($search) {
                    $tradingUser->where('name', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
        }

        if ($request->filled('type')) {
            $filter = $request->input('type');
            if ($filter === 'inactive'){
                $tradingListing->whereRaw('ABS(IFNULL(demo_fund, 0) - balance) = 0');
            }
            elseif ($filter === 'deleted'){
                $tradingListing->where(function ($q) use ($filter) {
                    $q->whereHas('tradingUser', function ($tradingUser) use ($filter) {
                        $tradingUser->where('acc_status', 'Deleted');
                    });
                });
            }
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $tradingListing->whereBetween('created_at', [$start_date, $end_date]);
        }
           
        if ($request->has('exportStatus')) {
            return Excel::download(new TradingAccountExport($tradingListing), Carbon::now() . '_Trading Account' .'_History-report.xlsx');
        }

        $results = $tradingListing->latest()->paginate(10);

        return response()->json($results);
    }

    public function edit_leverage(Request $request)
    {
        $leverage = TradingAccount::find($request->id);

        $leverage->update([
            'margin_leverage' => $request->margin_leverage,
        ]);


        return back()->with('toast', trans('public.created_trading_account'));
    }

    public function change_password(Request $request)
    {
        $password = TradingAccount::find($request->id);

        $rules = [
            'meta_login' => ['required'],
            'master_password' => ['sometimes', 'string', 'regex:/^(?=.*[A-Z])(?=.*\d).+$/'],
            'investor_password' => ['sometimes', 'string'],
        ];

        $attributes = [
            'meta_login' => trans('public.meta_login'),
            'master_password' => trans('public.master_password'),
            'investor_password' => trans('public.investor_password'),
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {

            $user = User::find($request->user_id);
            $meta_login = $request->meta_login;
            $master_password = $request->master_password;
            $investor_password = $request->investor_password;
            $metaService = new MetaFiveService();
            $connection = $metaService->getConnectionStatus();

            if ($connection != 0) {
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }

            if ($master_password || $investor_password) {
                if ($master_password) {
                    $metaService->changePassword($meta_login, 0, $master_password);
                }

                if ($investor_password) {
                    $metaService->changePassword($meta_login, 1, $investor_password);
                }

                Notification::route('mail', $user->email)
                    ->notify(new ChangeTradingAccountPassowrdNotification($user, $meta_login, $master_password, $investor_password));

                return back()->with('toast', trans('public.updated_trading_account'));
            }

            return back()->with('toast', trans('public.error_updating_account'));
        }

    }

    public function balanceAdjustment(BalanceAdjustmentRequest $request)
    {
        $user = User::find($request->user_id);
        $meta_login = $request->meta_login;
        $amount = $request->amount;
        $transaction_type = $request->transaction_type;
        $fund_type = $request->fund_type;

        $connection = (new MetaFiveService())->getConnectionStatus();
        $metaService = new MetaFiveService();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $tradingAccount = TradingAccount::with('subscriber')->where('meta_login', $meta_login)->first();

        try {
            $metaService->getUserInfo(collect([$tradingAccount]));
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        if ($transaction_type == 'BalanceOut') {
            // Check if balance is sufficient
            if (!empty($tradingAccount->subscriber) &&
                !empty($tradingAccount->unsubscribe_date) &&
                $tradingAccount->subscriber->unsubscribe_date->greaterThan(Carbon::now()->subHours(24))
            ) {
                throw ValidationException::withMessages(['amount' => trans('public.terminatiion_message')]);
            }

            // Check if balance is sufficient
            if ($fund_type == 'DemoFund') {
                if ($tradingAccount->demo_fund < $amount || $amount <= 0) {
                    throw ValidationException::withMessages(['amount' => 'Insufficient Demo Fund']);
                }
            } else {
                if ($tradingAccount->balance - $tradingAccount->demo_fund < $amount || $amount <= 0) {
                    throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
                }
            }
        }

        $deal = [];

        try {
            $deal = (new MetaFiveService())->createDeal($meta_login, $amount, $request->description, $transaction_type == 'BalanceIn' ? dealAction::DEPOSIT : dealAction::WITHDRAW);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        Transaction::create([
            'category' => 'trading_account',
            'user_id' => $user->id,
            'to_meta_login' => $meta_login,
            'ticket' => $deal['deal_Id'],
            'transaction_number' => RunningNumberService::getID('transaction'),
            'transaction_type' => $request->transaction_type,
            'fund_type' => $fund_type,
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'Success',
            'comment' => $deal['conduct_Deal']['comment'],
        ]);

        if ($request->fund_type == 'DemoFund') {
            $tradingUser = TradingUser::where('meta_login', $meta_login)->first();
            $tradingAccount = TradingAccount::where('meta_login', $meta_login)->first();

            if ($transaction_type == 'BalanceIn') {
                $tradingUser->demo_fund += $amount;
                $tradingAccount->demo_fund += $amount;
            } else {
                $tradingUser->demo_fund -= $amount;
                $tradingAccount->demo_fund -= $amount;
            }
            $tradingUser->save();
            $tradingAccount->save();
        }

        $subscriber = Subscriber::with(['master:id,meta_login', 'tradingAccount'])
            ->where('user_id', $user->id)
            ->where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Subscribing'])
            ->first();

        if ($subscriber && $subscriber->status == 'Pending') {
            $subscriber->initial_meta_balance += $amount;
            $subscriber->save();
        } elseif ($subscriber && $subscriber->status == 'Subscribing') {
            $subscriber->subscribe_amount += $amount;
            $subscriber->save();

            $subscription = Subscription::with(['master:id,meta_login', 'tradingAccount'])
                ->where('user_id', $user->id)
                ->where('meta_login', $meta_login)
                ->where('master_id', $subscriber->master_id)
                ->where('status', 'Active')
                ->first();

            if ($subscription) {
                $subscription->meta_balance += $amount;
                $subscription->save();

                CopyTradeTransaction::create([
                    'user_id' => $user->id,
                    'trading_account_id' => $tradingAccount->id,
                    'meta_login' => $tradingAccount->meta_login,
                    'subscription_id' => $subscription->id,
                    'master_id' => $subscription->master->id,
                    'master_meta_login' => $subscription->master->meta_login,
                    'amount' => $tradingAccount->balance,
                    'real_fund' => abs($tradingAccount->demo_fund - $tradingAccount->balance),
                    'demo_fund' => $tradingAccount->demo_fund,
                    'type' => 'Deposit',
                    'status' => 'Success',
                ]);

                SubscriptionBatch::create([
                    'user_id' => $user->id,
                    'trading_account_id' => $subscriber->trading_account_id,
                    'meta_login' => $meta_login,
                    'meta_balance' => $amount,
                    'real_fund' => $fund_type == 'RealFund' ? $amount : 0,
                    'demo_fund' => $fund_type == 'DemoFund' ? $amount : 0,
                    'master_id' => $subscriber->master_id,
                    'master_meta_login' => $subscriber->master_meta_login,
                    'type' => 'CopyTrade',
                    'subscriber_id' => $subscriber->id,
                    'subscription_id' => $subscription->id,
                    'subscription_number' => $subscription->subscription_number,
                    'subscription_period' => $subscriber->roi_period,
                    'transaction_id' => $subscriber->transaction_id,
                    'subscription_fee' => $subscriber->initial_subscription_fee,
                    'settlement_start_date' => now(),
                    'settlement_date' => now()->addDays($subscriber->roi_period)->endOfDay(),
                    'status' => 'Active',
                    'approval_date' => now() < $subscription->approval_date ? now()->addDay() : now(),
                ]);
            }
        }

        return redirect()->back()
            ->with('title', 'Success adjustment')
            ->with('success', 'Successfully ' . $request->transaction_type . ' to LOGIN: ' . $meta_login);
    }

    public function deleteAccount(Request $request)
    {
        $tradingAcc = TradingAccount::find($request->id);
        $tradingUser = TradingUser::find($request->trade_user);

        $request->validate([
            'remarks' => ['required'],
        ]);

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }
        else{
            $metaService->deleteAccount($request->meta_login);
            $tradingUser->update([
                'remarks' => $request->remarks . ' - by ID : ' . \Auth::user()->id,
                'acc_status' => 'Deleted',
            ]);

            Mt5DeleteLog::create([
                'user_id' => $tradingAcc->user_id,
                'trading_account_id' => $request->id,
                'meta_login' => $request->meta_login,
                'type' => 'manual',
                'account_created_at' => $tradingAcc->created_at,
                'account_balance' => abs($tradingAcc->demo_fund ?? 0 - $tradingAcc->balance),
                'remarks' => $request->remarks,
                'handle_by' => \Auth::user()->id,
            ]);

        }
        
        return redirect()->back()
            ->with('title', 'Success delete')
            ->with('success', 'Successfully deleted LOGIN: ' . $request->meta_login);
    }
}
