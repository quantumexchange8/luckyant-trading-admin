<script setup>
import Card from "primevue/card";
import Skeleton from "primevue/skeleton";
import {onMounted, ref} from "vue";
import { ArrowCircleUpIcon, ArrowCircleDownIcon } from '@heroicons/vue/solid'
import {
    UsersCheckIcon,
    CurrencyDollarIcon,
} from '@/Components/Icons/outline'
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    subscriptionsCount: Number,
})

const {formatAmount} = transactionFormat();
const isLoading = ref(false);
const topThreeUser = ref([]);
const currentMonthActiveSubscriber = ref(0);
const lastMonthSubscriberComparison = ref(0);
const lastMonthActiveFundComparison = ref(0);
const currentActiveFund = ref(0);

const getHighestDeposit = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/pamm/getPammSubscriptionOverview');
        topThreeUser.value = response.data.topThreeUser;
        currentMonthActiveSubscriber.value = response.data.currentMonthActiveSubscriber;
        lastMonthSubscriberComparison.value = response.data.lastMonthSubscriberComparison;
        lastMonthActiveFundComparison.value = response.data.lastMonthActiveFundComparison;
        currentActiveFund.value = response.data.currentActiveFund;
    } catch (error) {
        console.error('Error fetching recent approvals:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    getHighestDeposit();
});

const calculatePercentage = (fund) => {
    if (!currentActiveFund.value || !fund) {
        return 0;
    }
    return Math.min(((fund / currentActiveFund.value) * 100), 100);
};
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-3 md:gap-5 w-full">
        <div class="grid sm:grid-cols-2 lg:grid-cols-1 gap-3 md:gap-5 lg:min-w-80 xl:min-w-[400px]">
            <Card>
                <template #content>
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col gap-1">
                            <div class="flex flex-col items-start gap-2">
                                <div class="text-gray-500 text-sm">
                                    Active Subscribers
                                </div>
                                <div class="text-gray-950 dark:text-white text-xl font-semibold md:text-xxl">
                                    <div v-if="subscriptionsCount === 0">
                                        {{ formatAmount(0, 0) }}
                                    </div>
                                    <div v-else-if="isLoading">
                                        <Skeleton width="5rem" class="mt-0.5" height="1.75rem"></Skeleton>
                                    </div>
                                    <div v-else>
                                        {{ formatAmount(currentMonthActiveSubscriber, 0) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div v-if="currentMonthActiveSubscriber" class="flex items-center gap-2">
                                    <div
                                        class="flex items-center gap-2"
                                        :class="
                                        {
                                            'text-green-500': lastMonthSubscriberComparison > 0,
                                            'text-pink-500': lastMonthSubscriberComparison < 0
                                        }"
                                    >
                                        <ArrowCircleUpIcon v-if="lastMonthSubscriberComparison > 0" class="w-5 h-5" />
                                        <ArrowCircleDownIcon v-if="lastMonthSubscriberComparison < 0" class="w-5 h-5" />
                                        <span class="text-xs font-medium md:text-sm">  {{ `${formatAmount(lastMonthSubscriberComparison, 0)} pax` }}</span>
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">vs last month</span>
                                </div>
                                <span v-else class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">Data not available</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900 w-[72px] h-[72px]">
                            <div class="flex items-center justify-center rounded-full bg-primary-200 dark:bg-primary-700 w-14 h-14 text-primary-600 dark:text-primary-300">
                                <UsersCheckIcon class="w-9 h-9" />
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
                                <div class="text-gray-500 text-sm">
                                    Active Fund
                                </div>
                                <div class="text-gray-950 dark:text-white text-xl font-semibold md:text-xxl">
                                    <div v-if="subscriptionsCount === 0">
                                        ¥{{ formatAmount(0) }}
                                    </div>
                                    <div v-else-if="isLoading">
                                        <Skeleton width="5rem" class="mt-0.5" height="1.75rem"></Skeleton>
                                    </div>
                                    <div v-else>
                                        $ {{ formatAmount(currentActiveFund) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div v-if="currentMonthActiveSubscriber" class="flex items-center gap-2">
                                    <div
                                        class="flex items-center gap-2"
                                        :class="
                                        {
                                            'text-green-500': lastMonthActiveFundComparison > 0,
                                            'text-pink-500': lastMonthActiveFundComparison < 0
                                        }"
                                    >
                                        <ArrowCircleUpIcon v-if="lastMonthActiveFundComparison > 0" class="w-5 h-5" />
                                        <ArrowCircleDownIcon v-if="lastMonthActiveFundComparison < 0" class="w-5 h-5" />
                                        <span class="text-xs font-medium md:text-sm">  {{ `${formatAmount(lastMonthActiveFundComparison)}%` }}</span>
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">vs last month</span>
                                </div>
                                <span v-else class="text-gray-400 dark:text-gray-600 text-xs md:text-sm">Data not available</span>
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

        <Card class="w-full">
            <template #content>
                <div class="flex flex-col items-start gap-5">
                    <div class="flex flex-col gap-2 items-start self-stretch md:flex-shrink-0">
                        <div class="flex justify-center items-center">
                            <span class="text-gray-500 text-sm">Top Active Subscribers</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 items-center self-stretch w-full">
                        <div v-for="index in 3" :key="index" class="flex items-center py-3 gap-3 md:gap-4 w-full">
                            <div class="w-full flex items-center min-w-[140px] md:min-w-[180px] md:max-w-[240px] gap-3 md:gap-4">
                                <img
                                    class="object-cover w-7 h-7 rounded-full"
                                    :src="topThreeUser[index - 1]?.user.profile_photo ? topThreeUser[index - 1]?.user.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                                    alt="userPic"
                                />
                                <span class="truncate w-full max-w-[180px] md:max-w-[200px] text-gray-950 dark:text-white text-sm font-medium md:text-base">
                                    {{ topThreeUser[index - 1]?.user.name || '------' }}
                                </span>
                            </div>
                            <div class="w-full max-w-[52px] xs:max-w-sm sm:max-w-md md:max-w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full relative">
                                <div
                                    class="absolute top-0 left-0 h-full rounded-full bg-primary-500 transition-all duration-700 ease-in-out"
                                    :style="{ width: `${calculatePercentage(topThreeUser[index - 1]?.total_fund)}%` }"
                                />
                            </div>
                            <span class="truncate text-gray-950 dark:text-white text-right text-sm font-medium md:text-base w-full max-w-[88px] md:max-w-[110px]">
                                $ {{ topThreeUser[index - 1]?.total_fund ? formatAmount(topThreeUser[index - 1].total_fund) : formatAmount(0) }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
