<script setup>
import Card from "primevue/card"
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {XCircleIcon} from "@heroicons/vue/outline";
import Loading from "@/Components/Loading.vue";
import {onMounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import Button from "primevue/button";
import {
    SlidersOneIcon,
    CloudDownloadIcon,
    XIcon,
    SearchLgIcon
} from "@/Components/Icons/outline.jsx"
import dayjs from "dayjs";
import Popover from "primevue/popover";
import Select from "primevue/select";
import DatePicker from "primevue/datepicker"
import debounce from "lodash/debounce.js";
import {IconFileSearch} from "@tabler/icons-vue";
import Dialog from "primevue/dialog";
import Tag from "primevue/tag";
import RadioButton from "primevue/radiobutton";

const props = defineProps({
    selectedType: String
})

const isLoading = ref(false);
const dt = ref(null);
const transactions = ref([]);
const exportTable = ref('no');
const {formatAmount} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);
const totalAmount = ref();
const successAmount = ref();
const rejectedAmount = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: props.selectedType, matchMode: FilterMatchMode.EQUALS },
    leader_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    fund_type: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };

    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('transaction.getTransactionHistory', params);
            const response = await fetch(url);
            const results = await response.json();

            transactions.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalAmount.value = results?.totalAmount;
            successAmount.value = results?.successAmount;
            rejectedAmount.value = results?.rejectedAmount;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        transactions.value = [];
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
    lazyParams.value.filters = filters.value ;
    loadLazyData(event);
};

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
    getLeaders();
}

const leaders = ref();
const loadingLeaders = ref(false);
const selectedDate = ref([]);

const getLeaders = async () => {
    loadingLeaders.value = true;
    try {
        const response = await axios.get('/getLeaders');
        leaders.value = response.data;
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

const clearJoinDate = () => {
    selectedDate.value = [];
}

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_date'].value = startDate;
        filters.value['end_date'].value = endDate;
        loadLazyData();
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

watch(selectedDate, () => {
    loadLazyData();
})

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
);

watch([filters.value['type'], filters.value['leader_id'], filters.value['fund_type'], filters.value['status']], () => {
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['leader_id'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const visible = ref(false);
const detail = ref(null);

const openDialog = (data) => {
    visible.value = true;
    detail.value = data;
}

const getSeverity = (status) => {
    switch (status) {
        case 'Rejected':
            return 'danger';

        case 'Success':
            return 'success';

        case 'Processing':
            return 'info';
    }
}

const emit = defineEmits(['update-totals']);

watch([totalAmount, successAmount, rejectedAmount], () => {
    emit('update-totals', {
        totalAmount: totalAmount.value,
        successAmount: successAmount.value,
        rejectedAmount: rejectedAmount.value,
    });
});

const exportStatus = ref(false);

const exportReport = () => {
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

    const url = route('transaction.getTransactionHistory', params);  // Construct the export URL

    try {
        // Send the request to the backend to trigger the export
        window.location.href = url;  // This will trigger the download directly
    } catch (e) {
        console.error('Error occurred during export:', e);  // Log the error if any
    } finally {
        isLoading.value = false;  // Reset loading state
        exportStatus.value = false;  // Reset export status
    }
};
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    :value="transactions"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    lazy
                    paginator
                    removableSort
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :first="first"
                    :rows="10"
                    v-model:filters="filters"
                    ref="dt"
                    dataKey="id"
                    :totalRecords="totalRecords"
                    :loading="isLoading"
                    @page="onPage($event)"
                    @sort="onSort($event)"
                    @filter="onFilter($event)"
                    :globalFilterFields="['user.name', 'user.email', 'meta_login', 'master_meta_login']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch md:pb-5">
                            <div class="relative w-full md:w-60">
                                <InputIconWrapper class="md:col-span-2">
                                    <template #icon>
                                        <SearchLgIcon aria-hidden="true" class="w-5 h-5" />
                                    </template>
                                    <Input
                                        withIcon
                                        id="search"
                                        type="text"
                                        class="block w-full"
                                        placeholder="Search"
                                        v-model="filters['global'].value"
                                    />
                                </InputIconWrapper>
                                <div
                                    v-if="filters['global'].value !== null"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <XCircleIcon aria-hidden="true" class="w-4 h-4"/>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 w-full gap-3">
                                <Button
                                    class="w-full md:w-28 flex gap-2"
                                    severity="secondary"
                                    outlined
                                    @click="toggle"
                                >
                                    <SlidersOneIcon class="w-4 h-4" />
                                    Filter
                                </Button>
                                <div class="w-full flex justify-end">
                                    <Button
                                        class="w-full md:w-28 flex gap-2"
                                        severity="secondary"
                                        @click="exportReport"
                                        :disabled="exportTable==='yes'"
                                    >
                                        <CloudDownloadIcon class="w-4 h-4" />
                                        Export
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="flex flex-col">
                            <span>No transactions</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">Loading transactions</span>
                            <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                        </div>
                    </template>
                    <template v-if="transactions?.length > 0">
                        <Column
                            field="created_at"
                            sortable
                            frozen
                            class="table-cell min-w-36"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.date') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="uppercase">{{ dayjs(slotProps.data.created_at).format('DD/MM/YYYY HH:mm:ss') }}</span>
                            </template>
                        </Column>
                        <Column
                            field="user.name"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.name') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data.user" class="flex flex-col">
                                        <span class="font-semibold">{{ slotProps.data.user.name }}</span>
                                        <span class="text-gray-400">{{ slotProps.data.user.email }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="first_leader_id"
                            show-filter-menu
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.leader') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data.first_leader_name" class="flex flex-col">
                                        <span class="font-semibold">{{ slotProps.data.first_leader_name }}</span>
                                        <span class="text-gray-400">{{ slotProps.data.first_leader_email }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="to_wallet_id"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.to') }}</span>
                            </template>
                            <template #body="slotProps">
                                {{ $t(`public.${slotProps.data.to_wallet.type}`) }}
                            </template>
                        </Column>
                        <Column
                            field="transaction_number"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.transaction_no') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="font-semibold">{{ slotProps.data.transaction_number }}</span>
                            </template>
                        </Column>
                        <Column
                            field="amount"
                            sortable
                            class="table-cell min-w-40"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.amount') }}</span>
                            </template>
                            <template #body="slotProps">
                                $ {{ formatAmount(slotProps.data.amount ?? 0) }}
                            </template>
                        </Column>
                        <Column
                            field="status"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.status') }}</span>
                            </template>
                            <template #body="slotProps">
                                <Tag :severity="getSeverity(slotProps.data.status)" :value="slotProps.data.status" />
                            </template>
                        </Column>
                        <Column
                            field="action"
                            class="table-cell"
                            frozen
                            align-frozen="right"
                        >
                            <template #body="{data}">
                                <Button
                                    type="button"
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    class="!p-2"
                                    @click="openDialog(data)"
                                >
                                    <IconFileSearch size="14" />
                                </Button>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Leader-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_leaders') }}
                </div>
                <Select
                    v-model="filters['leader_id'].value"
                    :options="leaders"
                    optionLabel="name"
                    placeholder="Select a leader"
                    class="w-full"
                    filter
                    :filter-fields="['name', 'email']"
                    :loading="loadingLeaders"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            {{ slotProps.value.name }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex flex-col max-w-[220px] truncate">
                            <div>{{ slotProps.option.name }}</div>
                            <div class="text-gray-400">{{ slotProps.option.email }}</div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Filter Join Date-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_date') }}
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedDate"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                        tim
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-2 text-gray-400 select-none cursor-pointer bg-transparent"
                        @click="clearJoinDate"
                    >
                        <XIcon class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- Filter Fund -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    Filter by fund
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['fund_type'].value" inputId="demo_fund" value="DemoFund" class="w-4 h-4" />
                        <label for="demo_fund">Demo</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['fund_type'].value" inputId="real_fund" value="RealFund" class="w-4 h-4" />
                        <label for="real_fund">Real</label>
                    </div>
                </div>
            </div>

            <!-- Filter Status -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    Filter by status
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['status'].value" inputId="status_success" value="Success" class="w-4 h-4" />
                        <label for="status_success">Success</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['status'].value" inputId="status_rejected" value="Rejected" class="w-4 h-4" />
                        <label for="status_rejected">Rejected</label>
                    </div>
                </div>
            </div>

            <Button
                type="button"
                severity="info"
                class="w-full"
                outlined
                @click="clearAll"
            >
                Clear All
            </Button>
        </div>
    </Popover>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.view_details')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center gap-4 divide-y dark:divide-gray-700 self-stretch">
            <div class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full">
                <div class="flex flex-col items-start w-full">
                    <span class="text-gray-950 dark:text-white text-sm font-medium">{{ detail.user.name }}</span>
                    <span class="text-gray-500 text-xs">{{ detail.user.email }}</span>
                </div>
                <div class="min-w-[180px] text-gray-950 dark:text-white font-semibold text-xl md:text-right">
                    $ {{ formatAmount(detail.amount ?? 0) }}
                </div>
            </div>

            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.date') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ dayjs(detail.created_at).format('DD/MM/YYYY HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.to') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ $t(`public.${detail.to_wallet.type}`) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.leader') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        <div v-if="detail.first_leader_name" class="flex flex-col">
                            <span class="font-semibold">{{ detail.first_leader_name }}</span>
                            <span class="text-gray-400">{{ detail.first_leader_email }}</span>
                        </div>
                        <div v-else class="h-[37px] flex items-center self-stretch">
                            -
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.transaction_no') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ detail.transaction_number }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.status') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        <Tag :severity="getSeverity(detail.status)" :value="detail.status" />
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
