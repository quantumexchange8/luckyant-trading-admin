<script setup>
import {CurrencyDollarCircleIcon, Users01Icon} from "@/Components/Icons/outline.jsx";
import {transactionFormat} from "@/Composables/index.js";

defineProps({
    totalUser: Number,
    totalPerformanceIncentive: Number,
})

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
            <div class="flex flex-col gap-4">
                <div>
                    Total Users
                </div>
                <div class="text-xl font-bold">
                        <span v-if="totalUser !== null">
                            {{ totalUser }}
                        </span>
                    <span v-else>
                          Loading...
                        </span>
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                <Users01Icon class="text-primary-500 w-8 h-8" />
            </div>
        </div>
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
            <div class="flex flex-col gap-4">
                <div>
                    Total Performance Incentives
                </div>
                <div class="text-xl font-bold">
                        <span v-if="totalPerformanceIncentive !== null">
                          $ {{ formatAmount(totalPerformanceIncentive ? totalPerformanceIncentive : 0) }}
                        </span>
                    <span v-else>
                          Loading...
                        </span>
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                <CurrencyDollarCircleIcon class="text-success-500 w-8 h-8" />
            </div>
        </div>
    </div>
</template>
