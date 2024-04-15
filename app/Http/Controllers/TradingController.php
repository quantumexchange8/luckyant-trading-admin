<?php

namespace App\Http\Controllers;

use App\Http\Requests\BalanceAdjustmentRequest;
use App\Models\CopyTradeTransaction;
use App\Models\Subscription;
use App\Models\TradingUser;
use App\Models\Transaction;
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
        $connection = (new MetaFiveService())->getConnectionStatus();

        if ($connection == 0) {
            try {
                (new MetaFiveService())->getUserInfo(TradingAccount::all());
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }

        $tradingListing = TradingAccount::query()
            ->with(['user', 'accountType', 'tradingUser']);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $tradingListing->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                })
                ->orWhereHas('tradingUser', function ($tradingUser) use ($search) {
                    $tradingUser->where('name', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
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

        $results->each(function ($trading_account) {
            $trading_account->real_fund = abs($trading_account->demo_fund - $trading_account->balance);
        });

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

        $subscriptions = Subscription::with('master:id,meta_login')
            ->where('user_id', $user->id)
            ->where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Active'])
            ->get();

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

        if ($transaction_type == 'Withdrawal') {
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
            $deal = (new MetaFiveService())->createDeal($meta_login, $amount, $request->description, $transaction_type == 'Deposit' ? dealAction::DEPOSIT : dealAction::WITHDRAW);
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

            if ($transaction_type == 'Deposit') {
                $tradingUser->demo_fund += $amount;
                $tradingAccount->demo_fund += $amount;
            } else {
                $tradingUser->demo_fund -= $amount;
                $tradingAccount->demo_fund -= $amount;
            }
            $tradingUser->save();
            $tradingAccount->save();
        }

        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $subscription->meta_balance += $amount;
                $subscription->save();
                if ($subscription->status == 'Active') {
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
                }
            }
        }

        return redirect()->back()
            ->with('title', 'Success adjustment')
            ->with('success', 'Successfully ' . $request->transaction_type . ' to LOGIN: ' . $meta_login);
    }
}
