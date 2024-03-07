<?php

namespace App\Http\Controllers;

use App\Models\CurrencyConversionRate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Country;
use App\Models\SettingPaymentMethod;
use App\Models\Setting;
use App\Http\Requests\PaymentSettingRequest;
use Carbon\Carbon;
use Auth;

class SettingController extends Controller
{
    //

    public function paymentSetting()
    {
        $countries = Country::whereIn('id', [132, 45, 240, 219, 101, 102])->get();
        $formattedCountries = $countries->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
                'currency' => $country->currency,
            ];
        });

        $paymentDetails = SettingPaymentMethod::where('status', 'Active')->first();

        $paymentHistories = SettingPaymentMethod::with(['user:id,name'])->latest()->get();

        return Inertia::render('Setting/Payment/SettingPayment', [
            'countries' => $formattedCountries,
            'paymentDetails' => $paymentDetails,
            'paymentHistories' => $paymentHistories,
        ]);
    }

    public function addPaymentSetting(PaymentSettingRequest $request)
    {
        $country = Country::find($request->country);

        $paymentSetting = SettingPaymentMethod::create([
            'payment_method' => $request->payment_method,
            'payment_account_name' => $request->payment_account_name,
            'payment_platform_name' => $request->payment_platform_name,
            'account_no' => $request->account_no,
            'country' => $request->country,
            'currency' => $request->country ? $country->currency : 'USD',
            'bank_swift_code' => $request->bank_swift_code,
            'bank_code' => $request->bank_code,
            'status' => 'Active',
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->network) {
            $paymentSetting->update([
                'crypto_network' => implode(', ', $request->network),
            ]);
        }

        if ($request->hasFile('payment_logo')) {
            $paymentSetting->addMedia($request->payment_logo)->toMediaCollection('payment_logo');
        }

        return redirect()->back()
            ->with('title', 'Success Add Payment')
            ->with('success', 'The payment method has been added successfully.');
    }

    public function updatePaymentSetting(PaymentSettingRequest $request)
    {
        
        $payment = SettingPaymentMethod::find($request->id);

        $payment->update([
            'payment_account_name' => $request->payment_account_name,
            'payment_platform_name' => $request->payment_platform_name,
            'account_no' => $request->account_no,
            'country' => $request->country,
            'bank_swift_code' => $request->bank_swift_code,
            'bank_code' => $request->bank_code,
            'network' => $request->network,
            'status' => $request->status,
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->hasFile('payment_logo')) {
            $payment->clearMediaCollection('payment_logo');
            $payment->addMedia($request->payment_logo)->toMediaCollection('payment_logo');
        }

        return redirect()->back()
            ->with('title', 'Success Updated Payment')
            ->with('success', 'The payment has been updated successfully.');
    }

    public function deletePayment(Request $request)
    {
        $payment = SettingPaymentMethod::find($request->id);

        $payment->delete();

        return redirect()->back();
    }

    public function getPaymentHistory(Request $request, $status)
    {
        if ($status == 'Active') {
            $history = SettingPaymentMethod::query()
            ->with(['user:id,name,email'])
            ->where('status', [$status]);
        } else {
            $history = SettingPaymentMethod::query()
            ->with(['user:id,name,email']);
        }

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

        return redirect()->back()->with('title', 'Updated successfully')->with('success', 'The payment configuaration has been updated successfully.');
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
}
