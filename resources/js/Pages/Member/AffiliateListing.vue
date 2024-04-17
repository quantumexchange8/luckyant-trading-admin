<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {
    ArrowsDownIcon,
    ArrowsUpIcon, ChevronLeftDoubleIcon,
    ChevronLeftIcon, ChevronRightDoubleIcon,
    ChevronRightIcon,
    CloudDownloadIcon, SwitchVertical01Icon
} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
import {ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Badge from "@/Components/Badge.vue";
import Combobox from "@/Components/Combobox.vue";
import NoData from "@/Components/NoData.vue";
import {
    useVueTable,
    FlexRender,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    getFilteredRowModel,
} from "@tanstack/vue-table";
import BaseListbox from "@/Components/BaseListbox.vue";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const sorting = ref();
const search = ref('');
const date = ref('');
const pageSize = ref(10);
const firstLeader = ref(null);
const leaderDetail = ref(null);
const refresh = ref(false);
const affiliateSummaries = ref(null)
const { formatDateTime, formatAmount } = transactionFormat();

const pageSizes = [
    {value: 5, label: 5 },
    {value: 10, label: 10 },
    {value: 20, label: 20 },
    {value: 50, label: 50 },
    {value: 100, label: 100 },
]

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

const getResults = async (page = 1, firstLeader = null, search = '', date = '') => {
    try {
        let url = `/member/getAffiliateSummaries?page=${page}`;

        if (firstLeader) {
            url += `&firstLeader=${firstLeader.value}`;
        }

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        affiliateSummaries.value = response.data.children;
        leaderDetail.value = response.data.first_leader;

    } catch (error) {
        console.error(error);
    }
}

watch(
    [firstLeader, search, date],
    debounce(([firstLeaderValue, searchValue, dateValue]) => {
        if (firstLeaderValue) {
            getResults(1, firstLeaderValue, searchValue, dateValue);
        }
    }, 300)
);

const exportSummary = () => {
    if(leaderDetail.value) {

        let url = `/member/getAffiliateSummaries?exportStatus=yes`;

        if (firstLeader) {
            url += `&firstLeader=${firstLeader.value.value}`;
        }

        if (search.value) {
            url += `&search=${search.value}`;
        }

        if (date.value) {
            url += `&date=${date.value}`;
        }

        window.location.href = url;
    }
}

const kycVariant = (kycApprovalStatus) => {
    if (kycApprovalStatus === 'Pending') return 'processing';
    if (kycApprovalStatus === 'Verified') return 'success';
    if (kycApprovalStatus === 'Unverified') return 'warning';
}

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
        accessorKey: 'profit',
        header: 'profit_in',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'bonus_in',
        header: 'bonus_wallet_in',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'bonus_out',
        header: 'bonus_wallet_out',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'e_wallet_in',
        header: 'e_wallet_in',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'e_wallet_out',
        header: 'e_wallet_out',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'total_funding',
        header: 'total_funding',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'total_withdrawal',
        header: 'total_withdrawal',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'total_demo_fund',
        header: 'total_demo_fund',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
];

let table = useVueTable({
    data: affiliateSummaries.value,
    columns: columns.value,
    getCoreRowModel: getCoreRowModel(),
});

watch(affiliateSummaries, (newMembers) => {
    // Update table data when members value changes
    table = useVueTable({
        data: newMembers,
        columns: columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        state: {
            get sorting() {
                return sorting.value
            },
        },
        onSortingChange: updaterOrValue => {
            sorting.value =
                typeof updaterOrValue === 'function'
                    ? updaterOrValue(sorting.value)
                    : updaterOrValue
        },
    });
});

watch(pageSize, (newPageSize) => {
    table.setPageSize(newPageSize)
})

const pageIndex = ref(table.getState().pagination.pageIndex + 1);

watch(pageIndex, (newPageIndex) => {
    table.setPageIndex(newPageIndex - 1)
})

const clearFilter = () => {
    search.value = '';
    date.value = '';
    firstLeader.value = null;
    leaderDetail.value = null;
    affiliateSummaries.value = null;
}
</script>

<template>
    <AuthenticatedLayout title="Affiliate Listing">
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Affiliate Listing
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Track all transaction history carried out by different groups.
                    </p>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-start gap-8">
            <div class="flex justify-between items-start self-stretch">
                <div class="flex flex-col md:flex-row items-center gap-4 w-2/3">
                    <div class="w-full">
                        <Combobox
                            :load-options="loadUsers"
                            v-model="firstLeader"
                            placeholder="Leader"
                            image
                        />
                    </div>
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
                                :disabled="leaderDetail === null"
                            />
                        </InputIconWrapper>
                    </div>
                    <div class="w-full">
                        <vue-tailwind-datepicker
                            placeholder="Select dates"
                            :formatter="formatter"
                            separator=" - "
                            v-model="date"
                            input-classes="py-2.5 rounded-lg text-base font-normal shadow-xs border placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 disabled:bg-gray-200 disabled:cursor-not-allowed dark:disabled:bg-gray-800 disabled:text-gray-400 dark:disabled:text-gray-500"
                            :disabled="leaderDetail === null"
                        />
                    </div>
                </div>
                <div class="flex justify-end items-center gap-5">
                    <Button
                        type="button"
                        variant="secondary"
                        :disabled="leaderDetail === null"
                        @click="clearFilter"
                    >
                        <span class="text-lg">Clear</span>
                    </Button>
                    <Button
                        type="button"
                        variant="gray"
                        class="flex gap-1 justify-center"
                        v-slot="{ iconSizeClasses }"
                        @click="exportSummary"
                        :disabled="leaderDetail === null"
                    >
                        <CloudDownloadIcon class="w-5 h-5" />
                        Export
                    </Button>
                </div>
            </div>

            <div v-if="leaderDetail" class="flex flex-col sm:flex-row gap-5 w-full">
                <div class="p-5 w-full bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="flex flex-col gap-5">
                        <Badge
                            :variant="kycVariant(leaderDetail.kyc_approval)"
                        >
                            {{ leaderDetail.kyc_approval }}
                        </Badge>
                        <div class="flex justify-between">
                            <div class="flex items-center gap-2">
                                <img :src="leaderDetail.profile_photo_url ? leaderDetail.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-12 h-12 rounded-full" alt="">
                                <div class="flex flex-col">
                                    <div>
                                        {{ leaderDetail.name }}
                                    </div>
                                    <div>
                                        {{ leaderDetail.email }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <Badge variant="primary" width="auto">{{ leaderDetail.rank.name }}</Badge>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-5 w-full bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 w-full">
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.profit_in') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.profit ? leaderDetail.profit : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.bonus_wallet_in') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.bonus_in ? leaderDetail.bonus_in : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.bonus_wallet_out') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.bonus_out ? leaderDetail.bonus_out : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.e_wallet_in') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.e_wallet_in ? leaderDetail.e_wallet_in : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.e_wallet_out') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.e_wallet_out ? leaderDetail.e_wallet_out : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.total_funding') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.total_funding ? leaderDetail.total_funding : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.total_withdrawal') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.total_withdrawal ? leaderDetail.total_withdrawal : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.total_demo_fund') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.total_demo_fund ? leaderDetail.total_demo_fund : 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900 w-full">
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
                    v-if="affiliateSummaries === null"
                    class="w-full flex flex-col items-center justify-center"
                >
                    <div v-if="!leaderDetail" class="text-gray-600 dark:text-gray-400">
                        Search for a leader
                    </div>
                    <NoData />
                </div>
                <div v-else>
                    <div class="relative overflow-x-auto sm:rounded-lg">
                        <table class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
                            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                            <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <th
                                    v-for="header in headerGroup.headers"
                                    scope="col"
                                    class="p-3"
                                    :class="{
                                        'cursor-pointer select-none': header.column.getCanSort(),
                                        'bg-primary-100 dark:bg-primary-900': header.column.getIsSorted()
                                    }"
                                    @click="header.column.getToggleSortingHandler()?.($event)"
                                >
                                    <div class="flex items-center gap-2">
                                        <FlexRender
                                            :render="$t('public.' + header.column.columnDef.header)"
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
                        <div class="py-4 flex flex-col md:flex-row gap-2 md:items-center md:justify-between w-full">
                            <div class="text-sm">
                                Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }} - {{ table.getRowCount() }} results
                            </div>

                            <div class="flex items-center gap-4">
                                <Button
                                    variant="gray"
                                    type="button"
                                    @click="table.setPageIndex(0)"
                                    pill
                                    size="sm"
                                    v-slot="{iconSizeClasses}"
                                    :disabled="!table.getCanPreviousPage()"
                                >
                                    <ChevronLeftDoubleIcon class="w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="gray"
                                    @click="table.previousPage()"
                                    :disabled="!table.getCanPreviousPage()"
                                    pill
                                    size="sm"
                                    v-slot="{iconSizeClasses}"
                                >
                                    <ChevronLeftIcon class="w-4" />
                                </Button>
                                <Input
                                    id="page"
                                    type="number"
                                    min="1"
                                    :max="table.getPageCount()"
                                    class="block w-20"
                                    placeholder="Page"
                                    :value="table.getState().pagination.pageIndex + 1"
                                    v-model="pageIndex"
                                />
                                <Button
                                    type="button"
                                    variant="gray"
                                    @click="table.nextPage()"
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
                                    @click="table.setPageIndex(table.getPageCount() - 1)"
                                    pill
                                    size="sm"
                                    v-slot="{iconSizeClasses}"
                                    :disabled="!table.getCanNextPage()"
                                >
                                    <ChevronRightDoubleIcon class="w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
