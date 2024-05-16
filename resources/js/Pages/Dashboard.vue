<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from '@/Components/Button.vue'
import {transactionFormat} from "@/Composables/index.js";
import {ChevronRightIcon} from "@/Components/Icons/outline.jsx";
import TopGroup from "@/Pages/Dashboard/TopGroup.vue";
import TotalDeposit from "@/Pages/Dashboard/TotalDeposit.vue";
import {usePermission} from "@/Composables/permissions.js";

const props = defineProps({
    pendingDeposits: String,
    pendingWithdrawals: String,
    pendingSubscribers: Number,
    pendingKyc: Number,
});

const { formatDateTime, formatAmount } = transactionFormat();
const { hasRole } = usePermission();

const handleRedirectTo = (pending) => {
    switch (pending) {
        case 'deposit':
            window.location.href = route('transaction.pending_transaction', {status: 'deposit'});
            break
        case 'withdrawal':
            window.location.href = route('transaction.pending_transaction', {status: 'withdrawal'});
            break
        case 'subscriber':
            window.location.href = route('subscriber.pending_subscriber');
            break
        case 'kyc':
            window.location.href = route('member.member_listing', {status: 'pending'});
            break
        default:
            console.error('Unknown pending status:', pending)
    }
}


</script>

<template>
    <AuthenticatedLayout title="Dashboard">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Dashboard
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 w-full">
            <div class="flex flex-col gap-5 col-span-3 xl:col-span-2">
                <div class="flex flex-col sm:flex-row gap-5 items-center w-full">
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
                        <div class="flex flex-col gap-2">
                            <div class="text-sm text-gray-400">
                                Pending Deposit
                            </div>
                            <div class="text-xl text-gray-900 dark:text-white font-semibold">
                                $ {{ formatAmount(pendingDeposits) }}
                            </div>
                        </div>
                        <div
                            class="rounded-full bg-primary-400 grow-0 shrink-0 hover:-translate-y-1 transition-all duration-300 ease-in-out hover:cursor-pointer"
                            @click="handleRedirectTo('deposit')"
                        >
                            <ChevronRightIcon class="text-white" />
                        </div>
                    </div>
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
                        <div class="flex flex-col gap-2">
                            <div class="text-sm text-gray-400">
                                Pending Withdrawal
                            </div>
                            <div class="text-xl text-gray-900 dark:text-white font-semibold">
                                $ {{ formatAmount(pendingWithdrawals) }}
                            </div>
                        </div>
                        <div
                            class="rounded-full bg-primary-400 grow-0 shrink-0 hover:-translate-y-1 transition-all duration-300 ease-in-out hover:cursor-pointer"
                            @click="handleRedirectTo('withdrawal')"
                        >
                            <ChevronRightIcon class="text-white" />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-5 items-center w-full">
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
                        <div class="flex flex-col gap-2">
                            <div class="text-sm text-gray-400">
                                Pending Subscriber
                            </div>
                            <div class="text-xl text-gray-900 dark:text-white font-semibold">
                                {{ formatAmount(pendingSubscribers, 0) }}
                            </div>
                        </div>
                        <div
                            class="rounded-full bg-primary-400 grow-0 shrink-0 hover:-translate-y-1 transition-all duration-300 ease-in-out hover:cursor-pointer"
                            @click="handleRedirectTo('subscriber')"
                        >
                            <ChevronRightIcon class="text-white" />
                        </div>
                    </div>
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
                        <div class="flex flex-col gap-2">
                            <div class="text-sm text-gray-400">
                                Pending KYC
                            </div>
                            <div class="text-xl text-gray-900 dark:text-white font-semibold">
                                {{ formatAmount(pendingKyc, 0) }}
                            </div>
                        </div>
                        <div
                            class="rounded-full bg-primary-400 grow-0 shrink-0 hover:-translate-y-1 transition-all duration-300 ease-in-out hover:cursor-pointer"
                            @click="handleRedirectTo('kyc')"
                        >
                            <ChevronRightIcon class="text-white" />
                        </div>
                    </div>
                </div>
                <TotalDeposit />
            </div>
            <div v-if="hasRole('super-admin')" class="rounded-md col-span-3 xl:col-span-1 bg-white dark:bg-gray-900 shadow-md p-5">
                <TopGroup />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
