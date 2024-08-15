<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use App\Services\SelectOptionService;
use Spatie\Activitylog\Models\Activity;
use App\Http\Requests\AssignUserRequest;

class AdminController extends Controller
{
    public function admin()
    {
        return Inertia::render('Admin/Admin', [
            'roleLists' => (new SelectOptionService())->getRoles(),
        ]);
    }

    public function getAdminUsers(Request $request)
    {
        $authUser = \Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $adminQuery = User::query()
            ->whereIn('role', ['super-admin', 'admin']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $adminQuery->where(function ($innerQuery) use ($search) {
                $innerQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $adminQuery->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('rank')) {
            $rank_id = $request->input('rank');
            $adminQuery->where(function ($innerQuery) use ($rank_id) {
                $innerQuery->where('display_rank_id', $rank_id);
            });
        }

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $adminQuery->whereIn('id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $adminQuery->whereIn('id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $adminQuery->whereIn('id', []);
        }

        $results = $adminQuery
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $locale = app()->getLocale();

        $results->each(function ($user) use ($locale) {
            $rank = $user->rank;
            $translations = json_decode($rank->name, true);
            $user->user_rank = $translations[$locale] ?? $rank->name;
        });

        return response()->json([
            'adminUsers' => $results,
        ]);
    }

    public function assign_user(AssignUserRequest $request)
    {
        $user = User::find($request->user_id);
        $role = Role::find($request->role);
        $oldRole = $user->role;

        if ($user->hasRole($role->name)) {
            return redirect()->back()
                ->with('title', trans('public.is_admin'))
                ->with('warning', trans('public.already_is_admin'));
        }

        $user->assignRole($role);
        $user->role = 'admin';
        $user->save();

        $newRole = \DB::table('model_has_roles')->where('model_id', $user->id)->get();
        
        Activity::create([
            'log_name' => 'role',
            'description' => $user->name . ' has been assigned the role of admin.',
            'subject_type' => Role::class,
            'subject_id' => $user->id,
            'causer_type' => get_class(\Auth::user()),
            'causer_id' => \Auth::id(),
            'properties' => [
                'attributes' => ['role' => $newRole],
            ],
            'event' => 'created',
        ]);
                
        return redirect()->back()->with('title', trans('public.admin_added'))->with('success', trans('public.success_added_admin'));
    }

    public function remove_admin(Request $request)
    {
        $user = User::find($request->id);

        $old = \DB::table('model_has_roles')->where('model_id', $user->id)->get();
        $user->roles()->detach();
        $user->role = 'user';
        $user->save();


        Activity::create([
            'log_name' => 'role',
            'description' => $user->name . ' has been removed the role of admin.',
            'subject_type' => Role::class,
            'subject_id' => $user->id,
            'causer_type' => get_class(\Auth::user()),
            'causer_id' => \Auth::id(),
            'properties' => [
                'old' => [ $old ],
            ],
            'event' => 'deleted',
        ]);

        return redirect()->back()->with('title', trans('public.remove_admin'))->with('success', trans('public.remove_admin_success'));
    }
}
