<script setup>
import BaseListbox from "@/Components/BaseListbox.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import NoData from "@/Components/NoData.vue";
import {h, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import ColumnName from "@/Components/TanstackTable/ColumnName.vue";
import Action from "@/Pages/Transaction/TransactionPending/Partials/Action.vue";

const props = defineProps({
    search: String,
    leader: Object,
    date: String,
    type: String,
    exportStatus: String,
})

const pageSize = ref(10);
const sorting = ref();
const pendingWithdrawals = ref({data: []});
const action = ref('');
const currentPage = ref(1);
const { formatDateTime, formatAmount } = transactionFormat();
const emit = defineEmits(['update:totalPendingDeposits', 'update:totalPendingWithdrawals', 'update:totalRejectedRequest', 'update:exportStatus'])

const pageSizes = [
    {value: 5, label: 5 },
    {value: 10, label: 10 },
    {value: 20, label: 20 },
    {value: 50, label: 50 },
    {value: 100, label: 100 },
]

const getResults = async (page = 1, paginate = 10,  search = '', leader = null, date = '', columnName = sorting.value) => {
    try {
        let url = `/transaction/getPendingTransaction/Withdrawal?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (search) {
            url += `&search=${search}`;
        }

        if (leader) {
            url += `&leader=${leader.value}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        pendingWithdrawals.value = response.data[props.type];
        emit('update:totalPendingDeposits', response.data.totalPendingDeposits);
        emit('update:totalPendingWithdrawals', response.data.totalPendingWithdrawals);
    } catch (error) {
        console.error(error);
    }
}

getResults();

const columns = [
    {
        accessorKey: 'created_at',
        header: 'date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'user',
        header: 'name',
        enableSorting: false,
        cell: ({ row }) => h(ColumnName, {
            user: row.original.user,
        }),
    },
    {
        accessorKey: 'user.first_leader',
        header: 'first_leader',
        cell: info => info.getValue(),
        enableSorting: false,
    },
    {
        accessorKey: 'from_wallet.name',
        header: 'from',
        enableSorting: false,
    },
    {
        accessorKey: 'transaction_number',
        header: 'transaction_id',
    },
    {
        accessorKey: 'payment_method',
        header: 'payment_method',
        enableSorting: false,
    },
    {
        accessorKey: 'to_wallet_address',
        header: 'usdt_address_bank_acc',
        enableSorting: false,
    },
    {
        accessorKey: 'amount',
        header: 'amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'transaction_charges',
        header: 'payment_charges',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'transaction_amount',
        header: 'transaction_amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'action',
        header: 'action',
        enableSorting: false,
        cell: ({ row }) => h(Action, {
            transaction: row.original,
        }),
    },
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
        getResults(1, pageSizeValue, props.search, props.leader, props.date, sortingValue);
    }
);


watch(
    [() => props.search, () => props.leader, () => props.date],
    debounce(([searchValue, leaderValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, leaderValue, dateValue, sorting.value);
    }, 300)
);

const exportTransactions = () => {
    let url = `/transaction/getPendingTransaction/Withdrawal?exportStatus=yes`;

    if (props.search) {
        url += `&search=${props.search}`;
    }

    if (props.leader) {
        url += `&leader=${props.leader.value}`;
    }

    if (props.date) {
        url += `&date=${props.date}`;
    }

    window.location.href = url;
}

watch(
    () => props.exportStatus,
    (newStatus) => {
        if (newStatus === 'yes') {
            exportTransactions();
            emit('update:exportStatus', '');
        }
    }
);

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
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
            v-if="pendingWithdrawals.data.length === 0"
            class="w-full flex items-center justify-center"
        >
            <NoData />
        </div>
        <div v-else>
            <TanStackTable
                :data="pendingWithdrawals"
                :columns="columns"
                @update:sorting="sorting = $event"
                @update:action="action = $event"
                @update:currentPage="currentPage = $event"
            />
        </div>
    </div>
</template>
