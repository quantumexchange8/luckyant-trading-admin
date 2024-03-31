<?php

namespace App\Http\Controllers;

use App\Exports\MemberListingExport;
use App\Http\Requests\KycApprovalRequest;
use App\Http\Requests\WalletAdjustmentRequest;
use App\Models\Country;
use App\Models\SettingRank;
use App\Models\TradingAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\PaymentAccount;
use App\Models\Subscription;
use App\Notifications\KycApprovalNotification;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Http\Requests\EditMemberRequest;
use App\Http\Requests\PaymentAccountRequest;
use App\Http\Requests\AddMemberRequest;
use App\Services\SelectOptionService;
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

class MemberController extends Controller
{
    public function index()
    {
        $rankLists = SettingRank::all()->map(function ($rank) {
            return [
                'value' => $rank->id,
                'label' => $rank->name,
            ];
        });

        $kycCounts = User::where('role', 'member')
            ->whereIn('kyc_approval', ['Pending', 'Verified', 'Unverified'])
            ->selectRaw('kyc_approval, count(*) as count')
            ->groupBy('kyc_approval')
            ->pluck('count', 'kyc_approval')
            ->toArray();

        return Inertia::render('Member/MemberListing', [
            'rankLists' => $rankLists,
            'kycCounts' => $kycCounts,
            'countries' => (new SelectOptionService())->getCountries(),
            'nationalities' => (new SelectOptionService())->getNationalities(),
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
            'password' => Hash::make($request->password),
            'identification_number' => $request->identity_number,
            'role' => 'member',
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

    public function getMemberDetails(Request $request)
    {
        $members = User::query()
            ->where('role', '=', 'member')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                $query->where(function ($innerQuery) use ($type) {
                    $innerQuery->where('kyc_approval', $type);
                });
            })
            ->when($request->filled('rank'), function ($query) use ($request) {
                $rank_id = $request->input('rank');
                $query->where(function ($innerQuery) use ($rank_id) {
                    $innerQuery->where('setting_rank_id', $rank_id);
                });
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('sortType'), function($query) use ($request) {
                $sortType = $request->input('sortType');
                $sort = $request->input('sort');

                $query->orderBy($sortType, $sort);
            })
            // ->select('id', 'name', 'email', 'setting_rank_id', 'kyc_approval', 'country','created_at', 'hierarchyList', 'identification_number', 'gender', )
            ->with(['rank:id,name', 'country:id,name', 'tradingAccounts', 'tradingUser', 'top_leader'])
            ->latest();

        if ($request->has('exportStatus')) {
            return Excel::download(new MemberListingExport($members), Carbon::now() . '-report.xlsx');
        }

        return response()->json($members->get());

        $members = $members->paginate(10);

        $members->each(function ($user) {
            $user->profile_photo_url = $user->getFirstMediaUrl('profile_photo');
            $user->front_identity = $user->getFirstMediaUrl('front_identity');
            $user->back_identity = $user->getFirstMediaUrl('back_identity');
            $user->kyc_upload_date = $user->getMedia('back_identity')->first()->created_at ?? null;
            $user->walletBalance = $user->wallets->sum('balance');
            $user->userName = $user->top_leader->name ?? null;
        });

        return response()->json($members);
    }

    public function validateKyc(EditMemberRequest $request)
    {
        $this->updateMember($request);
    }

    public function verifyMember(KycApprovalRequest $request)
    {
        $user = User::find($request->id);
        $approvalType = $request->type;

        $title = '';
        $message = '';

        if ($approvalType == 'approve') {
            $user->update([
                'kyc_approval' => 'Verified',
                'kyc_approval_description' => 'Approved by admin',
            ]);

            $title = 'Member verified!';
            $message = 'The member has been verified successfully.';

        } elseif ($approvalType == 'reject') {
            $user->update([
                'kyc_approval' => 'Unverified',
                'kyc_approval_description' => $request->remark,
            ]);

            $title = 'Member unverified!';
            $message = 'An email has been sent to the member to request updated KYC information.';

        }

        Notification::route('mail', $user->email)
            ->notify(new KycApprovalNotification($user));

        return redirect()->back()->with('title', $title)->with('toast', $message);
    }

    public function viewMemberDetails($id)
    {
        $user = User::with(['media', 'upline:id,name,email'])->find($id);

        $settingRanks = SettingRank::all();

        $formattedRanks = $settingRanks->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
            ];
        });

        $countries = Country::all();
        $formattedCountries = $countries->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
            ];
        });

        $formattedNationalities = $countries->map(function ($country) {
            return [
                'value' => $country->nationality,
                'label' => $country->nationality,
            ];
        });

        $paymentAccounts = PaymentAccount::where('user_id', $id)->get();

        $wallets = Wallet::where('user_id', $user->id)->get();

        $user->profile_photo_url = $user->getFirstMediaUrl('profile_photo');

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

        return redirect()->back()->with('title', 'Member updated!')->with('toast', 'The member has been updated successfully.');
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

        return redirect()->back()->with('title', 'Payment Account!')->with('toast', 'The payment account has been updated successfully.');
    }

    public function advanceEditMember(Request $request)
    {

        $user = User::find($request->user_id);

        $currentRank = $request->rank;

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        if ($currentRank != $user->setting_rank_id) {
            $user->rank_up_status = 'manual';
            $user->save();
        }

        $user->update([
            'setting_rank_id' => $request->rank,
        ]);

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

                }

                $this->transferUpline($user, $upline_id);
            }
        }

        if ($user->leader_status != $request->leader_status) {
            $user->update([
                'leader_status' => $request->leader_status,
            ]);
        }

        return redirect()->back()->with('title', 'Member updated!')->with('toast', 'The member has been updated successfully.');
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

        if ($wallet->type == 'cash_wallet') {
            $transactionData['amount'] = $amount;
            $transactionData['transaction_amount'] = $amount;
            $transactionData['transaction_type'] = $transaction_type;
            $transactionData['fund_type'] = $request->fund_type;

            if ($transaction_type == 'Deposit') {
                $transactionData['to_wallet_id'] = $wallet->id;
                $transactionData['new_wallet_amount'] = $wallet->balance + $amount;
            } else {
                $transactionData['from_wallet_id'] = $wallet->id;
                $transactionData['new_wallet_amount'] = $wallet->balance - $amount;
            }
        } else {
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
            $transactionData['transaction_type'] = 'WalletAdjustment';
        }

        if ($transactionData['new_wallet_amount'] < 0) {
            throw ValidationException::withMessages(['amount' => 'Insufficient balance']);
        }

        $transaction = Transaction::create($transactionData);

        $wallet->update([
            'balance' => $transaction->new_wallet_amount
        ]);

        return redirect()->back()->with('title', 'Wallet Adjusted!')->with('success', 'This wallet has been adjusted successfully.');
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
        $users = User::query()
            ->where('role', '=', 'member')
            ->whereNot('id', $request->id)
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
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

        if ($searchTerm) {
            $searchUser = User::where('name', 'like', '%' . $searchTerm . '%')
                ->orWhere('email', 'like', '%' . $searchTerm . '%')
                ->first();

            if (!$searchUser) {
                return response()->json(['error' => 'User not found for the given search term.'], 404);
            }
        }

        $user = $searchUser ?? User::find($id);

        $users = User::whereHas('upline', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->get();

        $level = 0;
        $rootNode = [
            'name' => $user->username,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level,
            'direct_affiliate' => count($user->children),
            'total_affiliate' => count($user->getChildrenIds()),
           'self_deposit' => $this->getSelfDeposit($user),
           'total_group_deposit' => $this->getTotalGroupDeposit($user),
        //    'valid_affiliate_deposit' => $this->getValidAffiliateDeposit($user),
            'children' => $users->map(function ($user) {
                return $this->mapUser($user, 0);
            })
        ];

        return response()->json($rootNode);
    }

    protected function getSelfDeposit($user)
    {
        return Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'Active')
            ->sum('meta_balance');
    }

    protected function getTotalGroupDeposit($user)
    {
        $ids = $user->getChildrenIds();

        return Subscription::query()
            ->whereIn('user_id', $ids)
            ->where('status', 'Active')
            ->sum('meta_balance');
    }

    protected function mapUser($user, $level) {
        $children = $user->children;

        $mappedChildren = $children->map(function ($child) use ($level) {
            return $this->mapUser($child, $level + 1);
        });

        $mappedUser = [
            'name' => $user->username,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level + 1,
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

    public function impersonate(User $user)
    {
        $dataToHash = $user->name . $user->email . $user->id;
        $hashedToken = md5($dataToHash);

        $domain = $_SERVER['HTTP_HOST'];

        if ($domain === 'secure-admin.luckyantfxasia.com') {
            $url = "https://member.luckyantfxasia.com/admin_login/{$hashedToken}";
        } elseif ($domain === 'testadmin.luckyantfxasia.com') {
            $url = "https://testmember.luckyantfxasia.com/admin_login/{$hashedToken}";
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
            ->where('role', 'member')
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
}
