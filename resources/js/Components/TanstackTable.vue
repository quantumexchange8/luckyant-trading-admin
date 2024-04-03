<script setup>
import {
    useVueTable,
    FlexRender,
    getCoreRowModel,
    getSortedRowModel
} from "@tanstack/vue-table";
import {ref, watch, h} from "vue";
import Button from "@/Components/Button.vue";
import Loading from "@/Components/Loading.vue";
import {transactionFormat} from "@/Composables/index.js";
import BaseListbox from "@/Components/BaseListbox.vue";
import {
    ArrowsDownIcon,
    ArrowsUpIcon,
    SwitchVertical01Icon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronLeftDoubleIcon,
    ChevronRightDoubleIcon, CloudDownloadIcon,
} from "./Icons/outline.jsx";
import Badge from "@/Components/Badge.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import debounce from "lodash/debounce.js";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon} from "@heroicons/vue/outline";
const { formatDateTime, formatAmount } = transactionFormat();

const members = ref({data: []});
const sorting = ref([]);
const search = ref('');
const date = ref('');

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

const pageSize = ref(10);

watch(
    [pageSize, search, date],
    debounce(([pageSizeValue, searchValue, dateValue]) => {
        getResults(table.getState().pagination.pageIndex, pageSizeValue, searchValue, dateValue)
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value,filterDate = date.value, columnName = null) => {
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
        accessorKey: 'trade_volume',
        header: 'Trade Volume',
    },
    {
        accessorKey: 'net_rebate_amt',
        header: 'Rebate ($)'
    },
    {
        accessorKey: 'rebate_final_amt_get',
        header: 'Total Rebate ($)'
    },
    {
        accessorKey: 'is_claimed',
        header: 'Status',
        enableSorting: false,
        cell: ({ row }) => h(Badge, {status: row.original.is_claimed}),
    },
];

let table = useVueTable({
    data: members.value.data,
    columns: columnsMembers,
    getCoreRowModel: getCoreRowModel(),
});

watch(members, (newMembers) => {
    // Update table data when members value changes
    table = useVueTable({
        data: newMembers.data,
        columns: columnsMembers,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        manualPagination: true,
        manualSorting: true,
        rowCount: newMembers.total,
        state: {
            pagination: {
                pageIndex: newMembers.current_page - 1,
                pageSize: newMembers.per_page
            },
            get sorting() {
                return sorting.value
            }
        },
        onSortingChange: updaterOrValue => {
            sorting.value =
                typeof updaterOrValue === 'function'
                    ? updaterOrValue(sorting.value)
                    : updaterOrValue

            getResults(table.getState().pagination.pageIndex, pageSize.value, search.value, date.value, sorting.value[0])
        },
        autoResetPage: false,
    });
});

const handlePageChange = (action, pageIndex) => {
    // setPagination
    if (action === 'previous') {
        getResults(pageIndex, pageSize.value)
    } else {
        getResults(pageIndex + 2, pageSize.value)
    }
}

const handleFirstLastPage = (action) => {
    if (action === 'first') {
        getResults(1, pageSize.value)
    } else {
        getResults(table.getPageCount(), pageSize.value)
    }
}

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
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div class="flex justify-between items-center w-full">
            <div class="flex gap-4 w-1/2">
                <div class="w-full">
                    <InputIconWrapper class="w-full">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
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
            <div class="flex items-center gap-2">
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

<!--        <div v-if="isLoading" class="w-full flex justify-center my-8">-->
<!--            <Loading />-->
<!--        </div>-->
        <table v-if="members.data.length > 0" class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <th
                    v-for="header in headerGroup.headers"
                    scope="col"
                    class="p-3"
                    :class="{
                        'cursor-pointer select-none': header.column.getCanSort(),
                        'bg-primary-100': header.column.getIsSorted()
                    }"
                    @click="header.column.getToggleSortingHandler()?.($event)"
                >
                    <div class="flex items-center gap-2">
                        <FlexRender
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                        <div v-if="header.column.getIsSorted()">
                            <ArrowsUpIcon
                                v-if="header.column.getIsSorted() === 'asc'"
                                class="w-4 h-4 text-primary-600 dark:text-white"
                            />
                            <ArrowsDownIcon
                                v-else-if="header.column.getIsSorted() === 'desc'"
                                class="w-4 h-4 text-primary-600 dark:text-white"
                            />
                        </div>
                        <div v-else>
                            <SwitchVertical01Icon v-if="header.column.getCanSort()" class="w-4 h-4" />
                        </div>
                    </div>
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
        <div class="py-4 flex items-center justify-between w-full">
            <div class="text-sm">
                Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }} - {{ table.getRowCount() }} results
            </div>

            <div class="flex items-center gap-4">
                <Button
                    variant="gray"
                    type="button"
                    @click="handleFirstLastPage('first')"
                    pill
                    size="sm"
                    v-slot="{iconSizeClasses}"
                >
                    <ChevronLeftDoubleIcon class="w-4" />
                </Button>
                <Button
                    type="button"
                    variant="gray"
                    @click="handlePageChange('previous', table.getState().pagination.pageIndex)"
                    :disabled="!table.getCanPreviousPage()"
                    pill
                    size="sm"
                    v-slot="{iconSizeClasses}"
                >
                    <ChevronLeftIcon class="w-4" />
                </Button>
                <Button
                    type="button"
                    variant="gray"
                    @click="handlePageChange('next', table.getState().pagination.pageIndex)"
                    :disabled="!table.getCanNextPage()"
                    pill
                    size="sm"
                    v-slot="{iconSizeClasses}"
                >
                    <ChevronRightIcon class="w-4" />
                </Button>
                <Button
                    variant="gray"
                    type="button"
                    @click="handleFirstLastPage('last')"
                    pill
                    size="sm"
                    v-slot="{iconSizeClasses}"
                >
                    <ChevronRightDoubleIcon class="w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
