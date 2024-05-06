<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {h, ref, watch} from "vue";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {CloudDownloadIcon, UsersSquareIcon, UsersCheckIcon, UsersXIcon} from "@/Components/Icons/outline.jsx";
import BaseListbox from "@/Components/BaseListbox.vue";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Action from "@/Pages/Master/MasterListing/Partials/Action.vue";
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import StatusBadge from "@/Components/StatusBadge.vue";

const masters = ref({data: []});
const sorting = ref();
const search = ref('');
const publicStatus = ref('');
const date = ref('');
const subscriberStatus = ref('');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const currentLocale = ref(usePage().props.locale);
const totalMasters = ref(null);
const totalPublicMaster = ref(null);
const totalPrivateMaster = ref(null);

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

const publicStatusSel = [
    {value: '', label: 'All' },
    {value: '1', label: 'Public' },
    {value: '0', label: 'Private' },
]

const statusOptions = [
    {value: '', label: 'All' },
    {value: 'Active', label: 'Active' },
    {value: 'Inactive', label: 'Inactive' },
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
        getResults(1, pageSizeValue, search.value, publicStatus.value, date.value, subscriberStatus.value.value, sortingValue);
    }
);

watch(
    [search, publicStatus, date, subscriberStatus],
    debounce(([searchValue, publicStatusValue, dateValue, subscriberStatusValue]) => {
        getResults(1, pageSize.value, searchValue, publicStatusValue, dateValue, subscriberStatusValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterPublicStatus = publicStatus.value, filterDate = date.value, filterMasterStatus = subscriberStatus.value, columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/master/getAllMaster?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
        }

        if (filterPublicStatus) {
            url += `&publicStatus=${filterPublicStatus}`;
        }

        if (filterDate) {
            url += `&date=${filterDate}`;
        }

        if (filterMasterStatus) {
            url += `&status=${filterMasterStatus}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        masters.value = response.data.masters;
        totalMasters.value = response.data.totalMasters;
        totalPublicMaster.value = response.data.totalPublicMaster;
        totalPrivateMaster.value = response.data.totalPrivateMaster;
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
        accessorKey: 'user.name',
        header: 'name',
        enableSorting: false,
    },
    {
        accessorKey: 'trading_user.name',
        header: 'master',
        cell: info => info.getValue() ?? '-',
        enableSorting: false,
    },
    {
        accessorKey: 'meta_login',
        header: 'account_no',
    },
    {
        accessorKey: 'min_join_equity',
        header: 'min_join_equity',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'sharing_profit',
        header: 'sharing_profit',
        cell: info => formatAmount(info.getValue()) + '%',
    },
    {
        accessorKey: 'is_public',
        header: 'sharing_profit',
        cell: info => info.getValue() ? 'Public' : 'Private',
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({row}) => h(StatusBadge, {value: row.original.status}),
    },
    {
        accessorKey: 'action',
        header: 'action',
        enableSorting: false,
        cell: ({row}) => h(Action, {masters: row.original}),
    },
];

const exportSubscribers = () => {
    let url = `/master/getAllMaster?exportStatus=yes`;

    if (search.value) {
        url += `&search=${search.value}`;
    }

    if (publicStatus.value) {
        url += `&publicStatus=${publicStatus.value.value}`;
    }

    if (date.value) {
        url += `&date=${date.value}`;
    }

    if (subscriberStatus.value) {
        url += `&status=${subscriberStatus.value}`;
    }

    window.location.href = url;
}

const clearFilter = () => {
    search.value = '';
    date.value = '';
    publicStatus.value = '';
    subscriberStatus.value = '';
}
</script>

<template>
    <AuthenticatedLayout title="Master Listing">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Master Listing
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Manage all active master accounts.
                    </p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 w-full gap-4">
            <div
                class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Master Accounts
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalMasters !== null">
                            {{ totalMasters }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                    <UsersSquareIcon class="text-primary-500 w-8 h-8"/>
                </div>
            </div>
            <div
                class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Public Master
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalPublicMaster !== null">
                          {{ totalPublicMaster ? totalPublicMaster : 0 }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-purple-200">
                    <UsersCheckIcon class="text-purple-500 w-8 h-8"/>
                </div>
            </div>
            <div
                class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Private Master
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalPrivateMaster !== null">
                            {{ totalPrivateMaster ? totalPrivateMaster : 0 }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-gray-200">
                    <UsersXIcon class="text-gray-500 w-8 h-8"/>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
                <div class="w-full">
                    <InputIconWrapper class="w-full">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5"/>
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
                <div class="w-full col-span-3 md:col-span-1">
                    <BaseListbox
                        id="statusID"
                        v-model="publicStatus"
                        :options="publicStatusSel"
                        placeholder="Filter Status"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        v-model="subscriberStatus"
                        :options="statusOptions"
                        placeholder="Filter Status"
                    />
                </div>
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
                    @click="exportSubscribers"
                >
                    <CloudDownloadIcon class="w-5 h-5"/>
                    Export
                </Button>
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
                v-if="masters.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData/>
            </div>
            <div v-else>
                <TanStackTable
                    :data="masters"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
