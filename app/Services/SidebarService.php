<?php

namespace App\Services;

use App\Models\MasterRequest;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionRenewalRequest;
use App\Models\Transaction;
use App\Models\User;

class SidebarService {
    public function getPendingTransactionCount(): int
    {
        return Transaction::query()
            ->where('category', 'wallet')
            ->where('status', 'Processing')
            ->count();
    }

    public function getPendingKycCount(): int
    {
        return User::where('role', 'member')
            ->where('kyc_approval', 'Pending')
            ->count();
    }

    public function getPendingMasterCount(): int
    {
        return MasterRequest::query()
            ->where('status', 'Pending')
            ->count();
    }

    public function getPendingSubscriberRequestCount(): int
    {
        $subscription_renewal = SubscriptionRenewalRequest::where('status', 'Pending')->count();
        $subscriber = Subscription::where('status', 'Pending')->count();

        return $subscription_renewal + $subscriber;
    }
}
