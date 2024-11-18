<script setup>
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import {SearchLgIcon, SlidersOneIcon} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import Button from "primevue/button";
import Card from "primevue/card";
import Paginator from "primevue/paginator";
import Tag from "primevue/tag";
import NoData from "@/Components/NoData.vue";
import {transactionFormat} from "@/Composables/index.js";
import {
    IconCircleXFilled,
    IconUserDollar,
    IconPremiumRights,
    IconScanEye,
} from '@tabler/icons-vue';

const props = defineProps({
    mastersCount: Number
})

const masters = ref([]);
const search = ref('');
const selectedSort = ref('latest');
const isLoading = ref(false);
const tag = ref();
const status = ref();
const currentPage = ref(1);
const rowsPerPage = ref(12);
const totalRecords = ref(0);
const {formatAmount} = transactionFormat();

const sortOptions = ref([
    'latest',
    'popular',
    'largest_fund',
    'most_investor',
]);

const getResults = async (page = 1, rowsPerPage = 12) => {
    isLoading.value = true;

    try {
        let url = `/master/getMasters?page=${page}&limit=${rowsPerPage}`;

        if (selectedSort.value && selectedSort.value) {
            url += `&sortType=${selectedSort.value}`;
        }

        if (tag.value) {
            url += `&tag=${tag.value}`;
        }

        if (status.value) {
            url += `&status=${status.value}`;
        }

        if (search.value) {
            url += `&search=${search.value}`;
        }

        const response = await axios.get(url);
        masters.value = response.data.masters;
        totalRecords.value = response.data.totalRecords;
        currentPage.value = response.data.currentPage;
    } catch (error) {
        console.error('Error getting masters:', error);
    } finally {
        isLoading.value = false;
    }
};

// Initial call to populate data
getResults(currentPage.value, rowsPerPage.value);

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
    getResults(currentPage.value, rowsPerPage.value);
};

const clearSearch = () => {
    search.value = '';
}
</script>

<template>
    <div class="flex flex-col md:flex-row gap-3 items-center self-stretch">
        <div class="relative w-full md:w-60">
            <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                <SearchLgIcon class="w-5 h-5" />
            </div>
            <InputText v-model="search" :placeholder="$t('public.search')" class="font-normal pl-12 w-full md:w-60" />
            <div
                v-if="search"
                class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                @click="clearSearch"
            >
                <IconCircleXFilled size="16" />
            </div>
        </div>
        <div class="w-full flex justify-between items-center self-stretch gap-3">
            <Button
                class="w-full md:w-28 flex gap-2"
                severity="secondary"
            >
                <SlidersOneIcon class="w-4 h-4" />
                Filter
            </Button>

            <Select
                v-model="selectedSort"
                :options="sortOptions"
                optionLabel="name"
                :placeholder="$t('public.filter_sort')"
                class="w-full md:w-56"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div>{{ $t(`public.${slotProps.value}`) }}</div>
                        </div>
                    </div>
                </template>
                <template #option="slotProps">
                    {{ $t(`public.${slotProps.option}`) }}
                </template>
            </Select>
        </div>
    </div>

    <div v-if="mastersCount === 0 && !masters.length">
        <NoData />
    </div>

    <div v-else class="w-full">
        <div v-if="isLoading">
            q
        </div>

        <div v-else-if="!masters.length">
            <NoData />
        </div>

        <div v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 3xl:grid-cols-4 gap-5 self-stretch">
                <Card
                    v-for="(master, index) in masters"
                    :key="index"
                >
                    <template #content>
                        <div class="flex flex-col items-center gap-4 self-stretch">
                            <!-- Profile Section -->
                            <div class="w-full flex items-center gap-4 self-stretch">
                                <img
                                    class="object-cover w-7 h-7 rounded-full"
                                    :src="master.profile_photo ? master.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                                    alt="masterPic"
                                />
                                <div class="flex flex-col items-start">
                                    <div class="self-stretch truncate text-gray-950 dark:text-white font-bold">
                                        {{ master.trading_user.name }}
                                    </div>
                                    <div class="self-stretch truncate text-gray-500 text-sm">
                                        {{ master.meta_login }}
                                    </div>
                                </div>
                                <div class="flex gap-3 items-center w-full justify-end">
<!--                                    <TradingMasterAction-->
<!--                                        :master="master"-->
<!--                                    />-->
                                </div>
                            </div>

                            <!-- StatusBadge Section -->
                            <div class="flex items-center gap-2 self-stretch">
                                <Tag severity="primary">
                                    $ {{ formatAmount(master.min_join_equity) }}
                                </Tag>
                                <Tag severity="secondary">
                                    <div v-if="master.join_period !== 0">
                                        {{ master.join_period }}
                                        {{ $t('public.days') }}
                                    </div>
                                    <div v-else>
                                        {{ $t('public.lock_free') }}
                                    </div>
                                </Tag>
                                <Tag severity="secondary">
                                    {{ formatAmount(master.sharing_profit, 0) + '%&nbsp;' + $t('public.profit') }}
                                </Tag>
                            </div>

                            <!-- Performance Section -->
                            <div class="py-2 flex justify-center items-center gap-2 self-stretch border-y border-solid border-gray-200 dark:border-gray-700">
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-gray-950 dark:text-white text-center font-semibold">
                                        {{ formatAmount(master.total_gain) }}%
                                    </div>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.total_gain') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-gray-950 dark:text-white text-center font-semibold">
                                        {{ formatAmount(master.monthly_gain) }}%
                                    </div>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.monthly_gain') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-center font-semibold">
                                        <div
                                            v-if="master.latest_profit !== 0"
                                            :class="(master.latest_profit < 0) ? 'text-error-500' : 'text-success-500'"
                                        >
                                            ${{ formatAmount(master.latest_profit) }}
                                        </div>
                                        <div
                                            v-else
                                            class="text-gray-950 dark:text-white"
                                        >
                                            -
                                        </div>
                                    </div>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.total_pnl') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="flex items-end justify-between self-stretch">
                                <div class="flex flex-col items-center gap-1 self-stretch">
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <IconUserDollar size="20" stroke-width="1.25" />
                                        <div class="w-full text-gray-950 dark:text-white text-sm font-medium">
                                            {{ master.active_copy_trades_count + master.active_pamm_count }} {{ $t('public.investors') }}
                                        </div>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <IconPremiumRights size="20" stroke-width="1.25" />
                                        <div class="w-full text-gray-950 dark:text-white text-sm font-medium">
                                            <span class="text-primary-500">$ {{ formatAmount(master.active_copy_trades_sum_subscribe_amount ?? 0 + master.active_pamm_sum_subscription_amount ?? 0) }}</span> {{ $t('public.fund_capital') }}
                                        </div>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <IconScanEye size="20" stroke-width="1.25" />
                                        <div class="w-full text-gray-950 dark:text-white text-sm font-medium max-w-[128px] xxs:max-w-[180px] xs:max-w-[220px] sm:max-w-full md:max-w-[180px] xl:max-w-md 3xl:max-w-[180px] truncate">
                                            {{ master.visibility_type === 'public' ? $t(`public.${master.visibility_type}`) : master.group_names }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            <Paginator
                :first="(currentPage - 1) * rowsPerPage"
                :rows="rowsPerPage"
                :totalRecords="totalRecords"
                @page="onPageChange"
            />
        </div>
    </div>
</template>
