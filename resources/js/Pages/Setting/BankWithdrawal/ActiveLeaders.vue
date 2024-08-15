<script setup>
import BaseListbox from "@/Components/BaseListbox.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import debounce from "lodash/debounce.js";
import NoData from "@/Components/NoData.vue";
import {h, ref, watch, watchEffect, onMounted } from "vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import UpdateBankWithdrawal from "@/Pages/Setting/BankWithdrawal/UpdateBankWithdrawal.vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    search: String,
})

const leaders = ref({data: []});
const sorting = ref();
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const status = ref('Active');

const getResults = async (page = 1, paginate = pageSize.value, filter = '', withdrawal_status = status.value, columnName = sorting.value) => {
    try {
        let url = `/setting/getLeaders?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filter) {
            url += `&search=${filter}`;
        }

        if (withdrawal_status) {
            url += `&status=${withdrawal_status}`;
        }

        if (columnName) {
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        leaders.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

getResults();

const columns = [
    {
        accessorKey: 'name',
        header: 'name',
    },
    {
        accessorKey: 'email',
        header: 'email',
    },
    {
        accessorKey: 'enable_bank_withdrawal',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => {
        const statusMapping = {
            1: 'Active',
            0: 'Inactive'
        };
        const statusValue = row.original.enable_bank_withdrawal;
        const statusString = statusMapping[statusValue] || 'Unknown';
        return h(StatusBadge, { value: statusString });
    },
    },
    {
        accessorKey: 'action',
        header: 'action',
        enableSorting: false,
        cell: ({ row }) => h(UpdateBankWithdrawal, {
            user_id: row.original.id,
            enable_bank_withdrawal: row.original.enable_bank_withdrawal,
        }),
    },
];

watch([currentPage, action], ([currentPageValue, newAction]) => {
    if (newAction === 'goToFirstPage' || newAction === 'goToLastPage') {
        getResults(currentPageValue, pageSize.value, props.search, status.value, sorting.value);
    } else {
        getResults(currentPageValue, pageSize.value, props.search, status.value, sorting.value);
    }
});

watch(
    [sorting, pageSize],
    ([sortingValue, pageSizeValue]) => {
        getResults(currentPage.value, pageSizeValue, props.search, status.value, sortingValue);
    }
);

watch([() => props.search, status], debounce(([searchValue, statusValue]) => {
    getResults(currentPage.value, pageSize.value, searchValue, statusValue, sorting.value);
}, 300));

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults(currentPage.value, pageSize.value, props.search, status.value, sorting.value);
    }
});
</script>

<template>
    <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
        <div
            v-if="leaders.data.length === 0"
            class="w-full flex items-center justify-center"
        >
            <NoData />
        </div>
        <div v-else>
            <TanStackTable
                :data="leaders"
                :columns="columns"
                @update:sorting="sorting = $event"
                @update:action="action = $event"
                @update:currentPage="currentPage = $event"
            />
        </div>
    </div>
</template>
