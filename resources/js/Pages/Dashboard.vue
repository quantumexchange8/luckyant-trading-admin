<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from '@/Components/Button.vue'
import { GithubIcon } from '@/Components/Icons/brands'
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    totalDeposits: Number, 
    totalWithdrawals: Number, 
    kyc: Number, 
    pendingTransaction: Number, 
});

const { formatDateTime, formatAmount } = transactionFormat();

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

        <div class="p-6 mb-3 overflow-hidden bg-white rounded-md shadow-md dark:bg-gray-900">
            You're logged in!
        </div>

        <div class="grid grid-cols-4 w-full gap-4">
            <div class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div>
                    Total Deposit
                </div>
                <div class="text-2xl font-bold">
                    $ {{ formatAmount(props.totalDeposits) }}
                </div>
            </div>

            <div class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div>
                    Total Withdrawal
                </div>
                <div class="text-2xl font-bold">
                    $ {{ formatAmount(props.totalWithdrawals) }}
                </div>
            </div>

            <div 
            class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900"
            >
                <div>
                    Pending KYC
                </div>
                <div class="text-2xl font-bold">
                    {{ props.kyc }}
                </div>
            </div>

            <div class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div>
                    Pending Transaction
                </div>
                <div class="text-2xl font-bold">
                    {{ props.pendingTransaction }}
                </div>
            </div>
        </div>

        
    </AuthenticatedLayout>
</template>
