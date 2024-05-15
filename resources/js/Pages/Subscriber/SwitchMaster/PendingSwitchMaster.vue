<script setup>
import BaseListbox from "@/Components/BaseListbox.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import NoData from "@/Components/NoData.vue";
import {h, ref, watch, watchEffect} from "vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import {transactionFormat} from "@/Composables/index.js";
import PendingAction from "@/Pages/Subscriber/SwitchMaster/PendingAction.vue";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    search: String,
    leader: Object,
    date: String,
    status: String,
})

const pageSize = ref(10);
const sorting = ref();
const pendingSwitches = ref({data: []});
const action = ref('');
const currentPage = ref(1);
const { formatDateTime, formatAmount } = transactionFormat();
const emit = defineEmits(['update:totalRequest', 'update:totalApprovedRequest', 'update:totalRejectedRequest'])

const pageSizes = [
    {value: 5, label: 5 },
    {value: 10, label: 10 },
    {value: 20, label: 20 },
    {value: 50, label: 50 },
    {value: 100, label: 100 },
]

const getResults = async (page = 1, paginate = 10,  search = '', leader = null, date = '', columnName = sorting.value) => {
    try {
        let url = `/subscriber/getSwitchMasterData?status=Pending&page=${page}`;

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
        pendingSwitches.value = response.data.switchMasters;
        emit('update:totalRequest', response.data.totalRequest);
        emit('update:totalApprovedRequest', response.data.totalApprovedRequest);
        emit('update:totalRejectedRequest', response.data.totalRejectedRequest);
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
        accessorKey: 'user.name',
        header: 'subscriber',
        enableSorting: false,
    },
    {
        accessorKey: 'meta_login',
        header: 'trading_account',
    },
    {
        accessorKey: 'first_leader.name',
        header: 'first_leader',
        cell: info => info.getValue() ?? '-',
        enableSorting: false,
    },
    {
        accessorKey: 'old_master.trading_user.name',
        header: 'current_master',
        enableSorting: false,
    },
    {
        accessorKey: 'new_master.trading_user.name',
        header: 'new_master',
        enableSorting: false,
    },
    {
        accessorKey: 'subscriber.subscribe_amount',
        header: 'amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => h(StatusBadge, {value: row.original.status}),
    },
    {
        accessorKey: 'action',
        header: 'action',
        enableSorting: false,
        cell: ({ row }) => h(PendingAction, {
            switchMaster: row.original,
            status: props.status
        }),
    },
];

watch(
    [() => props.search, () => props.leader, () => props.date],
    debounce(([searchValue, leaderValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, leaderValue, dateValue, sorting.value);
    }, 300)
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
            v-if="pendingSwitches.data.length === 0"
            class="w-full flex items-center justify-center"
        >
            <NoData />
        </div>
        <div v-else>
            <TanStackTable
                :data="pendingSwitches"
                :columns="columns"
                @update:sorting="sorting = $event"
                @update:action="action = $event"
                @update:currentPage="currentPage = $event"
            />
        </div>
    </div>
</template>
