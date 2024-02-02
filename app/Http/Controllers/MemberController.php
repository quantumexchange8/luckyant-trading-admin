<?php

namespace App\Http\Controllers;

use App\Http\Requests\KycApprovalRequest;
use App\Models\Country;
use App\Models\SettingRank;
use App\Models\TradingAccount;
use App\Models\User;
use App\Notifications\KycApprovalNotification;
use App\Services\MetaFiveService;
use App\Http\Requests\EditMemberRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

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
        ]);
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
            ->select('id', 'name', 'email', 'setting_rank_id', 'kyc_approval', 'country', 'cash_wallet','created_at')
            ->with(['rank:id,name', 'country:id,name'])
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // if ($request->has('exportStatus')) {
        //     if($request->type != null){
        //         return Excel::download(new MemberListingTypeExport($members), Carbon::now() . '-' . $request->type . '-report.xlsx');
        //     } else {
        //         return Excel::download(new MemberListingExport($members), Carbon::now() . '-' . '-report.xlsx');
        //     }
        // }

        $members->each(function ($user) {
            $user->profile_photo_url = $user->getFirstMediaUrl('profile_photo');
            $user->front_identity = $user->getFirstMediaUrl('front_identity');
            $user->back_identity = $user->getFirstMediaUrl('back_identity');
            $user->kyc_upload_date = $user->getMedia('back_identity')->first()->created_at ?? null;
        });

        return response()->json($members);
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

        return Inertia::render('Member/MemberDetails/MemberDetail', [
            'member_detail' => $user,
            'ranks' => $formattedRanks,
            'countries' => $formattedCountries,
//            'referralCount' => $referralCount,
        ]);
    }

    public function editMember(EditMemberRequest $request)
    {
        
        $user = User::find($request->user_id);
        
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'dob' => $request->dob,
            'country' => $request->country,
        ]);
        
        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->back()->with('title', 'Member updated!')->with('toast', 'The member has been updated successfully.');
    }

    public function advanceEditMember(Request $request)
    {

        $user = User::find($request->user_id);
        $upline_id = $request->upline_id['value'];

        $currentRank = $request->rank;

        if ($currentRank != $user->setting_rank_id) {
            $user->rank_up_status = 'manual';
            $user->save();
        }

        $user->update([
            'setting_rank_id' => $request->rank,
        ]);

        if($user->upline_id != $upline_id)
        {
            $this->transferUpline($user, $upline_id);
        }

        return redirect()->back()->with('title', 'Member updated!')->with('toast', 'The member has been updated successfully.');
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
                    $new_user_hierarchy = $new_parent->hierarchyList . $new_parent->id . "-";
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

        return TradingAccount::where('user_id', $user->id)->latest()->get();
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
            'name' => $user->name,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level,
            'direct_affiliate' => count($user->children),
            'total_affiliate' => count($user->getChildrenIds()),
//            'self_deposit' => $this->getSelfDeposit($user),
//            'valid_affiliate_deposit' => $this->getValidAffiliateDeposit($user),
            'children' => $users->map(function ($user) {
                return $this->mapUser($user, 0);
            })
        ];

        return response()->json($rootNode);
    }

    protected function mapUser($user, $level) {
        $children = $user->children;

        $mappedChildren = $children->map(function ($child) use ($level) {
            return $this->mapUser($child, $level + 1);
        });

        $mappedUser = [
            'name' => $user->name,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level + 1,
            'total_affiliate' => count($user->getChildrenIds()),
//            'self_deposit' => $this->getSelfDeposit($user),
//            'valid_affiliate_deposit' => $this->getValidAffiliateDeposit($user),
        ];

        // Add 'children' only if there are children
        if (!$mappedChildren->isEmpty()) {
            $mappedUser['children'] = $mappedChildren;
        }

        return $mappedUser;
    }
}
