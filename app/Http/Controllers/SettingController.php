<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeLeverage;
use App\Models\AccountTypeToLeader;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayToLeader;
use App\Models\SettingPaymentToLeader;
use Auth;
use Carbon\Carbon;
use App\Models\Term;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Country;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\SettingLeverage;
use App\Http\Requests\TermsRequest;
use App\Models\SettingPaymentMethod;
use App\Models\CurrencyConversionRate;
use App\Models\User;
use App\Http\Requests\LeveragesRequest;
use Spatie\Activitylog\Models\Activity;
use App\Http\Requests\PaymentSettingRequest;

class SettingController extends Controller
{
    public function paymentSetting()
    {
        return Inertia::render('Setting/Payment/SettingPayment');
    }

    public function getSettingPaymentMethods()
    {
        $paymentMethods = SettingPaymentMethod::withSum('successTransactions', 'amount')
            ->with('visibleLeaders')
            ->latest()
            ->get();

        return response()->json([
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function addPaymentSetting(PaymentSettingRequest $request)
    {
        $paymentSetting = SettingPaymentMethod::create([
            'payment_method' => $request->payment_method,
            'payment_account_name' => $request->payment_account_name,
            'payment_platform_name' => $request->payment_platform_name,
            'account_no' => $request->account_no,
            'country' => $request->country,
            'currency' => $request->country ? $request->currency : 'USD',
            'bank_swift_code' => $request->bank_swift_code,
            'bank_code' => $request->bank_code,
            'status' => 'Active',
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->network) {
            $paymentSetting->update([
                'crypto_network' => implode(',', array_column($request->network, 'name')),
            ]);
        }

        $leaders = $request->leaders;

        if ($leaders) {
            foreach ($leaders as $leader) {
                SettingPaymentToLeader::create([
                    'setting_payment_method_id' => $paymentSetting->id,
                    'user_id' => $leader['id'],
                ]);
            }
        }

        if ($request->hasFile('payment_logo')) {
            $paymentSetting->addMedia($request->payment_logo)->toMediaCollection('payment_logo');
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_create_payment_method_success'),
            'type' => 'success',
        ]);
    }

    public function updatePaymentSetting(PaymentSettingRequest $request)
    {
        $payment = SettingPaymentMethod::find($request->id);

        $payment->update([
            'payment_account_name' => $request->payment_account_name,
            'payment_platform_name' => $request->payment_platform_name,
            'account_no' => $request->account_no,
            'country' => $request->country,
            'currency' => $request->country ? $request->currency : 'USD',
            'bank_swift_code' => $request->bank_swift_code,
            'bank_code' => $request->bank_code,
            'status' => $request->status ? 'Active' : 'Inactive',
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->network) {
            $payment->update([
                'crypto_network' => implode(',', array_column($request->network, 'name')),
            ]);
        }

        $leaders = $request->leaders;

        if ($leaders) {
            $existing_leaders = SettingPaymentToLeader::where('setting_payment_method_id', $payment->id)->get();

            foreach ($existing_leaders as $existing_leader) {
                $existing_leader->delete();
            }

            foreach ($leaders as $leader) {
                SettingPaymentToLeader::create([
                    'setting_payment_method_id' => $payment->id,
                    'user_id' => $leader['id'],
                ]);
            }
        }

        if ($request->hasFile('payment_logo')) {
            $payment->clearMediaCollection('payment_logo');
            $payment->addMedia($request->payment_logo)->toMediaCollection('payment_logo');
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_update_payment_method_success'),
            'type' => 'success',
        ]);
    }

    public function deletePayment(Request $request)
    {
        $payment = SettingPaymentMethod::find($request->id);
        $payment->delete();

        $payment->visibleLeaders()->delete();

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_delete_payment_method_success'),
            'type' => 'success',
        ]);
    }

    public function getPaymentHistory(Request $request)
    {
        $history = SettingPaymentMethod::query()
            ->with(['user:id,name,email']);

        if ($request->filled('search'))
        {
            $search = '%' . $request->input('search') . '%';
            $history->where(function ($q) use ($search) {
                $q->where('payment_account_name', 'like', $search)
                    ->orWhere('account_no', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $history->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('filter')) {
            $filter = $request->input('filter') ;
            $history->where(function ($q) use ($filter) {
                $q->where('payment_method', $filter);
            });
        }

        $results = $history->latest()->paginate(10);

        $results->each(function ($payment) {
            $payment->bank_logo_url = $payment->getFirstMediaUrl('payment_logo');
        });

        return response()->json($results);
    }

    public function getPaymentActivity(Request $request)
    {

        $historyActivity = Activity::with('causer');

        $result = $historyActivity->latest()->paginate(10);

        return response()->json($result);
    }

    public function masterSetting()
    {
        $settings = Setting::get();

        $withdrawal = Setting::where('slug', 'withdrawal-fee')->first();

        return Inertia::render('Setting/Master/MasterSetting', [
            'settings' => $settings,
            'withdrawal' => $withdrawal,
        ]);
    }

    public function updateMasterSetting(Request $request)
    {

        $setting = Setting::find($request->id);

        $updateSetting = $setting->update([
            'value' => $request->value,
        ]);

        return redirect()->back()->with('title', 'Updated successfully')->with('success', 'The master setting configuaration has been updated successfully.');
    }

    public function getCryptoNetworks(Request $request)
    {
        $networks = CurrencyConversionRate::query()
            ->where('base_currency', 'USDT')
            ->whereNot('id', $request->id)
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('crypto_network', 'like', "%{$search}%");
                });
            })
            ->select('id', 'base_currency', 'crypto_network')
            ->get();

        return response()->json($networks);
    }

    public function tncSetting(Request $request)
    {
        return Inertia::render('Setting/Tnc/TncSetting');
    }

    public function getTncSetting(Request $request)
    {

        $tnc = Term::query()
        ->with('user:id,name')
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->input('search');
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('title', 'like', '%' . $search . '%');
            });
        })
        ->when($request->filled('date'), function ($query) use ($request) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
            $query->whereBetween('created_at', [$start_date, $end_date]);
        })
        ->latest()
        ->paginate(10);

        return response()->json($tnc);
    }

    public function addTnCSetting(TermsRequest $request)
    {

        $term = Term::create([
            'type' => $request->type,
            'title' => $request->title,
            'contents' => $request->contents,
            'user_id' => \Auth::id(),
        ]);

        return redirect()->back()->with('title', 'Terms and Conditions created')->with('success', 'The Terms and Conditions has been created successfully.');

    }

    public function editTnCSetting(TermsRequest $request, $id)
    {

        $term = Term::findOrFail($id);

        $term->update([
            'type' => $request->type,
            'title' => $request->title,
            'contents' => $request->contents,
            'user_id'  => \Auth::id(),
        ]);

        return redirect()->back()->with('title', 'Terms and Conditions updated')->with('success', 'The Terms and Conditions has been updated successfully.');
    }

    public function leverageSetting()
    {
        return Inertia::render('Setting/Leverage/LeverageSetting');
    }
    public function getLeverageSetting(Request $request)
    {
        $leverageSettings = SettingLeverage::query()
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->input('search');
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('value', 'like', '%' . $search . '%');
            });
        })
        ->latest()
        ->paginate(10);

        return response()->json($leverageSettings);
    }

    public function addLeverageSetting(LeveragesRequest $request)
    {
        $leverageSetting = SettingLeverage::create([
            'display' => '1:' . $request->value,
            'value' => $request->value,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('title', 'Leverage created')->with('success', 'The Leverage has been created successfully.');

    }

    public function editLeverageSetting(LeveragesRequest $request, $id)
    {
        $leverageSetting = SettingLeverage::findOrFail($id);

        $leverageSetting->update([
            'display' => '1:' . $request->value,
            'value' => $request->value,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('title', 'Leverage updated')->with('success', 'The Leverage has been updated successfully.');
    }

    public function bankWithdrawalSetting()
    {
        return Inertia::render('Setting/BankWithdrawal/BankWithdrawalSetting');
    }

    public function getLeaders(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'name';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = User::where('leader_status', 1);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status') == 'Active' ? 1 : 0;
            $query->where(function ($q) use ($status) {
                $q->where('enable_bank_withdrawal', $status);
            });
        }

        $results = $query
            ->orderBy($column == null ? 'name' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json($results);
    }

    public function updateBankWithdrawalSetting(Request $request)
    {

        $leader = User::find($request->user_id);
        $leaderHierarchy = $leader->hierarchyList;

        $leader->update([
            'enable_bank_withdrawal' => $request->bank_status,
        ]);

        $members = User::where('hierarchyList', 'LIKE', '%' . $leaderHierarchy . $request->user_id . '%' )
                ->get();

        User::whereIn('id', $members->pluck('id'))
        ->update([
            'enable_bank_withdrawal' => $request->bank_status,
        ]);

        return redirect()->back()->with('title', 'Updated successfully')->with('success', 'The bank withdrawal option for the group has been updated successfully.');
    }

    public function payment_gateway()
    {
        return Inertia::render('Setting/PaymentGateway/PaymentGateway', [
            'payment_gateways_count' => PaymentGateway::count(),
        ]);
    }

    public function getLeadersSel()
    {
        $leaders = User::select([
            'id',
            'name',
            'email'
        ])
            ->where('leader_status', 1)
            ->get();

        return response()->json($leaders);
    }

    public function addPaymentGateway(Request $request)
    {
        Validator::make($request->all(), [
            'name' => ['required'],
            'platform' => ['required'],
            'payment_url' => ['required'],
            'payment_app_name' => ['required'],
            'secret_key' => ['required'],
            'secondary_key' => ['required'],
            'leaders' => ['required'],
        ])->setAttributeNames([
            'name' => 'Name',
            'platform' => 'Platform',
            'payment_url' => 'Payment URL',
            'payment_app_name' => 'Payment App',
            'secret_key' => 'Secret Key',
            'secondary_key' => 'Secondary Key',
            'leaders' => 'Visible To',
        ])->validate();

        $payment_gateway = PaymentGateway::create([
            'name' => $request->name,
            'platform' => $request->platform,
            'environment' => App::environment(),
            'payment_url' => $request->payment_url,
            'payment_app_name' => $request->payment_app_name,
            'secret_key' => $request->secret_key,
            'secondary_key' => $request->secondary_key,
            'edited_by' => \Auth::id(),
        ]);

        $leaders = $request->leaders;

        if ($leaders) {
            foreach ($leaders as $leader) {
                PaymentGatewayToLeader::create([
                    'payment_gateway_id' => $payment_gateway->id,
                    'user_id' => $leader['id'],
                ]);
            }
        }

        return redirect()->back()
            ->with('title', 'Added Successfully')
            ->with('success', 'A payment gateway has been added successfully.');
    }

    public function getPaymentGateways()
    {
        $payment_gateways = PaymentGateway::withSum('successTransactions', 'amount')
            ->with('visibleLeaders')
            ->latest()
            ->get();

        return response()->json([
            'paymentGateways' => $payment_gateways,
        ]);
    }

    public function updatePaymentGateway(Request $request)
    {
        Validator::make($request->all(), [
            'name' => ['required'],
            'platform' => ['required'],
            'payment_url' => ['required'],
            'payment_app_name' => ['required'],
            'secret_key' => ['required'],
            'secondary_key' => ['required'],
            'leaders' => ['required'],
        ])->setAttributeNames([
            'name' => 'Name',
            'platform' => 'Platform',
            'payment_url' => 'Payment URL',
            'payment_app_name' => 'Payment App',
            'secret_key' => 'Secret Key',
            'secondary_key' => 'Secondary Key',
            'leaders' => 'Visible To',
        ])->validate();

        $payment_gateway = PaymentGateway::find($request->payment_gateway_id);

        $payment_gateway->update([
            'name' => $request->name,
            'platform' => $request->platform,
            'environment' => App::environment(),
            'payment_url' => $request->payment_url,
            'payment_app_name' => $request->payment_app_name,
            'secret_key' => $request->secret_key,
            'secondary_key' => $request->secondary_key,
            'edited_by' => \Auth::id(),
        ]);

        $leaders = $request->leaders;

        if ($leaders) {
            $existing_leaders = PaymentGatewayToLeader::where('payment_gateway_id', $payment_gateway->id)->get();

            foreach ($existing_leaders as $existing_leader) {
                $existing_leader->delete();
            }

            foreach ($leaders as $leader) {
                PaymentGatewayToLeader::create([
                    'payment_gateway_id' => $payment_gateway->id,
                    'user_id' => $leader['id'],
                ]);
            }
        }

        return redirect()->back()
            ->with('title', 'Updated Successfully')
            ->with('success', 'A payment gateway has been updated successfully.');
    }

    public function deletePaymentGateway(Request $request)
    {
        $payment_gateway = PaymentGateway::find($request->payment_gateway_id);

        foreach ($payment_gateway->visibleLeaders as $visibleLeader) {
            $visibleLeader->delete();
        }

        $payment_gateway->delete();

        return redirect()->back()
            ->with('title', 'Deleted Successfully')
            ->with('success', 'A payment gateway has been deleted successfully.');
    }

    public function account_type()
    {
        return Inertia::render('Setting/AccountType/AccountType', [
            'accountTypesCount' => AccountType::count(),
        ]);
    }

    public function getAccountTypes()
    {
        $accountTypes = AccountType::with([
            'visibleLeaders',
            'leverages'
        ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'accountTypes' => $accountTypes,
        ]);
    }

    public function updateAccountType(Request $request)
    {
        Validator::make($request->all(), [
            'leverages' => ['required'],
            'leaders' => ['nullable'],
        ])->setAttributeNames([
            'leverages' => trans('public.leverage'),
            'leaders' => 'Visible To',
        ])->validate();

        $account_type = AccountType::find($request->account_type_id);

        if ($account_type->allow_trade != $request->allow_trade) {
            $account_type->update([
                'allow_trade' => $request->allow_trade,
            ]);
        }

        $leverages = $request->leverages;

        if ($leverages) {
            $existingLeverageIds = AccountTypeLeverage::where('account_type_id', $account_type->id)
                ->pluck('setting_leverage_id')
                ->toArray();

            $newLeverageIds = array_column($leverages, 'id');

            if (array_diff($existingLeverageIds, $newLeverageIds) || array_diff($newLeverageIds, $existingLeverageIds)) {
                // Delete old leverages
                AccountTypeLeverage::where('account_type_id', $account_type->id)->delete();

                // Create new leverages
                foreach ($leverages as $leverage) {
                    AccountTypeLeverage::create([
                        'account_type_id' => $account_type->id,
                        'setting_leverage_id' => $leverage['id'],
                    ]);
                }
            }
        }

        $leaders = $request->leaders;

        if ($leaders) {
            $existingLeaderIds = AccountTypeToLeader::where('account_type_id', $account_type->id)
                ->pluck('user_id')
                ->toArray();

            $newLeaderIds = array_column($leaders, 'id');

            if (array_diff($existingLeaderIds, $newLeaderIds) || array_diff($newLeaderIds, $existingLeaderIds)) {
                // Delete old leaders
                AccountTypeToLeader::where('account_type_id', $account_type->id)->delete();

                // Create new leaders
                foreach ($leaders as $leader) {
                    AccountTypeToLeader::create([
                        'account_type_id' => $account_type->id,
                        'user_id' => $leader['id'],
                    ]);
                }
            }
        } else {
            AccountTypeToLeader::where('account_type_id', $account_type->id)->delete();
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.toast_success_update_account_type_message'),
            'type' => 'success',
        ]);
    }
}
