<?php

namespace App\Http\Controllers;

use App\Exports\ApplicantExport;
use App\Models\Applicant;
use App\Models\ApplicationForm;
use App\Models\ApplicationFormToLeader;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationController extends Controller
{
    public function index()
    {
        return Inertia::render('Application/Listing/ApplicationListing', [
            'applicationsCount' => ApplicationForm::count()
        ]);
    }

    public function pending_application()
    {
        return Inertia::render('Application/Pending/ApplicationPending', [
            'applicantsCount' => Applicant::where('status', 'pending')->count()
        ]);
    }

    public function addApplication(Request $request)
    {
        Validator::make($request->all(), [
            'title' => ['required'],
            'content' => ['required'],
            'recipient' => ['nullable'],
        ])->setAttributeNames([
            'title' => trans('public.title'),
            'content' => trans('public.content'),
            'recipient' => trans('public.recipient'),
        ])->validate();

        $application = ApplicationForm::create([
            'title' => $request->title,
            'content' => $request->input('content'),
            'status' => 'Active',
            'handle_by' => \Auth::id()
        ]);

        $leaders = $request->recipient;

        if ($leaders) {
            foreach ($leaders as $leader) {
                ApplicationFormToLeader::create([
                    'application_form_id' => $application->id,
                    'user_id' => $leader['id'],
                ]);
            }
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_create_application"),
            'type' => 'success',
        ]);
    }

    public function getPendingApplications(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true); //only() extract parameters in lazyEvent

            //user query
            $query = Applicant::query()
                ->with([
                    'user:id,name,email,hierarchyList',
                    'country',
                    'application_form',
                    'transport_detail',
                    'transport_detail.country'
                ])->where('status', 'pending');

            //global filter
            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        });
                    });
                });
            }

            //date filter
            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay(); //add day to ensure capture entire day
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            // Filter by leaderId if provided
            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('leader_status', 1)
                    ->where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('user_id', $usersUnderLeader);
            }

            if ($data['filters']['requires_flight']['value']) {
                $query->where('requires_transport', $data['filters']['requires_flight']['value'] == 'true' ? 1 : 0);
            }

            if ($data['filters']['requires_ib_training']['value']) {
                $query->where('requires_ib_training', $data['filters']['requires_ib_training']['value'] == 'true' ? 1 : 0);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            if ($request->exportStatus) {
                return Excel::download(new ApplicantExport($query), Carbon::now() . '-pending-applicants.xlsx');
            }

            $applicants = $query->paginate($data['rows']);

            // Extract all hierarchy IDs from users' hierarchyLists
            $userHierarchyLists = $applicants->pluck('user.hierarchyList')
                ->filter()
                ->flatMap(fn($list) => explode('-', trim($list, '-')))
                ->unique()
                ->toArray();

            // Load all potential leaders in bulk
            $leaders = User::whereIn('id', $userHierarchyLists)
                ->where('leader_status', 1) // Only load users with leader_status == 1
                ->get()
                ->keyBy('id');

            // Attach the first leader details
            $applicants->each(function ($query) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($query->user?->hierarchyList, $leaders);

                $query->first_leader_id = $firstLeader?->id;
                $query->first_leader_name = $firstLeader?->name;
                $query->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $applicants,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function updateApplicationApproval(Request $request)
    {
        Validator::make($request->all(), [
            'action' => ['required'],
            'remarks' => ['required_if:action,reject']
        ])->setAttributeNames([
            'action' => trans('public.action'),
            'remarks' => trans('public.remarks')
        ])->validate();

        $applicant = Applicant::find($request->applicant_id);

        if ($request->action == 'approve') {
            $applicant->status = 'approved';
        } else {
            $applicant->status = 'rejected';
            $applicant->remarks = $request->remarks;
        }
        $applicant->approval_at = now();
        $applicant->update();

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_{$applicant->status}_applicant"),
            'type' => 'success',
        ]);
    }

    public function getApplicationData(Request $request)
    {
        // fetch limit with default
        $limit = $request->input('limit', 6);

        // Fetch parameter from request
        $search = $request->input('search', '');
        $start_date = $request->input('start_date', '');
        $end_date = $request->input('end_date', '');

        $query = ApplicationForm::with([
            'leaders',
        ])
            ->withCount('approved_applicants');

        if (!empty($search)) {
            $keyword = '%' . $search . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('subject', 'like', $keyword)
                    ->orWhere('details', 'like', $keyword);
            });
        }

        if (!empty($start_date) && !empty($end_date)) {
            $startDate = Carbon::parse($start_date)->addDay()->startOfDay();
            $endDate = Carbon::parse($end_date)->addDay()->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Get total count of masters
        $totalRecords = $query->count();

        // Fetch paginated results
        $announcements = $query->latest()
            ->paginate($limit);

        $formattedApplications = $announcements->map(function ($master) {
            // if all leaders included, return 'everyone'
            $totalCount = $master->leaders->count();
            $leadersCount = User::where('leader_status', 1)->count();

            if ($totalCount === $leadersCount) {
                $master->leaders_names = 'everyone';
            } else {
                $master->leaders_names = $master->leaders->pluck('name')->join(', ');
            }

            return $master;
        });

        return response()->json([
            'applications' => $formattedApplications,
            'totalRecords' => $totalRecords,
            'currentPage' => $announcements->currentPage(),
        ]);
    }

    public function getApplicantsData(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true); //only() extract parameters in lazyEvent

            //user query
            $query = Applicant::query()
                ->with([
                    'user:id,name,email,hierarchyList',
                    'country',
                    'application_form',
                    'transport_detail',
                    'transport_detail.country'
                ])->where([
                    'application_form_id' => $data['filters']['form_id']['value'],
                    'status' => 'approved',
                ]);

            //global filter
            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        });
                    });
                });
            }

            //date filter
            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay(); //add day to ensure capture entire day
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            // Filter by leaderId if provided
            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('leader_status', 1)
                    ->where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('user_id', $usersUnderLeader);
            }

            if ($data['filters']['requires_flight']['value']) {
                $query->where('requires_transport', $data['filters']['requires_flight']['value'] == 'true' ? 1 : 0);
            }

            if ($data['filters']['requires_ib_training']['value']) {
                $query->where('requires_ib_training', $data['filters']['requires_ib_training']['value'] == 'true' ? 1 : 0);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            if ($request->exportStatus) {
                return Excel::download(new ApplicantExport($query), Carbon::now() . '-attendance.xlsx');
            }

            $applicants = $query->paginate($data['rows']);

            // Extract all hierarchy IDs from users' hierarchyLists
            $userHierarchyLists = $applicants->pluck('user.hierarchyList')
                ->filter()
                ->flatMap(fn($list) => explode('-', trim($list, '-')))
                ->unique()
                ->toArray();

            // Load all potential leaders in bulk
            $leaders = User::whereIn('id', $userHierarchyLists)
                ->where('leader_status', 1) // Only load users with leader_status == 1
                ->get()
                ->keyBy('id');

            // Attach the first leader details
            $applicants->each(function ($query) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($query->user?->hierarchyList, $leaders);

                $query->first_leader_id = $firstLeader?->id;
                $query->first_leader_name = $firstLeader?->name;
                $query->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $applicants,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function updateApplication(Request $request)
    {
        Validator::make($request->all(), [
            'title' => ['required'],
            'content' => ['required'],
            'recipient' => ['nullable'],
        ])->setAttributeNames([
            'title' => trans('public.title'),
            'content' => trans('public.content'),
            'recipient' => trans('public.recipient'),
        ])->validate();

        $application = ApplicationForm::find($request->form_id);

        $application->update([
            'title' => $request->title,
            'content' => $request->input('content'),
            'handle_by' => \Auth::id()
        ]);

        $leaders = $request->recipient;

        if ($leaders) {
            $existingLeaders = ApplicationFormToLeader::where('application_form_id', $application->id)
                ->pluck('user_id')
                ->toArray();

            $incomingLeaderIds = collect($leaders)->pluck('id')->toArray();

            if (!empty(array_diff($existingLeaders, $incomingLeaderIds)) || !empty(array_diff($incomingLeaderIds, $existingLeaders))) {
                ApplicationFormToLeader::where('application_form_id', $application->id)->delete();

                foreach ($leaders as $leader) {
                    ApplicationFormToLeader::create([
                        'application_form_id' => $application->id,
                        'user_id' => $leader['id'],
                    ]);
                }
            }
        } else {
            ApplicationFormToLeader::where('application_form_id', $application->id)->delete();
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_application"),
            'type' => 'success',
        ]);
    }

    public function updateStatus(Request $request)
    {
        $application = ApplicationForm::find($request->id);

        $application->status = $application->status == 'Active' ? 'Inactive' : 'Active';
        $application->save();

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_application"),
            'type' => 'success',
        ]);
    }

    public function deleteApplication(Request $request)
    {
        $application = ApplicationForm::find($request->id);

        $application->delete();

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_delete_application"),
            'type' => 'success',
        ]);
    }
}
