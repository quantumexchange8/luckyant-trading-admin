<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Models\Term;
use Inertia\Inertia;
use App\Models\Country;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\SettingLeverage;
use App\Http\Requests\TermsRequest;
use App\Models\SettingPaymentMethod;
use App\Models\CurrencyConversionRate;
use App\Http\Requests\LeveragesRequest;
use Spatie\Activitylog\Models\Activity;
use App\Http\Requests\PaymentSettingRequest;

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
            'status' => $request->status,
            'handle_by' => Auth::user()->id,
        ]);

        if ($request->network) {
            $payment->update([
                'crypto_network' => implode(', ', $request->network),
            ]);
        }

        if ($request->hasFile('payment_logo')) {
            $payment->clearMediaCollection('payment_logo');
            $payment->addMedia($request->payment_logo)->toMediaCollection('payment_logo');
        }

        // Activity::create([
        //     'subject_id' => Auth::id(),
        //     'causer_type' => 'App\Models\SettingPaymentMethod',
        //     'causer_id' => auth()->id(),
        // ]);

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

}
