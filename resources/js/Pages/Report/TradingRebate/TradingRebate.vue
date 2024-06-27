<script setup>
import {h, ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import {CloudDownloadIcon, CurrencyDollarCircleIcon, Users01Icon} from "@/Components/Icons/outline.jsx";
import BaseListbox from "@/Components/BaseListbox.vue";
import {usePage} from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import Button from "@/Components/Button.vue";
import NoData from "@/Components/NoData.vue";
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import TanStackTable from "@/Components/TanStackTable.vue";

const totalRebateAmount = ref(null);
const totalTradeLots = ref(null);
const tradingRebates = ref({data: []});
const sorting = ref();
const search = ref('');
const date = ref('');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);

const { formatDateTime, formatAmount } = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const pageSizes = [
    {value: 5, label: 5 },
    {value: 10, label: 10 },
    {value: 20, label: 20 },
    {value: 50, label: 50 },
    {value: 100, label: 100 },
]

watch([currentPage, action], ([currentPageValue, newAction]) => {
    if (newAction === 'goToFirstPage' || newAction === 'goToLastPage') {
        getResults(currentPageValue, pageSize.value);
    } else {
        getResults(currentPageValue, pageSize.value);
    }
});

watch(
    [sorting, pageSize],
    ([sortingValue, pageSizeValue]) => {
        getResults(1, pageSizeValue, search.value, date.value, sortingValue);
    }
);

watch(
    [search, date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, dateValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value,filterDate = date.value, columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/report/getTradingRebate?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
        }

        if (filterDate) {
            url += `&date=${filterDate}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        tradingRebates.value = response.data.result;
        totalRebateAmount.value = response.data.totalRebateAmount;
        totalTradeLots.value = response.data.totalTradeLots;
    } catch (error) {
        console.error(error);
    } finally {
        // isLoading.value = false
    }
}

getResults()

const columns = [
    {
        accessorKey: 'created_at',
        header: 'date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'upline_user.name',
        header: 'name',
        enableSorting: false,
    },
    {
        accessorKey: 'upline_user.email',
        header: 'email',
        enableSorting: false,
    },
    {
        accessorKey: 'user.name',
        header: 'affiliate',
        enableSorting: false,
    },
    {
        accessorKey: 'user.email',
        header: 'affiliate_email',
        enableSorting: false,
    },
    {
        accessorKey: 'volume',
        header: 'trade_volume',
    },
    {
        accessorKey: 'rebate',
        header: 'total_rebate',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => h(Badge, {status: row.original.status}),
    },
];

const exportReport = () => {
    let url = `/report/getTradingRebate?exportStatus=yes`;

    if (search.value) {
        url += `&search=${search.value}`;
    }

    if (date.value) {
        url += `&date=${date.value}`;
    }

    window.location.href = url;
}

const clearFilter = () => {
    search.value = '';
    date.value = ''
}
</script>

<template>
    <AuthenticatedLayout title="Trading Rebate">
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Trading Rebate
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Maximize Returns: Explore Your Trading Rebates Report
                    </p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Rebate Amount
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalRebateAmount !== null">
                          $ {{ formatAmount(totalRebateAmount ? totalRebateAmount : 0) }}
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
                        Total Trade Lot
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalTradeLots !== null">
                            {{ formatAmount(totalTradeLots ? totalTradeLots : 0) }}
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

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full">
                <div class="w-full">
                    <InputIconWrapper class="w-full">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input
                            withIcon
                            id="search"
                            type="text"
                            class="block w-full"
                            placeholder="Search"
                            v-model="search"
                        />
                    </InputIconWrapper>
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                    />
                </div>
                <div class="flex justify-end gap-4 items-center w-full">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="clearFilter"
                    >
                        <span class="text-lg">Clear</span>
                    </Button>
                    <Button
                        type="button"
                        variant="gray"
                        class="flex gap-1 justify-center"
                        v-slot="{ iconSizeClasses }"
                        @click="exportReport"
                    >
                        <CloudDownloadIcon class="w-5 h-5" />
                        Export
                    </Button>
                </div>
            </div>
        </div>

        <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="flex justify-end items-center gap-2">
                <div class="text-sm">
                    Size
                </div>
                <div>
                    <BaseListbox
                        :options="pageSizes"
                        v-model="pageSize"
                    />
                </div>
            </div>
            <div
                v-if="tradingRebates.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData />
            </div>
            <div v-else>
                <TanStackTable
                    :data="tradingRebates"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
