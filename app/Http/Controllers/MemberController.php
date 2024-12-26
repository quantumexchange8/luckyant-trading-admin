<?php

namespace App\Http\Controllers;

use App\Exports\AffiliateSummaryExport;
use App\Exports\MemberListingExport;
use App\Http\Requests\KycApprovalRequest;
use App\Http\Requests\WalletAdjustmentRequest;
use App\Models\AccountTypeToLeader;
use App\Models\Country;
use App\Models\Master;
use App\Models\MasterToLeader;
use App\Models\PammSubscription;
use App\Models\PaymentGatewayToLeader;
use App\Models\RankingLog;
use App\Models\SettingRank;
use App\Models\TradingAccount;
use App\Models\User;
use App\Models\UserExtraBonus;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\PaymentAccount;
use App\Models\Subscription;
use App\Models\WalletLog;
use App\Notifications\KycApprovalNotification;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Http\Requests\EditMemberRequest;
use App\Http\Requests\PaymentAccountRequest;
use App\Http\Requests\AddMemberRequest;
use App\Services\SelectOptionService;
use App\Services\UserService;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function index()
    {
        $authUser = Auth::user();

        $kycQuery = User::whereNotIn('role', ['super-admin', 'admin'])
            ->whereIn('kyc_approval', ['Pending', 'Verified', 'Unverified']);

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $kycQuery->whereIn('id', $authUser->getChildrenIds());
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $kycQuery->whereIn('id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $kycQuery->whereIn('id', []);
        }

        $kycCounts = $kycQuery->selectRaw('kyc_approval, count(*) as count')
            ->groupBy('kyc_approval')
            ->pluck('count', 'kyc_approval')
            ->toArray();

        return Inertia::render('Member/Listing/MemberListing', [
            'kycCounts' => $kycCounts,
        ]);
    }

    public function addMember(AddMemberRequest $request)
    {

        $upline_id = $request->upline_id['value'];
        $upline = User::find($upline_id);

        if(empty($upline->hierarchyList)) {
            $hierarchyList = "-" . $upline_id . "-";
        } else {
            $hierarchyList = $upline->hierarchyList . $upline_id . "-";
        }

        $topLead = User::find($upline_id);

        if ($topLead) {
            if ($topLead->top_leader_id == null) {

                $topLead = $topLead->id;

            } else {

                $topLead = $topLead->top_leader_id;

            }

        }

        $dialCode = Country::find($request->country);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'country' => $request->country,
            'dial_code' => '+' . $dialCode->phone_code,
            'phone' => '+' . $dialCode->phone_code . $request->phone,
            'upline_id' => $upline_id,
            'top_leader_id' => $topLead,
            'dob' => $request->dob,
            'hierarchyList' => $hierarchyList,
            'setting_rank_id' => $request->ranking,
            'display_rank_id' => $request->ranking,
            'password' => Hash::make($request->password),
            'identification_number' => $request->identity_number,
            'role' => 'user',
            'kyc_approval' => 'Unverified',
        ]);

        $user->setReferralId();

        Wallet::create([
            'user_id' => $user->id,
            'name' => 'Cash Wallet',
            'type' => 'cash_wallet',
            'wallet_address' => RunningNumberService::getID('cash_wallet'),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'name' => 'Bonus Wallet',
            'type' => 'bonus_wallet',
            'wallet_address' => RunningNumberService::getID('bonus_wallet'),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'name' => 'E-Wallet',
            'type' => 'e_wallet',
            'wallet_address' => RunningNumberService::getID('e_wallet'),
        ]);


        $user->setReferralId();

        return redirect()->back()->with('title', 'New member added!')->with('success', 'The new member has been added successfully.');
    }

    public function getMemberListingData(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = User::query()
                ->with([
                    'ofCountry',
                    'upline:id,name,email',
                    'rank:id,name',
                    'media'
                ])
                ->withSum('active_pamm', 'subscription_amount')
                ->withSum('active_copy_trade', 'meta_balance')
                ->withCount('children')
                ->whereNotIn('role', ['super-admin', 'admin']);

            if ($data['filters']['kyc_status']['value'] != 'All') {
                $query->where('kyc_approval', $data['filters']['kyc_status']['value']);
            }

            if ($data['filters']['global']['value']) {
                $query->where( function($q) use ($data) {
                    $keyword = $data['filters']['global']['value'];

                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
                });
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            // Filter by leaderId if provided
            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('leader_status', 1)
                    ->where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('id', $usersUnderLeader);
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if ($data['filters']['country']['value']) {
                $query->where('country', $data['filters']['country']['value']);
            }

            if ($data['filters']['rank']['value']) {
                $query->where('display_rank_id', $data['filters']['rank']['value']);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            $authUser = Auth::user();

            if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
                $childrenIds = $authUser->getChildrenIds();
                $childrenIds[] = $authUser->id;
                $query->whereIn('id', $childrenIds);
            } elseif ($authUser->hasRole('super-admin')) {
                // Super-admin logic, no need to apply whereIn
            } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
                $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
                $query->whereIn('id', $childrenIds);
            } else {
                // No applicable conditions, set whereIn to empty array
                $query->whereIn('id', []);
            }

            // Export logic
            if ($request->has('exportStatus') && $request->exportStatus) {
                return Excel::download(new MemberListingExport($query), now() . '-member-report.xlsx');
            }

            $users = $query->paginate($data['rows']);

            $userHierarchyLists = $users->pluck('hierarchyList')
                ->filter()
                ->flatMap(fn($list) => explode('-', trim($list, '-')))
                ->unique()
                ->toArray();

            // Load all potential leaders in bulk
            if ($leaderId > 0) {
                $leaderQuery = User::where('id', $leaderId)
                    ->where('leader_status', 1);
            } else {
                $leaderQuery = User::whereIn('id', $userHierarchyLists)
                    ->where('leader_status', 1);
            }

            $leaders = $leaderQuery->get()->keyBy('id');

            $users->each(function ($user) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($user->hierarchyList, $leaders);

                $user->first_leader_id = $firstLeader?->id;
                $user->first_leader_name = $firstLeader?->name;
                $user->first_leader_email = $firstLeader?->email;
                $user->total_clients = count($user->getChildrenIds());
            });

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function validateKyc(EditMemberRequest $request)
    {
        $this->updateMember($request);
    }

    public function verifyMember(Request $request)
    {
        Validator::make($request->all(), [
            'name' => ['required'],
            'username' => ['nullable'],
            'dob' => ['required'],
            'dial_code' => ['required'],
            'phone' => ['required'],
            'gender' => ['required'],
            'address' => ['required'],
            'country' => ['required'],
            'nationality' => ['required'],
            'identification_number' => ['required'],
            'action' => ['required'],
            'remarks' => ['required_if:action,reject'],
        ])->setAttributeNames([
            'name' => trans('public.name'),
            'username' => trans('public.username'),
            'dob' => trans('public.dob'),
            'dial_code' => trans('public.phone'),
            'phone' => trans('public.phone'),
            'gender' => trans('public.gender'),
            'address' => trans('public.address'),
            'country' => trans('public.country'),
            'nationality' => trans('public.nationality'),
            'identification_number' => trans('public.identification_number'),
            'action' => trans('public.action'),
            'remarks' => trans('public.remarks'),
        ])->validate();

        $user = User::find($request->user_id);
        $approvalType = $request->action;

        $dial_code = $request->dial_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $dial_code = ltrim($dial_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $dial_code)) {
            // Concatenate dial code and phone number
            $phone_number = '+' . $dial_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone_number = '+' . $phone;
        }

        $users = User::where('dial_code', $dial_code)
            ->whereNot('id', $user->id)
            ->where('status', 'Active')
            ->get();

        foreach ($users as $user_phone) {
            if ($user_phone->phone == $phone_number) {
                throw ValidationException::withMessages(['phone' => trans('public.invalid_mobile_phone')]);
            }
        }

        $dobInput = $request->input('dob'); // Replace with actual input retrieval

        // Check and process the DOB format
        if (str_contains($dobInput, 'T')) {
            // If the format includes a time component, parse it and add a day
            $dob = Carbon::parse($dobInput)->addDay()->toDateString();
        } else {
            // If the format is already 'Y-m-d', no need to modify
            $dob = Carbon::parse($dobInput)->toDateString();
        }

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'dob' => $dob,
            'country' => $request->country,
            'dial_code' => $request->dial_code,
            'phone' => $phone_number,
            'nationality' => $request->nationality,
            'gender' => $request->gender,
            'address_1' => $request->address,
            'identification_number' => $request->identification_number,
        ]);

        $title = '';
        $message = '';

        if ($approvalType == 'approve') {
            $user->update([
                'kyc_approval' => 'Verified',
                'kyc_approval_date' => now(),
                'kyc_approval_description' => 'Approved by admin',
            ]);

            $message = 'toast_success_approve_kyc_message';

            Notification::route('mail', $user->email)
                ->notify(new KycApprovalNotification($user));

        } elseif ($approvalType == 'reject') {
            $user->update([
                'kyc_approval' => 'Unverified',
                'kyc_approval_date' => now(),
                'kyc_approval_description' => $request->remarks,
            ]);

            $message = 'toast_success_reject_kyc_message';
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.$message"),
            'type' => 'success',
        ]);
    }

    public function viewMemberDetails($id)
    {
        $user = User::with(['media', 'upline:id,name,email'])->find($id);

        $settingRanks = SettingRank::all();

        $formattedRanks = $settingRanks->map(function ($rank) {
            return [
                'value' => $rank->id,
                'label' => $rank->name,
            ];
        });

        $countries = Country::all();
        $formattedCountries = $countries->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
            ];
        });

        $formattedNationalities = $countries->map(function ($nationality) {
            return [
                'value' => $nationality->nationality,
                'label' => $nationality->nationality,
            ];
        });

        $paymentAccounts = PaymentAccount::where('user_id', $id)->get();

        $wallets = Wallet::where('user_id', $user->id)->get();

        $user->profile_photo_url = $user->getFirstMediaUrl('profile_photo');
        $user->front_identity = $user->getMedia('front_identity');
        $user->back_identity = $user->getMedia('back_identity');

        $formattedCurrencies = Country::whereIn('id', [132, 233, 102, 101, 45, 240])->get()->map(function ($country) {
            return [
                'value' => $country->currency,
                'label' => $country->currency_name . ' (' . $country->currency . ')',
            ];
        });

        return Inertia::render('Member/MemberDetails/MemberDetail', [
            'member_detail' => $user,
            'ranks' => $formattedRanks,
            'countries' => $formattedCountries,
            'nationalities' => $formattedNationalities,
            'wallets' => $wallets,
            'tradingAccounts' => User::find($id)->tradingAccounts,
            'paymentAccounts' => $paymentAccounts,
            'currencies' => $formattedCurrencies,
        ]);
    }

    public function editMember(EditMemberRequest $request)
    {
        $user = User::find($request->user_id);

        $this->updateMember($request);

        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMedia($request->profile_photo)->toMediaCollection('profile_photo');
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_member_message"),
            'type' => 'success',
        ]);
    }

    public function paymentAccount(PaymentAccountRequest $request)
    {

        $paymentAccount = PaymentAccount::find($request->id);

        $PaymentAccount = $paymentAccount->update([
            'payment_account_name' => $request->payment_account_name,
            'payment_platform_name' => $request->payment_platform_name,
            'account_no' => $request->account_no,
            'bank_swift_code' => $request->bank_swift_code,
            'currency' => $request->currency,
            'status' => $request->status,
        ]);

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_payment_account_message"),
            'type' => 'success',
        ]);
    }

    public function advanceEditMember(Request $request)
    {
        $user = User::find($request->user_id);

        $displayRank = $request->display_rank;
        $settingRank = $request->setting_rank;

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        if ($displayRank != $user->display_rank_id) {
            $user->display_rank_id = $displayRank;
            $user->rank_up_status = 'manual';
            $user->save();
        }

        if ($settingRank != $user->setting_rank_id) {
            $previous_rank_id = $user->setting_rank_id;

            $user->setting_rank_id = $settingRank;
            $user->rank_up_status = 'manual';
            $user->save();

            $rank = SettingRank::find($settingRank);

            RankingLog::create([
                'user_id' => $user->id,
                'old_rank' => $previous_rank_id,
                'new_rank' => $settingRank,
                'user_package_amount' => 0,
                'target_package_amount' => $rank->package_requirement,
                'user_direct_referral_amount' => 0,
                'target_direct_referral_amount' => $rank->direct_referral,
                'user_group_sales' => 0,
                'target_group_sales' => $rank->group_sales,
            ]);
        }

        if ($request->upline_id != null) {
            $upline_id = $request->upline_id['value'];

            if($user->upline_id != $upline_id)
            {
                $topLead = User::find($upline_id);

                if ($topLead) {
                    if ($topLead->top_leader_id == null) {
                        $user->update([
                            'top_leader_id' => $topLead->id
                        ]);
                    } else {
                        $user->update([
                            'top_leader_id' => $topLead->top_leader_id
                        ]);
                    }

                    $user->update([
                        'is_public' => $topLead->is_public
                    ]);

                    User::whereIn('id', $user->getChildrenIds())->update([
                        'is_public' => $user->is_public
                    ]);
                }

                $this->transferUpline($user, $upline_id);
            } else {
                if ($user->is_public != $request->is_public) {
                    $user->update([
                        'is_public' => $request->is_public,
                    ]);

                    $childrenIds = $user->getChildrenIds();

                    User::whereIn('id', $childrenIds)->update([
                        'is_public' => $user->is_public
                    ]);
                }
            }
        } else {
            if ($user->is_public != $request->is_public) {
                $user->update([
                    'is_public' => $request->is_public,
                ]);

                $childrenIds = $user->getChildrenIds();

                User::whereIn('id', $childrenIds)->update([
                    'is_public' => $user->is_public
                ]);
            }
        }

        if ($user->leader_status != $request->leader_status) {
            $user->update([
                'leader_status' => $request->leader_status,
            ]);
        }

        if ($user->role != $request->role) {
            $allRolesInDatabase = Role::all()->pluck('name');

            if (!$allRolesInDatabase->contains('special_demo')) {
                Role::create(['name' => 'special_demo']);
            }

            if ($request->role == 'special_demo') {
                $user->assignRole('special_demo');
            }

//            $allPermissionsInDatabase = Permission::all()->pluck('name');
//
//            if (!$allPermissionsInDatabase->contains('wallet_withdrawal')) {
//                Permission::create(['name' => 'wallet_withdrawal']);
//            }
//
//            if ($request->role == 'special_demo') {
//                $user->assignRole('special_demo');
//                $user->givePermissionTo('wallet_withdrawal');
//            } else {
//                $user->revokePermissionTo('wallet_withdrawal');
//            }

            $user->role = $request->role;
            $user->save();
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_member_message"),
            'type' => 'success',
        ]);
    }

    public function wallet_adjustment(WalletAdjustmentRequest $request)
    {
        $amount = $request->amount;
        $wallet = Wallet::find($request->wallet_id);
        $transaction_type = $request->transaction_type;

        $transactionData = [
            'user_id' => $request->user_id,
            'category' => 'wallet',
            'transaction_charges' => 0,
            'remarks' => $request->description,
            'handle_by' => Auth::id(),
            'status' => 'Success',
            'transaction_number' => RunningNumberService::getID('transaction')
        ];

        if ($transaction_type == 'Deposit') {
            $transactionData['amount'] = $amount;
            $transactionData['transaction_amount'] = $amount;
            $transactionData['new_wallet_amount'] = $wallet->balance + $amount;
        } else {
            $transactionData['amount'] = -$amount;
            $transactionData['transaction_amount'] = -$amount;
            $transactionData['new_wallet_amount'] = $wallet->balance - $amount;
        }

        $transactionData['from_wallet_id'] = $wallet->id;
        $transactionData['transaction_type'] = $request->type;

        if ($transactionData['new_wallet_amount'] < 0) {
            throw ValidationException::withMessages(['amount' => 'Insufficient balance']);
        }

        $transaction = Transaction::create($transactionData);

        $wallet->update([
            'balance' => $transaction->new_wallet_amount
        ]);

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_wallet_message"),
            'type' => 'success',
        ]);
    }

    protected function transferUpline($user, $upline_id)
    {
        if ($user->id == $upline_id) {
            throw ValidationException::withMessages(['upline_id' => 'Upline cannot be themselves']);
        }

        if ($upline_id == $user->upline_id) {
            throw ValidationException::withMessages(['upline_id' => 'Upline cannot be the same']);
        }

        $new_parent = User::find($upline_id);

        if ($user->upline_id != $new_parent->id) {

            if (str_contains($new_parent->hierarchyList, $user->id)) {
                $new_parent->hierarchyList = $user->hierarchyList;
                $new_parent->upline_id = $user->upline_id;
                $new_parent->save();
            }

            if (empty($new_parent->hierarchyList)) {
                $user_hierarchy = "-" . $new_parent->id . "-";
            } else {
                $user_hierarchy = $new_parent->hierarchyList . $new_parent->id . "-";
            }

            $this->updateHierarchyList($user, $user_hierarchy, '-' . $user->id . '-');

            $user->hierarchyList = $user_hierarchy;
            $user->upline_id = $new_parent->id;
            $user->save();

            // Update hierarchyList for users with same upline_id
            $sameUplineIdUsers = User::where('upline_id', $new_parent->id)->get();
            if ($sameUplineIdUsers) {
                foreach ($sameUplineIdUsers as $sameUplineUser) {
                    $new_user_hierarchy = $new_parent->hierarchyList ? $new_parent->hierarchyList . $new_parent->id . "-" : "-" . $new_parent->id . "-";
                    $this->updateHierarchyList($sameUplineUser, $new_user_hierarchy, '-' . $sameUplineUser->id . '-');
                    $sameUplineUser->hierarchyList = $new_user_hierarchy;
                    $sameUplineUser->upline_id = $new_parent->id;
                    $sameUplineUser->save();
                }
            }
        }
    }

    private function updateHierarchyList($user, $list, $id)
    {
        $children = $user->children;
        if (count($children)) {
            foreach ($children as $child) {
                //$child->hierarchyList = substr($list, -1) . substr($child->hierarchyList, strpos($child->hierarchyList, $id) + strlen($id));
                $child->hierarchyList = substr($list, 0, -1) . $id;
                $child->save();
                $this->updateHierarchyList($child, $list, $id . $child->id . '-');
            }
        }
    }

    public function getAllUsers(Request $request)
    {
        $authUser = Auth::user();

        $users = User::query()
            ->whereNotIn('role', ['super-admin', 'admin'])
            ->whereNot('id', $request->id)
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $users->whereIn('id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $users->whereIn('id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $users->whereIn('id', []);
        }

        $users = $users
            ->select('id', 'name', 'email')
            ->get();

        $users->each(function ($users) {
            $users->profile_photo = $users->getFirstMediaUrl('profile_photo');
        });

        return response()->json($users);
    }

    public function getAllLeaders(Request $request)
    {
        $authUser = Auth::user();

        $users = User::query()
            ->where('leader_status', 1)
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $users->whereIn('id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $users->whereIn('id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $users->whereIn('id', []);
        }

        $users = $users
            ->select('id', 'name', 'email')
            ->get();

        $users->each(function ($users) {
            $users->profile_photo = $users->getFirstMediaUrl('profile_photo');
        });

        return response()->json($users);
    }

    public function refreshTradingAccountsData()
    {
        $user = User::find(\request()->id);
        $connection = (new MetaFiveService())->getConnectionStatus();

        if ($connection == 0) {
            try {
                (new MetaFiveService())->getUserInfo($user->tradingAccounts);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }

        return TradingAccount::with('accountType:id,group_id,name')->where('user_id', $user->id)->latest()->get();
    }

    public function affiliate_tree($id)
    {
        $user = User::find($id);

        return Inertia::render('Member/MemberAffiliates/MemberAffiliate', [
            'user' => $user
        ]);
    }

    public function getTreeData(Request $request, $id)
    {
        $searchUser = null;
        $searchTerm = $request->input('search');
        $locale = app()->getLocale();
        $authUser = Auth::user();

        if ($searchTerm) {
            $searchUserQuery = User::query();

            if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
                $childrenIds = $authUser->getChildrenIds();
                $childrenIds[] = $authUser->id;
                $searchUserQuery->whereIn('id', $childrenIds);
            } elseif ($authUser->hasRole('super-admin')) {
                // Super-admin can search all users
            } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
                $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
                $searchUserQuery->whereIn('id', $childrenIds);
            } else {
                $users->whereIn('id', []);
            }

            $searchUser = $searchUserQuery
                ->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('email', 'like', '%' . $searchTerm . '%');
                })
                ->first();

            if (!$searchUser) {
                return response()->json(['error' => 'User not found for the given search term.'], 404);
            }
        }

        $user = $searchUser ?? User::find($id);

        $users = User::whereHas('upline', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->get();

        $rank = SettingRank::where('id', $user->display_rank_id)->first();

        // Parse the JSON data in the name column to get translations
        $translations = json_decode($rank->name, true);

        $level = 0;
        $rootNode = [
            'name' => $user->name,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level,
            'rank' => $translations[$locale] ?? $rank->name,
            'direct_affiliate' => count($user->children),
            'total_affiliate' => count($user->getChildrenIds()),
            'self_deposit' => $this->getSelfDeposit($user),
            'total_group_deposit' => $this->getTotalGroupDeposit($user),
            //'valid_affiliate_deposit' => $this->getValidAffiliateDeposit($user),
            'children' => $users->map(function ($user) {
                return $this->mapUser($user, 0);
            })
        ];

        return response()->json($rootNode);
    }

    protected function getSelfDeposit($user)
    {
        $subscriptions = Subscription::where('user_id', $user->id)
            ->where('status', 'Active')
            ->sum('meta_balance');

        $pamm = PammSubscription::where('user_id', $user->id)
            ->where('status', 'Active')
            ->sum('subscription_amount');

        return $subscriptions + $pamm;
    }

    protected function getTotalGroupDeposit($user)
    {
        $ids = $user->getChildrenIds();

        $subscriptions = Subscription::whereIn('user_id', $ids)
            ->where('status', 'Active')
            ->sum('meta_balance');

        $pamm = PammSubscription::whereIn('user_id', $ids)
            ->where('status', 'Active')
            ->sum('subscription_amount');

        return $subscriptions + $pamm;
    }

    protected function mapUser($user, $level) {
        $children = $user->children;

        $mappedChildren = $children->map(function ($child) use ($level) {
            return $this->mapUser($child, $level + 1);
        });

        $locale = app()->getLocale();

        $rank = SettingRank::where('id', $user->display_rank_id)->first();

        // Parse the JSON data in the name column to get translations
        $translations = json_decode($rank->name, true);

        $mappedUser = [
            'name' => $user->name,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level + 1,
            'rank' => $translations[$locale] ?? $rank->name,
            'total_affiliate' => count($user->getChildrenIds()),
            'self_deposit' => $this->getSelfDeposit($user),
            'total_group_deposit' => $this->getTotalGroupDeposit($user),
        //    'valid_affiliate_deposit' => $this->getValidAffiliateDeposit($user),
        ];

        // Add 'children' only if there are children
        if (!$mappedChildren->isEmpty()) {
            $mappedUser['children'] = $mappedChildren;
        }

        return $mappedUser;
    }

    // protected function getValidAffiliateDeposit($user)
    // {
    //     $ids = $user->getChildrenIds();

    //     return InvestmentSubscription::query()
    //         ->whereIn('user_id', $ids)
    //         ->whereDate('expired_date', '>', now())
    //         ->sum('amount');
    // }

    public function impersonate($user_id)
    {
        $user = User::find($user_id);
        $dataToHash = $user->name . $user->email . $user->id;
        $hashedToken = md5($dataToHash);

        $currentHost = $_SERVER['HTTP_HOST'];

        // Retrieve the app URL and parse its host
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST);
        $memberProductionUrl = config('app.member_production_url');

        if ($currentHost === 'testadmin.luckyantfxasia.com') {
            $url = "https://testmember.luckyantfxasia.com/admin_login/{$hashedToken}";
        } elseif ($currentHost === $appUrl) {
            $url = "$memberProductionUrl/admin_login/$hashedToken";
        } else {
            return back();
        }

        return Inertia::location($url);
    }

    protected function updateMember($request)
    {
        $user = User::find($request->user_id);
        $dial_code = $request->dial_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $dial_code = ltrim($dial_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $dial_code)) {
            // Concatenate dial code and phone number
            $phone_number = '+' . $dial_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone_number = '+' . $phone;
        }

        $users = User::where('dial_code', $dial_code)
            ->whereNot('id', $user->id)
            ->where('status', 'Active')
            ->get();

        foreach ($users as $user_phone) {
            if ($user_phone->phone == $phone_number) {
                throw ValidationException::withMessages(['phone' => trans('public.invalid_mobile_phone')]);
            }
        }

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'dob' => $request->dob,
            'country' => $request->country,
            'dial_code' => $request->dial_code,
            'phone' => $phone_number,
            'nationality' => $request->nationality,
            'gender' => $request->gender,
            'address_1' => $request->address,
            'identification_number' => $request->identification_number,
        ]);

        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMedia($request->profile_photo)->toMediaCollection('profile_photo');
        }

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }
    }

    protected function affiliate_listing(Request $request)
    {
        return Inertia::render('Member/AffiliateListing');
    }

    protected function calculateWalletTransactions($user, $walletType)
    {
        $wallet = $user->wallets->where('type', $walletType)->first();

        $walletIn = $user->walletLogs->where('category', 'bonus')->where('wallet_type', $walletType)->sum('amount') +
            $user->transactions->where('category', 'wallet')->where('to_wallet_id', optional($wallet)->id)->sum('transaction_amount');

        $walletOut = $user->transactions->where('category', 'wallet')->where('from_wallet_id', optional($wallet)->id)->sum('transaction_amount');

        return [
            'in' => $walletIn,
            'out' => $walletOut,
        ];
    }

    protected function getAffiliateSummaries(Request $request)
    {
        $leader = User::with(['rank:id,name', 'wallets', 'walletLogs', 'transactions'])->find($request->firstLeader);
        $leader->profile_photo_url = $leader->getFirstMediaUrl('profile_photo');
        $leader->profit = $leader->walletLogs->where('category', 'profit')->sum('amount');

        $bonusWalletTransactions = $this->calculateWalletTransactions($leader, 'bonus_wallet');
        $eWalletTransactions = $this->calculateWalletTransactions($leader, 'e_wallet');

        $leader->bonus_in = $bonusWalletTransactions['in'];
        $leader->bonus_out = $bonusWalletTransactions['out'];

        $leader->e_wallet_in = $eWalletTransactions['in'];
        $leader->e_wallet_out = $eWalletTransactions['out'];

        $leader->total_funding = $leader->transactions->where('category', 'wallet')->where('transaction_type', 'Deposit')->where('status', 'Success')->sum('transaction_amount');
        $leader->total_withdrawal = $leader->transactions->where('category', 'wallet')->where('transaction_type', 'Withdrawal')->where('status', 'Success')->sum('transaction_amount');
        $leader->total_demo_fund = $leader->transactions->where('category', 'trading_account')->where('transaction_type', 'Deposit')->where('fund_type', 'DemoFund')->where('status', 'Success')->sum('transaction_amount');

        $children = User::whereIn('id', $leader->getChildrenIds())
            ->select('id', 'name', 'email', 'leader_status', 'top_leader_id', 'hierarchyList', 'upline_id')
            ->with('wallets', 'walletLogs', 'transactions');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $children->where(function ($query) use ($search) {
                $query->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $children->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->has('exportStatus')) {
            return Excel::download(new AffiliateSummaryExport($children), Carbon::now() . '-summary-report.xlsx');
        }

        $results = $children->latest()->get();

        $results->each(function ($child) {
            $child->profile_photo_url = $child->getFirstMediaUrl('profile_photo');
            $child->profit = $child->walletLogs->where('category', 'profit')->sum('amount');

            $bonusWalletTransactions = $this->calculateWalletTransactions($child, 'bonus_wallet');
            $eWalletTransactions = $this->calculateWalletTransactions($child, 'e_wallet');

            $child->bonus_in = $bonusWalletTransactions['in'];
            $child->bonus_out = $bonusWalletTransactions['out'];

            $child->e_wallet_in = $eWalletTransactions['in'];
            $child->e_wallet_out = $eWalletTransactions['out'];

            $child->total_funding = $child->transactions->where('category', 'wallet')->where('transaction_type', 'Deposit')->where('status', 'Success')->sum('transaction_amount');
            $child->total_withdrawal = $child->transactions->where('category', 'wallet')->where('transaction_type', 'Withdrawal')->where('status', 'Success')->sum('transaction_amount');
            $child->total_demo_fund = $child->transactions->where('category', 'trading_account')->where('transaction_type', 'Deposit')->where('fund_type', 'DemoFund')->where('status', 'Success')->sum('transaction_amount');
        });

        return response()->json([
            'children' => $results,
            'first_leader' => $leader,
        ]);
    }

    public function getExtraBonus(Request $request)
    {
        $extraBonuses = UserExtraBonus::with('account_type')
            ->where('user_id', $request->user_id)
            ->get();

        return response()->json($extraBonuses);
    }

    public function updateExtraBonus(Request $request)
    {
        Validator::make($request->all(), [
            'account_type_id' => ['required'],
            'extra_bonus' => ['nullable'],
        ])->setAttributeNames([
            'account_type_id' => trans('public.account_type'),
            'extra_bonus' => trans('public.extra_bonus'),
        ])->validate();

        $extra_bonus = $request->extra_bonus;

        if ($extra_bonus > 0) {
            $user_extra_bonus = UserExtraBonus::where('user_id', $request->user_id)
                ->where('account_type_id', $request->account_type_id)
                ->first();

            if ($user_extra_bonus) {
                $user_extra_bonus->extra_bonus = $extra_bonus;
                $user_extra_bonus->save();
            } else {
                UserExtraBonus::create([
                    'user_id' => $request->user_id,
                    'account_type_id' => $request->account_type_id,
                    'extra_bonus' => $extra_bonus,
                ]);
            }
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_extra_bonus_message"),
            'type' => 'success',
        ]);
    }

    public function updateLeaderStatus(Request $request)
    {
        $user = User::find($request->user_id);
        $action = $request->action;
        $leader = $user->getFirstLeader();

        if ($action == 'promote_member') {
            $user->update([
                'leader_status' => 1,
            ]);

            if ($leader) {
                // Payment Gateway
                $existing_payment_gateways = PaymentGatewayToLeader::where('user_id', $leader->id)->get();

                foreach ($existing_payment_gateways as $existing_payment_gateway) {
                    PaymentGatewayToLeader::create([
                        'payment_gateway_id' => $existing_payment_gateway->payment_gateway_id,
                        'user_id' => $user->id,
                    ]);
                }

                // Account Type
                $existing_account_types = AccountTypeToLeader::where('user_id', $leader->id)->get();

                foreach ($existing_account_types as $existing_account_type) {
                    AccountTypeToLeader::create([
                        'account_type_id' => $existing_account_type->account_type_id,
                        'user_id' => $user->id,
                    ]);
                }

                // Master
                $existing_masters = MasterToLeader::where('user_id', $leader->id)->get();

                foreach ($existing_masters as $existing_master) {
                    MasterToLeader::create([
                        'master_id' => $existing_master->master_id,
                        'user_id' => $user->id,
                    ]);
                }
            } else {
                AccountTypeToLeader::create([
                    'account_type_id' => 1,
                    'user_id' => $user->id,
                ]);

                PaymentGatewayToLeader::create([
                    'payment_gateway_id' => 1,
                    'user_id' => $user->id,
                ]);

                if ($user->is_public) {
                    $public_masters = Master::where([
                        'strategy_type' => 'HOFI',
                        'is_public' => 1,
                        'status' => 'Active',
                    ])
                        ->get();

                    foreach ($public_masters as $master) {
                        MasterToLeader::create([
                            'master_id' => $master->id,
                            'user_id' => $user->id,
                        ]);
                    }
                }
            }

        } elseif ($action == 'demote_leader') {
            $user->update([
                'leader_status' => 0,
            ]);

            // Payment Gateway
            $existing_payment_gateways = PaymentGatewayToLeader::where('user_id', $user->id)->get();

            foreach ($existing_payment_gateways as $existing_payment_gateway) {
                $existing_payment_gateway->delete();
            }

            // Account Type
            $existing_account_types = AccountTypeToLeader::where('user_id', $user->id)->get();

            foreach ($existing_account_types as $existing_account_type) {
                $existing_account_type->delete();
            }

            // Master
            $existing_masters = MasterToLeader::where('user_id', $user->id)->get();

            foreach ($existing_masters as $existing_master) {
                $existing_master->delete();
            }
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_${action}_message"),
            'type' => 'success',
        ]);
    }
}
