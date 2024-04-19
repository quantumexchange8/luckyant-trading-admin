<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import {h, ref, watch, watchEffect} from "vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import Action from "@/Pages/Admin/AdminListing/Action.vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {trans} from "laravel-vue-i18n";
import {usePage} from "@inertiajs/vue3";
import {UserUp01Icon} from "@/Components/Icons/outline.jsx"
import AssignUser from "@/Pages/Admin/AdminListing/AssignUser.vue";

const props = defineProps({
    roleLists: Array
})

const pageSizes = [
    {value: 5, label: 5},
    {value: 10, label: 10},
    {value: 20, label: 20},
    {value: 50, label: 50},
    {value: 100, label: 100},
]

const totalAdmin = ref(null);
const totalAmount = ref(null);
const date = ref('');
const search = ref('');
const rank = ref('');
const refresh = ref(false);
const adminUsers = ref({data: []});
const sorting = ref();
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const {formatDateTime, formatAmount} = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});


const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterDate = date.value, filterRank = rank.value, columnName = sorting.value) => {
    try {
        let url = `/admin/getAdminUsers?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
        }

        if (filterDate) {
            url += `&date=${filterDate}`;
        }

        if (filterRank) {
            url += `&rank=${filterRank}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        adminUsers.value = response.data.adminUsers;
        totalAdmin.value = response.data.totalAdmin;

    } catch (error) {
        console.error(error);
    }
}

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
        accessorKey: 'created_at',
        header: 'join_date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'role',
        header: 'role',
        enableSorting: false,
        cell: info => trans('public.' + info.getValue()),
    },
    {
        accessorKey: 'user_rank',
        header: 'rank',
        enableSorting: false,
    },
    {
        accessorKey: 'edit',
        header: 'action',
        enableSorting: false,
        cell: ({ row }) => h(Action, {id: row.original.id}),
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
        getResults(1, pageSizeValue, search.value, date.value, rank.value, sortingValue);
    }
);

watch(
    [search, date, rank],
    debounce(([searchValue, dateValue, rankValue]) => {
        getResults(1, pageSize.value, searchValue, dateValue, rankValue, sorting.value);
    }, 300)
);

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
<AuthenticatedLayout :title="$t('public.admin_listing')">
    <template #header>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.admin_listing') }}
                </h2>
            </div>
        </div>
    </template>

    <div class="flex flex-col gap-5 items-start self-stretch my-8">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
            <div class="w-full">
                <InputIconWrapper>
                    <template #icon>
                        <SearchIcon aria-hidden="true" class="w-5 h-5"/>
                    </template>
                    <Input
                        withIcon
                        id="search"
                        type="text"
                        class="w-full block"
                        :placeholder="$t('public.search')"
                        v-model="search"
                    />
                </InputIconWrapper>
            </div>
            <div class="w-full">
                <vue-tailwind-datepicker
                    :placeholder="$t('public.date_placeholder')"
                    :formatter="formatter"
                    separator=" - "
                    v-model="date"
                    input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                />
            </div>

            <div class="flex sm:col-span-2 justify-end gap-4 items-center w-full">
                <Button
                    type="button"
                    variant="secondary"
                >
                    {{ $t('public.clear') }}
                </Button>
            </div>
        </div>
    </div>

    <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
        <div class="flex justify-between">
            <div class="flex items-center">
                <AssignUser
                    :roleLists="roleLists"
                />
            </div>
            <div class="flex items-center gap-2">
                <div class="text-sm">
                    {{ $t('public.size') }}
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
            v-if="adminUsers.data.length === 0"
            class="w-full flex items-center justify-center"
        >
            <NoData/>
        </div>
        <div v-else>
            <TanStackTable
                :data="adminUsers"
                :columns="columns"
                @update:sorting="sorting = $event"
                @update:action="action = $event"
                @update:currentPage="currentPage = $event"
            />
        </div>
    </div>

</AuthenticatedLayout>

</template>
