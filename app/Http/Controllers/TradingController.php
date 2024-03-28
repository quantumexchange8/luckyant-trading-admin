<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
                    $metaService->changePassword($meta_login, passwordType::MAIN, $master_password);
                }
    
                if ($investor_password) {
                    $metaService->changePassword($meta_login, passwordType::INVESTOR, $investor_password);
                }

                Notification::route('mail', $user->email)
                    ->notify(new ChangeTradingAccountPassowrdNotification($user, $meta_login, $master_password, $investor_password));
            
                return back()->with('toast', trans('public.updated_trading_account'));
            }
            
            return back()->with('toast', trans('public.error_updating_account'));
        }
    
        return back()->with('toast', trans('public.error_updating_account'));
        
    }
}
