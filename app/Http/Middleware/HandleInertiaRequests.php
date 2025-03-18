<?php

namespace App\Http\Middleware;

use App\Services\SidebarService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sidebarService = new SidebarService();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'auth.user.roles' => fn() => $request->user() ? $request->user()->getRoleNames() : null,
            'auth.user.permissions' => fn() => $request->user() ? $request->user()->getPermissionNames() : null,
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'toast' => session('toast'),
            'title' => session('title'),
            'success' => session('success'),
            'warning' => session('warning'),
            'pendingTransactionCount' => $sidebarService->getPendingTransactionCount(),
            'pendingKycCount' => $sidebarService->getPendingKycCount(),
            'pendingMasterCount' => $sidebarService->getPendingMasterCount(),
            'pendingSubscriberRequestCount' => $sidebarService->getPendingSubscriberRequestCount(),
            'pendingRenewalCount' => $sidebarService->getPendingRenewalCount(),
            'pendingSwitchMasterCount' => $sidebarService->getPendingSwitchMasterCount(),
            'pendingPammCount' => $sidebarService->getPendingPammCount(),
            'pendingBalanceIn' => $sidebarService->pendingBalanceIn(),
            'pendingApplicants' => $sidebarService->pendingApplicants(),
            'locale' => session('locale') ? session('locale') : app()->getLocale(),
        ];
    }
}
