<script setup>
import Card from "primevue/card";
import Skeleton from "primevue/skeleton";
import {transactionFormat} from "@/Composables/index.js";
import {onMounted, ref} from "vue";
import {ArrowCircleDownIcon, ArrowCircleUpIcon} from "@heroicons/vue/solid";
import {CurrencyDollarIcon} from "@/Components/Icons/outline.jsx";
import {IconCoinOff} from "@tabler/icons-vue";

const props = defineProps({
    terminationsCount: Number,
    routeName: String,
})

const {formatAmount} = transactionFormat();
const isLoading = ref(false);
const currentTerminationFund = ref(0);
const lastMonthTerminationFundComparison = ref(0);
const currentTerminationFee = ref(0);
const lastMonthTerminationFeeComparison = ref(0);

const getOverview = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/${props.routeName==='pamm'?'pamm':'copy_trading'}/getTerminationOverview`);
        currentTerminationFund.value = response.data.currentTerminationFund;
        lastMonthTerminationFundComparison.value = response.data.lastMonthTerminationFundComparison;
        currentTerminationFee.value = response.data.currentTerminationFee;
        lastMonthTerminationFeeComparison.value = response.data.lastMonthTerminationFeeComparison;
    } catch (error) {
        console.error('Error fetching recent approvals:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    getOverview();
});
</script>

<template>
    <div class="flex flex-col md:flex-row gap-3 md:gap-5 w-full">
        <Card class="w-full">
            <template #content>
                <div class="flex justify-between items-center">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-col items-start gap-2">
                            <div class="text-gray-500 text-sm font-semibold">
                                {{ $t('public.termination_fund') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-xl font-semibold md:text-xxl">
                                <div v-if="terminationsCount === 0">
                                    ¥{{ formatAmount(0) }}
                                </div>
                                <div v-else-if="isLoading">
                                    <Skeleton width="5rem" class="mt-0.5" height="1.75rem"></Skeleton>
                                </div>
                                <div v-else>
                                    $ {{ formatAmount(currentTerminationFund) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div v-if="currentTerminationFund" class="flex items-center gap-2">
                                <div
                                    class="flex items-center gap-2"
                                    :class="
                                        {
                                            'text-green-500': lastMonthTerminationFundComparison > 0,
                                            'text-pink-500': lastMonthTerminationFundComparison < 0,
                                            'text-gray-500': lastMonthTerminationFundComparison === 0,
                                        }"
                                >
                                    <ArrowCircleUpIcon v-if="lastMonthTerminationFundComparison > 0" class="w-5 h-5" />
                                    <ArrowCircleDownIcon v-if="lastMonthTerminationFundComparison < 0" class="w-5 h-5" />
                                    <span class="text-xs font-medium md:text-sm">  {{ `${formatAmount(lastMonthTerminationFundComparison)}%` }}</span>
                                </div>
                                <span class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">{{ $t('public.vs_last_month') }}</span>
                            </div>
                            <span v-else class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">{{ $t('public.data_not_available') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-center rounded-full bg-error-100 dark:bg-error-900 w-[72px] h-[72px]">
                        <div class="flex items-center justify-center rounded-full bg-error-200 dark:bg-error-700 w-14 h-14 text-error-600 dark:text-error-300">
                            <IconCoinOff class="w-9 h-9" />
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <Card class="w-full">
            <template #content>
                <div class="flex justify-between items-center">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-col items-start gap-2">
                            <div class="text-gray-500 text-sm font-semibold">
                                {{ $t('public.termination_fee') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-xl font-semibold md:text-xxl">
                                <div v-if="terminationsCount === 0">
                                    ¥{{ formatAmount(0) }}
                                </div>
                                <div v-else-if="isLoading">
                                    <Skeleton width="5rem" class="mt-0.5" height="1.75rem"></Skeleton>
                                </div>
                                <div v-else>
                                    $ {{ formatAmount(currentTerminationFee) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div v-if="currentTerminationFee" class="flex items-center gap-2">
                                <div
                                    class="flex items-center gap-2"
                                    :class="
                                        {
                                            'text-green-500': lastMonthTerminationFeeComparison > 0,
                                            'text-pink-500': lastMonthTerminationFeeComparison < 0,
                                            'text-gray-500': lastMonthTerminationFeeComparison === 0,
                                        }"
                                >
                                    <ArrowCircleUpIcon v-if="lastMonthTerminationFeeComparison > 0" class="w-5 h-5" />
                                    <ArrowCircleDownIcon v-if="lastMonthTerminationFeeComparison < 0" class="w-5 h-5" />
                                    <span class="text-xs font-medium md:text-sm">  {{ `${formatAmount(lastMonthTerminationFeeComparison, 0)}%` }}</span>
                                </div>
                                <span class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">{{ $t('public.vs_last_month') }}</span>
                            </div>
                            <span v-else class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">{{ $t('public.data_not_available') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-center rounded-full bg-success-100 dark:bg-success-900/80 w-[72px] h-[72px]">
                        <div class="flex items-center justify-center rounded-full bg-success-200 dark:bg-success-700 w-14 h-14 text-success-600 dark:text-success-300">
                            <CurrencyDollarIcon class="w-9 h-9" />
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
