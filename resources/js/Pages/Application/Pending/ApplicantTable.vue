<script setup>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputText from 'primevue/inputtext';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import ProgressSpinner from 'primevue/progressspinner';
import Popover from 'primevue/popover';
import Card from 'primevue/card';
import { IconXboxX, IconX, IconSearch, IconAdjustments, IconDownload } from '@tabler/icons-vue';
import { onMounted, ref, watch, watchEffect } from 'vue';
import debounce from "lodash/debounce.js";
import dayjs from 'dayjs';
import { FilterMatchMode } from '@primevue/core/api';
// import PendingWithdrawalAction from './PendingWithdrawalAction.vue';
import EmptyData from '@/Components/NoData.vue';
import { usePage } from '@inertiajs/vue3';
import { useLangObserver } from '@/Composables/localeObserver';

const isLoading = ref(false);
const dt = ref(null);
const first = ref(0);
const pendingWithdrawal = ref([]);
const totalRecords= ref(0);
const totalPendingAmount = ref();
const pendingWithdrawalCounts = ref();
const {locale} = useLangObserver();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;

    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value),
            };

            const url = route('transaction.pending.getPendingWithdrawalData', params);
            const response = await fetch(url);

            const results = await response.json();
            pendingWithdrawal.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalPendingAmount.value = results?.totalPendingAmount;
            pendingWithdrawalCounts.value = results?.pendingWithdrawalCounts;
            isLoading.value = false;
        }, 100);
    } catch (e) {
        pendingWithdrawal.value = [];
        totalRecords.value = 0;
        isLoading.value = false;
    }
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onFilter = (event) => {
    lazyParams.value.fitlers = filters.value;
    loadLazyData(event);
};

const emit = defineEmits(['updatePendingWithdrawal']);

watch([totalPendingAmount, pendingWithdrawalCounts], () => {
    emit('updatePendingWithdrawal', {
        totalPendingAmount: totalPendingAmount.value,
        pendingWithdrawalCounts: pendingWithdrawalCounts.value,
    });
});


//Date Filter
const selectedDate = ref([]);

const clearDate = () => {
    selectedDate.value = [];
};

watch(selectedDate, (newDateRange) => {
    if(Array.isArray(newDateRange)) { //ensure is an array with both start and end date
        const [startDate, endDate] = newDateRange; // extract data from newDateRange
        filters.value['start_date'].value = startDate; //update new date selected
        filters.value['end_date'].value = endDate;

        if(startDate !== null && endDate !== null){
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
});

//filter toggle
const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
};

onMounted(() => {
    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };
    loadLazyData();
});

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 300)
)

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
    selectedDate.value = [];
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
};

const getSeverity = (status) => {
    switch (status) {

        case 'processing':
            return 'info';
    }
};

const exportTable = ref('no');

const exportStatus = ref(false);
const exportWithdrawal = () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
    };

    const url = route('transaction.pending.getPendingWithdrawalData', params);

    try {
        window.location.href = url;
    } catch (e) {
        console.error('Error occured during export:', e);
    } finally {
        isLoading.value = false;
        exportStatus.value = false;
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

</script>

<template>
    <DataTable
        :value="pendingWithdrawal"
        lazy
        paginator
        removableSort
        :rows="10"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        :first="first"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        v-model:filters="filters"
        ref="dt"
        dataKey="id"
        :loading="isLoading"
        :totalRecords="totalRecords"
        @page="onPage($event)"
        @sort="onSort($event)"
        @filter="onFilter($event)"
        :globalFilterFields="['user.name' ,'transaction_number']"
    >
        <template #header>
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">

                    <!-- Search bar -->
                    <IconField>
                        <InputIcon>
                            <IconSearch :size="16" stroke-width="1.5" />
                        </InputIcon>
                        <InputText
                            v-model="filters['global'].value"
                            :placeholder="$t('public.search_keyword')"
                            type="text"
                            class="block w-full pl-10 pr-10"
                        />
                        <!-- Clear filter button -->
                        <div
                            v-if="filters['global'].value"
                            class="absolute top-1/2 -translate-y-1/2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                            @click="clearFilterGlobal"
                        >
                            <IconXboxX aria-hidden="true" :size="15" />
                        </div>
                    </IconField>

                    <!-- filter button -->
                    <Button
                        class="w-full md:w-28 flex items-center gap-2"
                        outlined
                        @click="toggle"
                        size="small"
                    >
                        <IconAdjustments :size="16" stroke-width="1.5" />
                        {{ $t('public.filter') }}
                    </Button>
                </div>

                <div class="flex items-center space-x-4 w-full md:w-auto mt-4 md:mt-0">
                    <!-- Export button -->
                    <!-- <Button
                        class="w-full md:w-auto flex justify-center items-center"
                        @click="exportWithdrawal"
                        :disabled="exportTable==='yes'"
                    >
                        <span class="pr-1">{{ $t('public.export') }}</span>
                        <IconDownload size="16" stroke-width="1.5"/>
                    </Button> -->
                </div>
            </div>
        </template>

        <template #empty>
            <div v-if="pendingWithdrawalCounts === 0">
                <EmptyData
                    :title="$t('public.no_withdrawal_founded')"
                />
            </div>
        </template>

        <template #loading>
            <div class="flex flex-col gap-2 items-center justify-center">
                <ProgressSpinner
                    strokeWidth="4"
                />
                <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">{{ $t('public.withdrawal_loading_caption') }}</span>
                <span v-else class="text-sm text-gray-700 dark:text-gray-300">{{ $t('public.export_withdrawal_caption') }}</span>
            </div>
        </template>

        <template v-if="pendingWithdrawal?.length > 0">
            <Column
                field="created_at"
                style="min-width: 11rem"
                class="hidden md:table-cell"
                dataType="date"
                sortable
            >
                <template #header>
                    <span class="block">{{ $t('public.date') }}</span>
                </template>
                <template #body="{ data }">
                    {{ dayjs(data.created_at).format('YYYY-MM-DD') }}
                    <div class="text-xs text-gray-500 mt-1">
                        {{ dayjs(data.created_at).add(8, 'hour').format('hh:mm:ss A') }}
                    </div>
                </template>
            </Column>

            <Column
                field="user_id"
                style="min-width: 11rem"
                class="hidden md:table-cell"
                sortable
            >
                <template #header>
                    <span class="block">{{ $t('public.name') }}</span>
                </template>
                <template #body="{ data }">
                    <div class="flex flex-col">
                        <span class="text-surface-950 dark:text-white">{{ data.user.name }}</span>
                        <span class="text-surface-500 text-xs">{{ data.user.email }}</span>
                    </div>
                </template>
            </Column>

            <Column
                field="transaction_number"
                style="min-width: 15rem"
                class="hidden md:table-cell"
                sortable
            >
                <template #header>
                    <span class="block">{{ $t('public.transaction_number') }}</span>
                </template>
                <template #body="{ data }">
                    {{ data.transaction_number }}
                </template>
            </Column>

            <Column
                field="to_payment_account_name"
                style="min-width: 9rem"
                class="hidden md:table-cell"
                sortable
            >
                <template #header>
                    <span class="block">{{ $t('public.payment_account') }}</span>
                </template>
                <template #body="{ data }">
                    <div class="flex gap-1 items-center">
                        <span class="break-words max-w-40 text-surface-950 dark:text-white">{{ data.to_payment_account_no }}</span>
                        <Tag
                            :severity="data.to_payment_platform === 'bank' ? 'info' : 'secondary'"
                            :value="$t(`public.${data.to_payment_platform}`)"
                        />
                    </div>
                </template>
            </Column>

            <Column
                field="amount"
                style="min-width: 9rem"
                class="hidden md:table-cell"
                dataType="numeric"
                sortable
            >
                <template #header>
                    <span class="block">{{ $t('public.amount') }}</span>
                </template>
                <template #body="{ data }">
                    <span class="font-medium">${{ formatAmount(data.amount ?? 0, 4) }}</span>
                </template>
            </Column>

            <!-- mobile view-->
            <Column
                field="mobile"
                class="table-cell md:hidden"
            >
                <template #body="{data}">
                    <div class="flex items-center gap-3 justify-between w-full">
                        <div class="flex flex-col items-start">
                                        <span class="text-xs text-white truncate">
                                                {{ dayjs(data.created_at).format('YYYY-MM-DD') }}
                                            </span>
                            <div class="flex gap-1 items-center text-surface-500 text-xs">
                                <div class="font-medium max-w-[180px] truncate">
                                    {{ data.user.name }}
                                </div>
                                <span>|</span>
                                <span class="font-bold dark:text-white/60">{{ data.transaction_number }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-start pr-2">
                            <div class="flex justify-end text-base w-full">
                                ${{ formatAmount(data.amount, 4) }}
                            </div>
                        </div>
                    </div>
                </template>
            </Column>

            <Column
                field="action"
                frozen
                alignFrozen="right"
                :style="locale === 'en'
                                    ? 'width: 5%'
                                    : 'width: 5%; min-width: 86px;'"
            >
                <template #body="{data}">
                    <PendingWithdrawalAction
                        :pending="data"
                    />
                </template>
            </Column>

        </template>
    </DataTable>
</template>
