<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {h, ref, watch} from "vue";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Combobox from "@/Components/Combobox.vue";
import {CloudDownloadIcon, Coins01Icon, CreditCardCheckIcon, CreditCardXIcon} from "@/Components/Icons/outline.jsx";
import BaseListbox from "@/Components/BaseListbox.vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import ColumnName from "@/Components/TanstackTable/ColumnName.vue";
import {trans} from "laravel-vue-i18n";
import TransactionHistoryAction from "@/Pages/Transaction/TransactionHistory/TransactionHistoryAction.vue";

const props = defineProps({
    transactionTypes: Array,
})

const transactions = ref({data: []});
const sorting = ref();
const search = ref('');
const leader = ref();
const date = ref('');
const methods = ref('');
const fund_type = ref('');
const transactionStatus = ref('');
const transactionType = ref('Deposit');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const totalAmount = ref(null);
const successAmount = ref(null);
const rejectedAmount = ref(null);

const {formatDateTime, formatAmount} = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const pageSizes = [
    {value: 5, label: 5},
    {value: 10, label: 10},
    {value: 20, label: 20},
    {value: 50, label: 50},
    {value: 100, label: 100},
]

const statusOptions = [
    {value: '', label: 'All'},
    {value: 'Success', label: 'Success'},
    {value: 'Rejected', label: 'Rejected'},
]

const fundTypes = [
    {value: '', label:"All"},
    {value: 'DemoFund', label:"Demo Fund"},
    {value: 'RealFund', label:"Real Fund"},
];

const paymentMethods = [
    {value: '', label:"All"},
    {value: 'Bank', label:"Bank"},
    {value: 'Crypto', label:"Crypto"},
    {value: 'Payment Merchant', label:"Payment Merchant"},
];

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
        getResults(1, pageSizeValue, search.value, leader.value, date.value, fund_type.value.value, methods.value.value, transactionStatus.value.value, transactionType.value.value, sortingValue);
    }
);

watch(
    [search, leader, date, fund_type, methods, transactionStatus, transactionType],
    debounce(([searchValue, leaderValue, dateValue, fundTypeValue, methodsValue, transactionStatusValue, transactionTypeValue]) => {
        getResults(1, pageSize.value, searchValue, leaderValue, dateValue, fundTypeValue, methodsValue, transactionStatusValue, transactionTypeValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterLeader = leader.value, filterDate = date.value, filterFundType = fund_type.value, filterMethods = methods.value, filterTransactionStatus = transactionStatus.value,  filterTransactionType = transactionType.value, columnName = sorting.value) => {
    try {
        let url = `/transaction/getTransactionHistory?page=${page}`;

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

        if (filterFundType) {
            url += `&fund_type=${filterFundType}`;
        }

        if (filterMethods) {
            url += `&methods=${filterMethods}`;
        }

        if (filterTransactionStatus) {
            url += `&status=${filterTransactionStatus}`;
        }

        if (filterTransactionType) {
            url += `&type=${filterTransactionType}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data.transactions;
        totalAmount.value = response.data.totalAmount;
        successAmount.value = response.data.successAmount;
        rejectedAmount.value = response.data.rejectedAmount;
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
        cell: ({row}) => h(ColumnName, {
            user: row.original.user,
        }),
    },
    {
        accessorKey: 'first_leader',
        header: 'first_leader',
        cell: info => info.getValue() ?? '-',
        enableSorting: false,
    },
    {
        accessorFn: row => row.from_wallet?.type || row.from_meta_login?.meta_login,
        header: 'from',
        enableSorting: false,
        cell: info => {
            const fromWallet = info.row.original.from_wallet;
            const fromMetaLogin = info.row.original.from_meta_login;
            const isTransfer = info.row.original.transaction_type === 'Transfer';
            const isPerformanceIncentive = info.row.original.transaction_type === 'PerformanceIncentive';

            if (isPerformanceIncentive) {
                return '-';
            } else if (fromWallet) {
                const name = fromWallet.user?.name || '';
                return isTransfer ? `${name ? name + ' - ' : ''}${fromWallet.wallet_address}` : trans('public.' + info.getValue());
            } else {
                return fromMetaLogin ? `${trans('public.account_no')} - ${info.getValue()}` : '-';
            }
        }
    },
    {
        accessorFn: row => row.to_wallet?.type || row.to_meta_login?.meta_login,
        header: 'to',
        enableSorting: false,
        cell: info => {
            const isTransfer = info.row.original.transaction_type === 'Transfer';
            const toWallet = info.row.original.to_wallet;
            const toMetaLogin = info.row.original.to_meta_login;
            const isWalletWithdrawal = info.row.original.category === 'wallet' && info.row.original.transaction_type === 'Withdrawal';

            if (isWalletWithdrawal) {
                return `${info.row.original.payment_account.payment_account_name} - ${info.row.original.payment_account.account_no}`;
            } else if (toWallet) {
                const name = toWallet.user?.name || '';
                return isTransfer ? `${name ? name + ' - ' : ''}${toWallet.wallet_address}` : trans('public.' + info.getValue());
            } else {
                return toMetaLogin ? `${trans('public.account_no')} - ${info.getValue()}` : '-';
            }
        }
    },
    {
        accessorKey: 'transaction_number',
        header: 'transaction_no',
    },
    {
        accessorKey: 'amount',
        header: 'amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'fund_type',
        header: 'fund_type',
        enableSorting: false,
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({row}) => h(StatusBadge, {value: row.original.status}),
    },
    {
        accessorKey: 'table_action',
        header: 'action',
        enableSorting: false,
        cell: ({row}) => h(TransactionHistoryAction, {transaction: row.original}),
    },
];

const exportSubscribers = () => {
    let url = `/transaction/getTransactionHistory?exportStatus=yes`;

    if (search.value) {
        url += `&search=${search.value}`;
    }

    if (leader.value) {
        url += `&leader=${leader.value.value}`;
    }

    if (date.value) {
        url += `&date=${date.value}`;
    }

    if (fund_type.value) {
        url += `&fund_type=${fund_type.value}`;
    }

    if (methods.value) {
        url += `&methods=${methods.value}`;
    }

    if (transactionStatus.value) {
        url += `&status=${transactionStatus.value}`;
    }


    if (transactionType.value) {
        url += `&type=${transactionType.value}`;
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
    transactionStatus.value = '';
}
</script>

<template>
    <AuthenticatedLayout title="Transaction History">
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Transaction History
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Track all transaction history carried out by your members.
                    </p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Amount
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalAmount !== null">
                          $ {{ formatAmount(totalAmount ? totalAmount : 0) }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                    <Coins01Icon class="text-primary-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Success Amount
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="successAmount !== null">
                          $ {{ formatAmount(successAmount ? successAmount : 0) }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <CreditCardCheckIcon class="text-success-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Rejected Amount
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="rejectedAmount !== null">
                          $ {{ formatAmount(rejectedAmount ? rejectedAmount : 0) }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-error-200">
                    <CreditCardXIcon class="text-error-500 w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full">
                <div class="w-full col-span-3 md:col-span-1">
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
                        id="fundType"
                        v-model="fund_type"
                        :options="fundTypes"
                        placeholder="Filter Status"
                    />
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <BaseListbox
                        id="paymentMethod"
                        v-model="methods"
                        :options="paymentMethods"
                        placeholder="Filter Status"
                    />
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <BaseListbox
                        id="transactionStatus"
                        v-model="transactionStatus"
                        :options="statusOptions"
                        placeholder="Filter Status"
                    />
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <BaseListbox
                        id="transactionType"
                        v-model="transactionType"
                        :options="transactionTypes"
                        placeholder="Filter Type"
                    />
                </div>
                <div class="flex justify-end gap-4 col-span-3 md:col-span-2 items-center w-full">
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
                v-if="transactions.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData/>
            </div>
            <div v-else>
                <TanStackTable
                    :data="transactions"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
