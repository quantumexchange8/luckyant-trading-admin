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
    pendingPamm: Number,
    pendingKyc: Number,
    dailyRegister: Number,
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
            window.location.href = route('copy_trading.pending');
            break
        case 'pamm':
            window.location.href = route('pamm.pending_pamm');
            break
        case 'kyc':
            window.location.href = route('member.member_listing', {status: 'pending'});
            break
        case 'daily_register':
            window.location.href = route('report.daily_register');
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

        <div class="flex flex-col xl:flex-row self-stretch gap-5 w-full">
            <div class="flex flex-col w-full gap-5">
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
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
                        <div class="flex flex-col gap-2">
                            <div class="text-sm text-gray-400">
                                Daily Registers
                            </div>
                            <div class="text-xl text-gray-900 dark:text-white font-semibold">
                               {{ dailyRegister }}
                            </div>
                        </div>
                        <div
                            class="rounded-full bg-primary-400 grow-0 shrink-0 hover:-translate-y-1 transition-all duration-300 ease-in-out hover:cursor-pointer"
                            @click="handleRedirectTo('daily_register')"
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
                                Pending PAMM
                            </div>
                            <div class="text-xl text-gray-900 dark:text-white font-semibold">
                                {{ formatAmount(pendingPamm, 0) }}
                            </div>
                        </div>
                        <div
                            class="rounded-full bg-primary-400 grow-0 shrink-0 hover:-translate-y-1 transition-all duration-300 ease-in-out hover:cursor-pointer"
                            @click="handleRedirectTo('pamm')"
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
            <div v-if="hasRole('super-admin')" class="rounded-md bg-white dark:bg-gray-900 shadow-md p-5 w-full xl:max-w-[358px] 2xl:max-w-[560px]">
                <TopGroup />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
