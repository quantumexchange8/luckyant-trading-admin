<script setup>
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import {SearchLgIcon, SlidersOneIcon, XIcon} from "@/Components/Icons/outline.jsx";
import {ref, watch, watchEffect} from "vue";
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
import MasterListAction from "@/Pages/Master/MasterListing/Partials/MasterListAction.vue";
import Skeleton from "primevue/skeleton";
import {usePage} from "@inertiajs/vue3";
import debounce from "lodash/debounce.js";
import Popover from "primevue/popover";
import RadioButton from "primevue/radiobutton";
import MultiSelect from "primevue/multiselect";
import Checkbox from "primevue/checkbox"

const props = defineProps({
    mastersCount: Number
})

const masters = ref([]);
const search = ref('');
const selectedSort = ref('latest');
const isLoading = ref(false);
const tag = ref();
const strategyType = ref();
const status = ref();
const currentPage = ref(1);
const rowsPerPage = ref(12);
const totalRecords = ref(0);
const {formatAmount} = transactionFormat();

const sortOptions = ref([
    'latest',
    'largest_fund',
    'most_investors',
]);

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
    getLeaders();
}

const leaders = ref();
const loadingLeaders = ref(false);
const selectedLeaders = ref([]);

const getLeaders = async () => {
    loadingLeaders.value = true;
    try {
        const response = await axios.get('/getLeaders');
        leaders.value = response.data;
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

const getResults = async (page = 1, rowsPerPage = 12) => {
    isLoading.value = true;

    try {
        let url = `/master/getMasters?page=${page}&limit=${rowsPerPage}`;

        if (selectedSort.value && selectedSort.value) {
            url += `&sortType=${selectedSort.value}`;
        }

        if (selectedLeaders.value.length > 0) {
            const groupNames = selectedLeaders.value.map(item => item.id).join(',');
            url += `&leaders=${groupNames}`;
        }

        if (tag.value) {
            url += `&tag=${tag.value}`;
        }

        if (strategyType.value) {
            url += `&strategy_type=${strategyType.value}`;
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

watch(search, debounce(() => {
    getResults(currentPage.value, rowsPerPage.value);
}, 300));

watch([selectedSort, selectedLeaders, tag, strategyType, status], () => {
    getResults(currentPage.value, rowsPerPage.value);
});

const clearSearch = () => {
    search.value = '';
}

const clearAll = () => {
    selectedLeaders.value = [];
    tag.value = null;
    strategyType.value = null;
    status.value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults(currentPage.value, rowsPerPage.value);
    }
});
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
                @click="toggle"
                :disabled="isLoading || masters.length === 0"
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
            <div class="grid grid-cols-1 md:grid-cols-2 3xl:grid-cols-4 gap-5 self-stretch">
                <Card
                    v-for="(master, index) in mastersCount > 12 ? 12 : mastersCount"
                    :key="index"
                >
                    <template #content>
                        <div class="flex flex-col items-center gap-4 self-stretch">
                            <!-- Profile Section -->
                            <div class="w-full flex items-center gap-4 self-stretch">
                                <img
                                    class="object-cover w-8 h-8 rounded-full"
                                    :src="master.profile_photo ? master.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                                    alt="masterPic"
                                />
                                <div class="flex flex-col items-start">
                                    <Skeleton width="10rem" class="my-1"></Skeleton>
                                    <Skeleton width="5rem" class="mt-1"></Skeleton>
                                </div>
                            </div>

                            <!-- StatusBadge Section -->
                            <div class="flex items-center gap-2 self-stretch">
                                <Skeleton width="5rem" height="1.5rem"></Skeleton>
                                <Skeleton width="5rem" height="1.5rem"></Skeleton>
                                <Skeleton width="5rem" height="1.5rem"></Skeleton>
                                <Skeleton width="5rem" height="1.5rem"></Skeleton>
                            </div>

                            <!-- Performance Section -->
                            <div class="py-2 flex justify-center items-center gap-2 self-stretch border-y border-solid border-gray-200 dark:border-gray-700">
                                <div class="w-full flex flex-col items-center">
                                    <Skeleton width="5rem" class="my-1"></Skeleton>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.settlement') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <Skeleton width="5rem" class="my-1"></Skeleton>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.total_pnl') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <Skeleton width="5rem" class="my-1"></Skeleton>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.latest') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="flex items-end justify-between self-stretch">
                                <div class="flex flex-col items-center gap-1 self-stretch w-full">
                                    <div class="py-1 flex items-center gap-3 self-stretch w-full text-gray-500">
                                        <IconUserDollar size="20" stroke-width="1.25" />
                                        <Skeleton width="5rem"></Skeleton>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch text-gray-500">
                                        <IconPremiumRights size="20" stroke-width="1.25" />
                                        <Skeleton width="5rem"></Skeleton>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <div class="min-w-5 text-gray-500">
                                            <IconScanEye size="20" stroke-width="1.25" />
                                        </div>
                                        <Skeleton width="5rem"></Skeleton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
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
                                    class="object-cover w-8 h-8 rounded-full"
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
                                    <MasterListAction
                                        :master="master"
                                    />
                                </div>
                            </div>

                            <!-- StatusBadge Section -->
                            <div class="flex items-center gap-2 self-stretch">
                                <Tag severity="primary">
                                    {{ master.min_join_equity > 0 ? '$ ' + formatAmount(master.min_join_equity, 0) : $t('public.no_min') }}
                                </Tag>
                                <Tag severity="secondary">
                                    <div v-if="master.join_period && master.join_period !== 0">
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
                                <Tag
                                    :severity="master.strategy_type === 'HOFI' ? 'warn' : 'info'"
                                    :value="master.strategy_type"
                                />
                            </div>

                            <!-- Performance Section -->
                            <div class="py-2 flex justify-center items-center gap-2 self-stretch border-y border-solid border-gray-200 dark:border-gray-700">
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-gray-950 dark:text-white text-center font-semibold">
                                        {{ formatAmount(master.roi_period, 0) }} {{ $t('public.days') }}
                                    </div>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.settlement') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-gray-950 dark:text-white text-center font-semibold">
                                        $ {{ formatAmount(master.close_trades_sum_trade_profit) }}
                                    </div>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.total_pnl') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-center font-semibold">
                                        <div
                                            v-if="master.latest_profit !== 0"
                                            :class="(master.latest_profit < 0) ? 'text-error-500' : 'text-success-500'"
                                        >
                                            $ {{ formatAmount(master.latest_profit) }}
                                        </div>
                                        <div
                                            v-else
                                            class="text-gray-950 dark:text-white"
                                        >
                                            -
                                        </div>
                                    </div>
                                    <div class="self-stretch text-gray-500 text-center text-xs">
                                        {{ $t('public.latest') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="flex items-end justify-between self-stretch">
                                <div class="flex flex-col items-center gap-1 self-stretch w-full">
                                    <div class="py-1 flex items-center gap-3 self-stretch w-full text-gray-500">
                                        <IconUserDollar size="20" stroke-width="1.25" />
                                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                                            {{ master.active_copy_trades_count + master.active_pamm_count }} {{ $t('public.investors') }}
                                        </div>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch text-gray-500">
                                        <IconPremiumRights size="20" stroke-width="1.25" />
                                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                                            <span class="text-primary-500">$ {{ formatAmount(master.active_copy_trades_sum_subscribe_amount ?? 0 + master.active_pamm_sum_subscription_amount ?? 0) }}</span> {{ $t('public.fund_capital') }}
                                        </div>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <div class="min-w-5 text-gray-500">
                                            <IconScanEye size="20" stroke-width="1.25" />
                                        </div>
                                        <div class="text-gray-950 dark:text-white text-sm font-medium truncate">
                                            <span v-if="master.is_public">{{ master.leaders_names || $t('public.public') }}</span>
                                            <span v-else>{{ master.leaders_names || '-' }}</span>
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

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Leader-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_leaders')}}
                </div>
                <MultiSelect
                    v-model="selectedLeaders"
                    :options="leaders"
                    optionLabel="name"
                    filter
                    :filter-fields="['name', 'email']"
                    :placeholder="$t('public.select_leaders')"
                    :maxSelectedLabels="3"
                    class="w-full"
                />
            </div>

            <!-- Filter tags -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_tags')}}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <Checkbox v-model="tag" inputId="no_min_investment" value="no_min_investment" />
                        <label for="no_min_investment">{{ $t('public.no_min') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <Checkbox v-model="tag" inputId="lock_free" value="lock_free" />
                        <label for="lock_free">{{ $t('public.lock_free') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <Checkbox v-model="tag" inputId="zero_fee" value="zero_fee" />
                        <label for="zero_fee">{{ $t('public.zero_fee') }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter strategy -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_strategy')}}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="strategyType" inputId="strategy_type_hofi" value="HOFI" class="w-4 h-4" />
                        <label for="strategy_type_hofi">HOFI</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="strategyType" inputId="strategy_type_alpha" value="Alpha" class="w-4 h-4" />
                        <label for="strategy_type_alpha">Alpha</label>
                    </div>
                </div>
            </div>

            <!-- Filter status -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_status')}}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="status" inputId="status_active" value="Active" class="w-4 h-4" />
                        <label for="status_active">{{ $t('public.active') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="status" inputId="status_inactive" value="Inactive" class="w-4 h-4" />
                        <label for="status_inactive">{{ $t('public.inactive') }}</label>
                    </div>
                </div>
            </div>

            <Button
                type="button"
                severity="info"
                class="w-full"
                outlined
                @click="clearAll"
            >
                Clear All
            </Button>
        </div>
    </Popover>
</template>
