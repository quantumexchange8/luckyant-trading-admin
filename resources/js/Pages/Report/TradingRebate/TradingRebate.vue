<script setup>
import {h, ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import {CloudDownloadIcon} from "@/Components/Icons/outline.jsx";
import BaseListbox from "@/Components/BaseListbox.vue";
import {usePage} from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import Button from "@/Components/Button.vue";
import NoData from "@/Components/NoData.vue";
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import TanStackTable from "@/Components/TanStackTable.vue";

const tradingRebates = ref({data: []});
const sorting = ref();
const search = ref('');
const date = ref('');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const currentLocale = ref(usePage().props.locale);

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
        tradingRebates.value = response.data;
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
        header: 'Date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'upline_user.name',
        header: 'Name',
        enableSorting: false,
    },
    {
        accessorKey: 'upline_user.email',
        header: 'Email',
        enableSorting: false,
    },
    {
        accessorKey: 'user.name',
        header: 'Affiliate',
        enableSorting: false,
    },
    {
        accessorKey: 'user.email',
        header: 'Affiliate Email',
        enableSorting: false,
    },
    {
        accessorKey: 'volume',
        header: 'Trade Volume',
    },
    {
        accessorKey: 'rebate',
        header: 'Total Rebate ($)'
    },
    {
        accessorKey: 'status',
        header: 'Status',
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

        <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="flex flex-col sm:flex-row gap-2 sm:justify-between sm:items-center w-full">
                <div class="flex flex-col md:flex-row gap-4 w-full sm:w-1/2">
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
                    <div class="w-full">
                        <vue-tailwind-datepicker
                            placeholder="Select dates"
                            :formatter="formatter"
                            separator=" - "
                            v-model="date"
                            input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                        />
                    </div>
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
