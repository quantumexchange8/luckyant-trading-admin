<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {h, ref, watch} from "vue";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Combobox from "@/Components/Combobox.vue";
import {CloudDownloadIcon, Tag01Icon, UsersCheckIcon, CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";
import BaseListbox from "@/Components/BaseListbox.vue";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Badge from "@/Components/Badge.vue";
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import StatusBadge from "@/Components/StatusBadge.vue";

const subscribers = ref({data: []});
const sorting = ref();
const search = ref('');
const leader = ref();
const date = ref('');
const subscriberStatus = ref('');
const packageProduct = ref('');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const currentLocale = ref(usePage().props.locale);
const totalSubscriptions = ref(null);
const totalCopyTradeBalance = ref(null);

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

const statusOptions = [
    {value: '', label: 'All' },
    {value: 'Active', label: 'Active' },
    {value: 'Expiring', label: 'Expiring' },
    {value: 'Terminated', label: 'Terminated' },
]

const productOptions = [
    {value: '', label: 'All' },
    {value: 'vimjuice', label: 'vimjuice' },
    {value: '沉香树', label: '沉香树' },
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
        getResults(1, pageSizeValue, search.value, leader.value, date.value, subscriberStatus.value.value, sortingValue);
    }
);

watch(
    [search, leader, date, subscriberStatus, packageProduct],
    debounce(([searchValue, leaderValue, dateValue, subscriberStatusValue, packageProductValue]) => {
        getResults(1, pageSize.value, searchValue, leaderValue, dateValue, subscriberStatusValue, packageProductValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterLeader = leader.value, filterDate = date.value, filterSubscriberStatus = subscriberStatus.value, filterPackageProduct = packageProduct.value, columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/pamm/getPammListingData?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
        }

        if (filterLeader) {
            url += `&leader=${leader.value.value}`;
        }

        if (filterDate) {
            url += `&date=${filterDate}`;
        }

        if (filterSubscriberStatus) {
            url += `&status=${filterSubscriberStatus}`;
        }

        if (filterPackageProduct) {
            url += `&product=${filterPackageProduct}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        subscribers.value = response.data.subscribers;
        totalSubscriptions.value = response.data.totalSubscriptions;
        totalCopyTradeBalance.value = response.data.totalCopyTradeBalance;
    } catch (error) {
        console.error(error);
    } finally {
        // isLoading.value = false
    }
}

getResults()

const columns = [
    {
        accessorKey: 'approval_date',
        header: 'approval_date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'user.name',
        header: 'subscriber',
        enableSorting: false,
        cell: ({ row }) => row.original.user.name + ' - ' +row.original.meta_login,
    },
    {
        accessorKey: 'first_leader.name',
        header: 'first_leader',
        cell: info => info.getValue() ?? '-',
        enableSorting: false,
    },
    {
        accessorKey: 'subscription_package_product',
        header: 'package',
        enableSorting: false,
        cell: ({ row }) => row.original.package ? '$ ' + formatAmount(row.original.package.amount, 0) + ' - ' + row.original.subscription_package_product : '-',
    },
    {
        accessorKey: 'master.trading_user.name',
        header: 'master',
        enableSorting: false,
    },
    {
        accessorKey: 'master_meta_login',
        header: 'account_no',
    },
    {
        accessorKey: 'subscription_amount',
        header: 'fund',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'type',
        header: 'type',
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => h(StatusBadge, {value: row.original.status}),
    },
];

const exportSubscribers = () => {
    let url = `/pamm/getPammListingData?exportStatus=yes`;

    if (search.value) {
        url += `&search=${search.value}`;
    }

    if (leader.value) {
        url += `&leader=${leader.value.value}`;
    }

    if (date.value) {
        url += `&date=${date.value}`;
    }

    if (subscriberStatus.value) {
        url += `&status=${subscriberStatus.value}`;
    }

    if (packageProduct.value) {
        url += `&product=${packageProduct.value}`;
    }

    window.location.href = url;
}

function loadUsers(query, setOptions) {
    fetch('/member/getAllLeaders?query=' + query)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(user => {
                    return {
                        value: user.id,
                        label: user.name,
                        img: user.profile_photo
                    }
                })
            )
        });
}

const clearFilter = () => {
    search.value = '';
    date.value = '';
    leader.value = null;
    subscriberStatus.value = '';
    packageProduct.value = '';
}
</script>

<template>
    <AuthenticatedLayout title="PAMM Listing">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        PAMM Listing
                    </h2>
                    <!-- <p class="text-base font-normal dark:text-gray-400">
                        Manage all pending subscribers.
                    </p> -->
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Subscriptions
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalSubscriptions !== null">
                            {{ totalSubscriptions }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                    <Tag01Icon class="text-primary-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Fund Size
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalCopyTradeBalance !== null">
                          $ {{ formatAmount(totalCopyTradeBalance ? totalCopyTradeBalance : 0) }}
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
                <div class="w-full col-span-3 md:col-span-1">
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
                    <Combobox
                        :load-options="loadUsers"
                        v-model="leader"
                        placeholder="Leader"
                        image
                    />
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
                        id="product"
                        v-model="packageProduct"
                        :options="productOptions"
                        placeholder="Filter Product"
                    />
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <BaseListbox
                        id="statusID"
                        v-model="subscriberStatus"
                        :options="statusOptions"
                        placeholder="Filter Status"
                    />
                </div>
                <div class="col-span-3 md:col-span-1 flex justify-end gap-4 items-center w-full">
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
                v-if="subscribers.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData />
            </div>
            <div v-else>
                <TanStackTable
                    :data="subscribers"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
