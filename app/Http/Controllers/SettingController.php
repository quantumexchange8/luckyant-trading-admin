<?php

namespace App\Http\Controllers;

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
        $countries = Country::all();
        $formattedCountries = $countries->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
                'currency' => $country->currency,
            ];
        });

        $paymentDetails = SettingPaymentMethod::where('status', 'Active')->first();
        if($paymentDetails == 'Banks') {
            $paymentBanks = $paymentDetails;
        } else {
            $paymentCrypto = $paymentDetails;
        }

        $paymentHistories = SettingPaymentMethod::with(['user:id,name'])->latest()->get();
        
        return Inertia::render('Setting/Payment/SettingPayment', [
            'countries' => $formattedCountries,
            'paymentDetails' => $paymentDetails,
            'paymentHistories' => $paymentHistories,
        ]);
    }

    public function updatePaymentSetting(PaymentSettingRequest $request)
    {
        
        if ($request->payment_method == 'Bank') {
            $request->validate([
                'payment_account_name' => 'required|string|max:255',
                'payment_platform_name' => 'required|string|max:255',
                'account_no' => 'required',
                'bank_swift_code' => 'required',
            ]);
        } else {
            $request->validate([
                'payment_account_name' => 'required|string|max:255',
                'payment_platform_name' => 'required|string|max:255',
                'account_no' => 'required',
            ]);
        }
        
        $countryname = Country::find($request->country);

        $updateOldSetting = SettingPaymentMethod::latest()->first();
        if ($updateOldSetting) {
            $updateOldSetting = $updateOldSetting->update([
                'status' => 'Inactive'
            ]);
        }
        
        
        $paymentSetting = SettingPaymentMethod::create([
            'payment_method' => $request->payment_method,
            'payment_account_name' => $request->payment_account_name,
            'payment_platform_name' => $request->payment_platform_name,
            'account_no' => $request->account_no,
            'country' => $countryname->name,
            'currency' => $request->currency,
            'bank_swift_code' => $request->bank_swift_code,
            'status' => 'Active',
            'handle_by' => Auth::user()->id,
        ]);

        return redirect()->back()->with('title', 'Updated successfully')->with('success', 'The payment configuaration has been updated successfully.');
    }
    public function getPaymentHistory(Request $request)
    {
        
        $history = SettingPaymentMethod::query()->with(['user:id,name,email']);

        if ($request->filled('search'))
        {
            $search = '%' . $request->input('search') . '%';
            $history->where(function ($q) use ($search) {
                $q->where('account_no', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $history->whereBetween('created_at', [$start_date, $end_date]);
        }
        
        $results = $history->latest()->paginate(10);

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
}
