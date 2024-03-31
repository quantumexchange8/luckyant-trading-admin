<script setup>
import {
    useVueTable,
    FlexRender,
    getCoreRowModel
} from "@tanstack/vue-table";
import {ref, watch} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import KycAction from "@/Pages/Member/Partials/KycAction.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import Badge from "@/Components/Badge.vue";
import {SortAscendingIcon} from "@heroicons/vue/solid";
import Button from "@/Components/Button.vue";
import Loading from "@/Components/Loading.vue";
import Action from "@/Pages/Member/Partials/Action.vue";
import {transactionFormat} from "@/Composables/index.js";
const { formatDateTime, formatAmount } = transactionFormat();

const members = ref([]);

const getResults = async (page = 1, search = '', rank = '', date = '', type = '', sortType = '', sort = '') => {
    // isLoading.value = true
    try {
        let url = `/member/getMemberDetails?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (rank) {
            url += `&rank=${rank}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (sortType) {
            url += `&sortType=${sortType}`;
            url += `&sort=${sort}`;
        }

        const response = await axios.get(url);
        members.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        // isLoading.value = false
    }
}

getResults()

const columnsMembers = [
    {
        accessorKey: 'name',
        header: 'Name'
    },
    {
        accessorKey: 'created_at',
        header: 'Joining Date',
        cell: info => formatDateTime(info.getValue())
    },
    // {
    //     accessorKey: 'mt5_account',
    //     header: 'MT5 Account'
    // },
    {
        accessorKey: 'top_leader.name',
        header: 'First Leader'
    },
    // {
    //     accessorKey: 'wallet_balance',
    //     header: 'Wallet Balance'
    // },
    {
        accessorKey: 'country.name',
        header: 'Country'
    },
    // {
    //     accessorKey: 'rank',
    //     header: 'Rank'
    // },
    {
        accessorKey: 'status',
        header: 'Status'
    },
    {
        accessorKey: 'action',
        header: 'Action'
    },
];

let table = useVueTable({
    data: members.value,
    columns: columnsMembers,
    getCoreRowModel: getCoreRowModel(),
});

watch(members, (newMembers) => {
    // Update table data when members value changes
    table = useVueTable({
        data: newMembers,
        columns: columnsMembers,
        getCoreRowModel: getCoreRowModel(),
    });
});
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
<!--        <div v-if="isLoading" class="w-full flex justify-center my-8">-->
<!--            <Loading />-->
<!--        </div>-->
        <table v-if="members.length > 0" class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <th v-for="header in headerGroup.headers" scope="col" class="p-3">
                    <FlexRender
                        :render="header.column.columnDef.header"
                        :props="header.getContext()"
                    />
                </th>
            </tr>
            </thead>
            <tbody>
            <tr
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                class="bg-white dark:bg-transparent text-xs font-normal text-gray-900 dark:text-white border-b dark:border-gray-800"
            >
                <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="p-3">
                    <FlexRender
                        :render="cell.column.columnDef.cell"
                        :props="cell.getContext()"
                    />
                </td>
            </tr>
            </tbody>
        </table>
<!--        <div class="flex justify-center mt-4" v-if="!isLoading">-->
<!--            <TailwindPagination-->
<!--                :item-classes=paginationClass-->
<!--                :active-classes=paginationActiveClass-->
<!--                :data="members"-->
<!--                :limit=2-->
<!--                @pagination-change-page="handlePageChange"-->
<!--            />-->
<!--        </div>-->
    </div>
</template>
