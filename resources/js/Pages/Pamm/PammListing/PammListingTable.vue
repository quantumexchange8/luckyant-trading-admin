<script setup>
import Card from "primevue/card"
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon, XCircleIcon} from "@heroicons/vue/outline";
import Loading from "@/Components/Loading.vue";
import {onMounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import Button from "primevue/button";
import {
    SlidersOneIcon,
    CloudDownloadIcon,
    XIcon
} from "@/Components/Icons/outline.jsx"
import dayjs from "dayjs";
import Tag from "primevue/tag";
import Popover from "primevue/popover";
import Select from "primevue/select";
import DatePicker from "primevue/datepicker"
import RadioButton from "primevue/radiobutton"
import debounce from "lodash/debounce.js";

const exportStatus = ref(false);
const isLoading = ref(false);
const dt = ref(null);
const subscriptions = ref([]);
const {formatAmount} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);

const filters = ref({
    global: {value: null, matchMode: FilterMatchMode.CONTAINS},
    master_meta_login: { value: null, matchMode: FilterMatchMode.EQUALS },
    leader_id: {value: null, matchMode: FilterMatchMode.EQUALS},
    start_approval_date: {value: null, matchMode: FilterMatchMode.EQUALS},
    end_approval_date: {value: null, matchMode: FilterMatchMode.EQUALS},
    start_terminate_date: {value: null, matchMode: FilterMatchMode.EQUALS},
    end_terminate_date: {value: null, matchMode: FilterMatchMode.EQUALS},
    start_expired_date: {value: null, matchMode: FilterMatchMode.EQUALS},
    end_expired_date: {value: null, matchMode: FilterMatchMode.EQUALS},
    fund_type: {value: null, matchMode: FilterMatchMode.EQUALS},
    status: {value: null, matchMode: FilterMatchMode.EQUALS},
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = {...lazyParams.value, first: event?.first || first.value};
    lazyParams.value.filters = filters.value;
    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('pamm.getPammListingData', params);
            const response = await fetch(url);
            const results = await response.json();

            subscriptions.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    } catch (e) {
        subscriptions.value = [];
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
    lazyParams.value.filters = filters.value;
    loadLazyData(event);
};

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
    getMasters();
    getLeaders();
}

const leaders = ref();
const loadingLeaders = ref(false);
const selectedTerminateDate = ref([]);
const selectedApprovalDate = ref([]);
const selectedExpiredDate = ref([]);

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

const masters = ref();
const loadingMasters = ref(false);

const getMasters = async () => {
    loadingMasters.value = true;
    try {
        const response = await axios.get('/getMasters?category=pamm');
        masters.value = response.data;
    } catch (error) {
        console.error('Error fetching masters:', error);
    } finally {
        loadingMasters.value = false;
    }
};

const clearApprovalDate = () => {
    selectedApprovalDate.value = [];
}

const clearTerminateDate = () => {
    selectedTerminateDate.value = [];
}

const clearExpiredDate = () => {
    selectedTerminateDate.value = [];
}

watch(selectedTerminateDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_terminate_date'].value = startDate;
        filters.value['end_terminate_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

watch(selectedApprovalDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_approval_date'].value = startDate;
        filters.value['end_approval_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

watch(selectedExpiredDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_expired_date'].value = startDate;
        filters.value['end_expired_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
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

watch([filters.value['master_meta_login'], filters.value['leader_id'], filters.value['fund_type'], filters.value['status']], () => {
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['leader_id'].value = null;
    selectedApprovalDate.value = [];
    selectedTerminateDate.value = [];
    selectedExpiredDate.value = [];
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

const emit = defineEmits(['update-totals']);

const exportTable = () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;

    if (filters.value) {
        lazyParams.value.filters = { ...filters.value };
    } else {
        lazyParams.value.filters = {};
    }

    let params = {
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
    };

    const url = route('pamm.getPammListingData', params);

    try {

        window.location.href = url;
    } catch (e) {
        console.error('Error occurred during export:', e);
    } finally {
        isLoading.value = false;
        exportStatus.value = false;
    }
};

const getSeverity = (status) => {
    switch (status) {
        case 'Terminated':
            return 'danger';

        case 'Active':
            return 'success';

        case 'Pending':
            return 'info';

        case 'Expiring':
            return 'warning';
    }
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
})
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    :value="subscriptions"
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
                    :globalFilterFields="['name', 'email', 'connection_number']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch md:pb-5">
                            <div class="relative w-full md:w-60">
                                <InputIconWrapper class="md:col-span-2">
                                    <template #icon>
                                        <SearchIcon aria-hidden="true" class="w-5 h-5" />
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
                                        @click="exportTable"
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
                            <span>No subscriptions</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span v-if="!exportStatus" class="text-sm text-gray-700 dark:text-gray-300">Loading subscriptions</span>
                            <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                        </div>
                    </template>
                    <template v-if="subscriptions.length">
                        <Column
                            field="approval_date"
                            sortable
                            frozen
                            class="table-cell min-w-36"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.join_date') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="uppercase">{{ dayjs(slotProps.data.approval_date).format('DD/MM/YYYY HH:mm:ss') }}</span>
                            </template>
                        </Column>
                        <Column
                            field="user.name"
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
                            field="meta_login"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.account') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="font-semibold">{{ slotProps.data.meta_login }}</span>
                            </template>
                        </Column>
                        <Column
                            field="master_meta_login"
                            sortable
                            class="table-cell text-nowrap"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.master') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data.master" class="flex flex-col">
                                        <span class="font-semibold">{{ slotProps.data.master.trading_user.name }}</span>
                                        <span class="text-gray-400">{{ slotProps.data.master_meta_login }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="platform"
                            sortable
                            class="table-cell text-nowrap"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.type') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="text-xs font-semibold uppercase">{{ slotProps.data.type }}</span>
                            </template>
                        </Column>
                        <Column
                            field="subscription_amount"
                            sortable
                            class="table-cell min-w-40"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.fund') }}</span>
                            </template>
                            <template #body="slotProps">
                                $ {{ formatAmount(slotProps.data.subscription_amount ?? 0) }}
                                <Tag
                                    :severity="slotProps.data.demo_fund > 0 ? 'secondary' : 'info'"
                                    :value="slotProps.data.demo_fund > 0 ? 'Demo' : 'Real'"
                                />
                            </template>
                        </Column>
                        <Column
                            field="approval_date"
                            sortable
                            class="table-cell text-nowrap"
                            :header="$t('public.expired_on')"
                        >
                            <template #body="{data}">
                                <div v-if="data.expired_date" class="flex flex-col">
                                    <span>{{ dayjs(data.expired_date).format('DD/MM/YYYY') }}</span>
                                    <span class="text-xs text-gray-500">{{ dayjs(data.expired_date).format('HH:mm:ss') }}</span>
                                </div>
                                <div v-else>
                                    -
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="status"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.status') }}</span>
                            </template>
                            <template #body="slotProps">
                                <Tag
                                    :severity="getSeverity(slotProps.data.status)"
                                    :value="slotProps.data.status"
                                />
                            </template>
                        </Column>
                        <Column
                            field="settlement_date"
                            class="table-cell"
                            :header="$t('public.settlement')"
                        >
                            <template #body="{data}">
                                {{ dayjs(data.settlement_date).add(1, 'day').format('YYYY/MM/DD') }}
                            </template>
                        </Column>
                        <Column
                            field="termination_date"
                            sortable
                            class="table-cell min-w-32"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.termination') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span v-if="slotProps.data.termination_date" class="uppercase">{{ dayjs(slotProps.data.termination_date).format('DD/MM/YYYY HH:mm:ss') }}</span>
                                <span v-else>-</span>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Role-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    Filter by Master
                </div>
                <Select
                    v-model="filters['master_meta_login'].value"
                    :options="masters"
                    optionLabel="trading_user.name"
                    placeholder="Select a master"
                    class="w-full"
                    filter
                    :filter-fields="['trading_user.name', 'meta_login']"
                    :loading="loadingMasters"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            {{ slotProps.value.trading_user.name }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center gap-1 max-w-[220px] truncate">
                            <span>{{ slotProps.option.trading_user.name }}</span> <span class="text-gray-400">{{ slotProps.option.meta_login }}</span>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Filter Leader-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    Filter by Leader
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
                    Filter Join Date
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedApprovalDate"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    />
                    <div
                        v-if="selectedApprovalDate && selectedApprovalDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-400 select-none cursor-pointer bg-white dark:bg-transparent"
                        @click="clearApprovalDate"
                    >
                        <XCircleIcon class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- Filter Terminate Date-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    Filter Termination Date
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedTerminateDate"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    />
                    <div
                        v-if="selectedTerminateDate && selectedTerminateDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-400 select-none cursor-pointer bg-white dark:bg-transparent"
                        @click="clearTerminateDate"
                    >
                        <XCircleIcon class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- Filter Expired Date-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    Filter Expired Date
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedExpiredDate"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    />
                    <div
                        v-if="selectedExpiredDate && selectedExpiredDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-400 select-none cursor-pointer bg-white dark:bg-transparent"
                        @click="clearExpiredDate"
                    >
                        <XCircleIcon class="w-4 h-4" />
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
                        <RadioButton v-model="filters['fund_type'].value" inputId="demo_fund" value="demo_fund" class="w-4 h-4" />
                        <label for="demo_fund">Demo</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['fund_type'].value" inputId="real_fund" value="real_fund" class="w-4 h-4" />
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
                        <RadioButton v-model="filters['status'].value" inputId="status_active" value="Active" class="w-4 h-4" />
                        <label for="status_active">Active</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['status'].value" inputId="status_terminated" value="Terminated" class="w-4 h-4" />
                        <label for="status_terminated">Terminated</label>
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
</template>
