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
        $authUser = \Auth::user();

        $query = Transaction::query()
            ->where('category', 'wallet')
            ->where('status', 'Processing');

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $query->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $query->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $query->whereIn('user_id', []);
        }

        return $query->count();
    }

    public function getPendingKycCount(): int
    {
        return User::where('role', 'user')
            ->where('kyc_approval', 'Pending')
            ->count();
    }

    public function getPendingMasterCount(): int
    {
        $authUser = \Auth::user();

        $query = MasterRequest::query()
            ->where('status', 'Pending');

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $query->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $query->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $query->whereIn('user_id', []);
        }

        return $query->count();
    }

    public function getPendingSubscriberRequestCount(): int
    {
        $authUser = \Auth::user();

        $subscription_renewal = SubscriptionRenewalRequest::where('status', 'Pending');
        $subscriber = Subscription::where('status', 'Pending');

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $subscription_renewal->whereIn('user_id', $childrenIds);
            $subscriber->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $subscription_renewal->whereIn('user_id', $childrenIds);
            $subscriber->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $subscription_renewal->whereIn('user_id', []);
            $subscriber->whereIn('user_id', []);
        }

        return $subscription_renewal->count() + $subscriber->count();
    }
}
