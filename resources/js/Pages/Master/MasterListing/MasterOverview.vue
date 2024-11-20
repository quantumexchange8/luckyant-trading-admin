<script setup>
import Card from "primevue/card";
import Skeleton from "primevue/skeleton";
import {onMounted, ref} from "vue";
import { ArrowCircleUpIcon, ArrowCircleDownIcon } from '@heroicons/vue/solid'
import {
    IconUserDollar,
    IconUsersPlus
} from "@tabler/icons-vue"
import {transactionFormat} from "@/Composables/index.js";
import MasterOverviewChart from "@/Pages/Master/MasterListing/Partials/MasterOverviewChart.vue";

const props = defineProps({
    mastersCount: Number,
})

const {formatAmount} = transactionFormat();
const isLoading = ref(false);
const currentMonthTotalMaster = ref(0);
const lastMonthMasterComparison = ref(0);
const currentTotalSubscribers = ref(0);
const lastMonthSubscribersComparison = ref(0);

const getHighestDeposit = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/master/getMasterOverview');
        currentMonthTotalMaster.value = response.data.currentMonthTotalMaster;
        lastMonthMasterComparison.value = response.data.lastMonthMasterComparison;
        currentTotalSubscribers.value = response.data.currentTotalSubscribers;
        lastMonthSubscribersComparison.value = response.data.lastMonthSubscribersComparison;
    } catch (error) {
        console.error('Error fetching master overview:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    getHighestDeposit();
});

</script>

<template>
    <div class="flex flex-col lg:flex-row gap-3 md:gap-5 w-full">
        <div class="grid sm:grid-cols-2 lg:grid-cols-1 gap-3 md:gap-5 lg:min-w-80 xl:min-w-[400px]">
            <Card>
                <template #content>
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col gap-1">
                            <div class="flex flex-col items-start gap-2">
                                <div class="text-gray-600 dark:text-gray-400 font-semibold text-sm">
                                    {{ $t('public.total_masters') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-xl font-semibold md:text-xxl">
                                    <div v-if="mastersCount === 0">
                                        {{ formatAmount(0, 0) }}
                                    </div>
                                    <div v-else-if="isLoading">
                                        <Skeleton width="5rem" class="mt-0.5" height="1.75rem"></Skeleton>
                                    </div>
                                    <div v-else>
                                        {{ formatAmount(currentMonthTotalMaster, 0) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div v-if="lastMonthMasterComparison" class="flex items-center gap-2">
                                    <div
                                        class="flex items-center gap-2"
                                        :class="
                                        {
                                            'text-green-500': lastMonthMasterComparison > 0,
                                            'text-pink-500': lastMonthMasterComparison < 0
                                        }"
                                    >
                                        <ArrowCircleUpIcon v-if="lastMonthMasterComparison > 0" class="w-5 h-5" />
                                        <ArrowCircleDownIcon v-if="lastMonthMasterComparison < 0" class="w-5 h-5" />
                                        <span class="text-xs font-medium md:text-sm">  {{ `${formatAmount(lastMonthMasterComparison, 0)} pax` }}</span>
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">vs last month</span>
                                </div>
                                <span v-else class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">Data not available</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900 w-[72px] h-[72px]">
                            <div class="flex items-center justify-center rounded-full bg-primary-200 dark:bg-primary-700 w-14 h-14 text-primary-600 dark:text-primary-300">
                                <IconUserDollar size="36" />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #content>
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col gap-1">
                            <div class="flex flex-col items-start gap-2">
                                <div class="text-gray-600 dark:text-gray-400 font-semibold text-sm">
                                    {{ $t('public.total_subscribers') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-xl font-semibold md:text-xxl">
                                    <div v-if="mastersCount === 0">
                                        ¥{{ formatAmount(0) }}
                                    </div>
                                    <div v-else-if="isLoading">
                                        <Skeleton width="5rem" class="mt-0.5" height="1.75rem"></Skeleton>
                                    </div>
                                    <div v-else>
                                        {{ formatAmount(currentTotalSubscribers, 0) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div v-if="lastMonthSubscribersComparison" class="flex items-center gap-2">
                                    <div
                                        class="flex items-center gap-2"
                                        :class="
                                        {
                                            'text-green-500': lastMonthSubscribersComparison > 0,
                                            'text-pink-500': lastMonthSubscribersComparison < 0
                                        }"
                                    >
                                        <ArrowCircleUpIcon v-if="lastMonthSubscribersComparison > 0" class="w-5 h-5" />
                                        <ArrowCircleDownIcon v-if="lastMonthSubscribersComparison < 0" class="w-5 h-5" />
                                        <span class="text-xs font-medium md:text-sm">  {{ `${formatAmount(lastMonthSubscribersComparison, 0)} pax` }}</span>
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">vs last month</span>
                                </div>
                                <span v-else class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">Data not available</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-center rounded-full bg-success-100 dark:bg-success-900/80 w-[72px] h-[72px]">
                            <div class="flex items-center justify-center rounded-full bg-success-200 dark:bg-success-700 w-14 h-14 text-success-600 dark:text-success-300">
                                <IconUsersPlus size="36" />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <Card class="w-full">
            <template #content>
                <div class="flex flex-col items-start gap-5">
                    <div class="flex flex-col gap-2 items-start self-stretch text-gray-600 dark:text-gray-400 font-semibold text-sm md:flex-shrink-0">
                        {{ $t('public.analysis_of_masters_by_subscriber_count') }}
                    </div>

                    <MasterOverviewChart
                    />
                </div>
            </template>
        </Card>
    </div>
</template>
