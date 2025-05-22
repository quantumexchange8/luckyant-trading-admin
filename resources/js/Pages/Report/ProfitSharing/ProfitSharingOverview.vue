<script setup>
import {CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";
import {transactionFormat} from "@/Composables/index.js";
import {IconTransactionDollar} from "@tabler/icons-vue";

defineProps({
    activeCapital: Number,
    totalProfitSharing: Number,
})

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.active_fund') }}
                </div>
                <div class="text-xl font-bold">
                        <span v-if="activeCapital !== null">
                            ${{ formatAmount(activeCapital) }}
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
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.profit_sharing') }}
                </div>
                <div class="text-xl font-bold">
                        <span v-if="totalProfitSharing !== null">
                          $ {{ formatAmount(totalProfitSharing ? totalProfitSharing : 0) }}
                        </span>
                    <span v-else>
                          Loading...
                        </span>
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-purple-200 text-purple-500">
                <IconTransactionDollar size="32" stroke-width="1.5" />
            </div>
        </div>
    </div>
</template>
