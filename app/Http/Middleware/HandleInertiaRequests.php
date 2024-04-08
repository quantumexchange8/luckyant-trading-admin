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
            'locale' => session('locale') ? session('locale') : app()->getLocale(),
        ];
    }
}
