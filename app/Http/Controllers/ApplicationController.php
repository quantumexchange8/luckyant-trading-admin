<?php

namespace App\Http\Controllers;

use App\Models\ApplicationForm;
use App\Models\ApplicationFormToLeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function index()
    {
        return Inertia::render('Application/Listing/ApplicationListing', [
            'applicationsCount' => ApplicationForm::count()
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
}
