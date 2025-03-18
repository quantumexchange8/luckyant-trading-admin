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
import ApplicantTableAction from "@/Pages/Application/Pending/ApplicantTableAction.vue";
import {SearchIcon, XCircleIcon} from "@heroicons/vue/outline";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import RadioButton from "primevue/radiobutton";

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
    leader_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    requires_flight: { value: null, matchMode: FilterMatchMode.EQUALS },
    requires_ib_training: { value: null, matchMode: FilterMatchMode.EQUALS },
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

            const url = route('application.getPendingApplications', params);
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
    getLeaders();
};

const leaders = ref();
const loadingLeaders = ref(false);

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

watch([filters.value['leader_id'], filters.value['requires_flight'], filters.value['requires_ib_training']], () => {
    loadLazyData();
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
    filters.value['leader_id'].value = null;
    filters.value['requires_flight'].value = null;
    filters.value['requires_ib_training'].value = null;
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
const exportReport = () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
    };

    const url = route('application.getPendingApplications', params);

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
            <div class="flex flex-wrap justify-between items-center mb-5">
                <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
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

                    <!-- filter button -->
                    <Button
                        type="button"
                        class="w-full md:w-28 flex items-center gap-2"
                        outlined
                        severity="secondary"
                        @click="toggle"
                        size="small"
                    >
                        <IconAdjustments :size="16" stroke-width="1.5" />
                        {{ $t('public.filter') }}
                    </Button>
                </div>

                <div class="flex items-center space-x-4 w-full md:w-auto mt-4 md:mt-0">
                    <!-- Export button -->
                    <Button
                        class="w-full md:w-auto flex justify-center items-center"
                        @click="exportReport"
                        :disabled="exportTable==='yes'"
                    >
                        <span class="pr-1">{{ $t('public.export') }}</span>
                        <IconDownload size="16" stroke-width="1.5"/>
                    </Button>
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
                <div class="dark:text-white">
                    Loading data
                </div>
            </div>
        </template>

        <template v-if="pendingWithdrawal?.length > 0">
            <Column
                field="created_at"
                class="hidden md:table-cell"
                dataType="date"
                :header="$t('public.request_date')"
                sortable
            >
                <template #body="{ data }">
                    {{ dayjs(data.created_at).format('YYYY-MM-DD') }}
                </template>
            </Column>

            <Column
                field="user"
                :header="$t('public.user_requested')"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    <div class="flex flex-col">
                        <span class="text-surface-950 dark:text-white">{{ data.user.name }}</span>
                        <span class="text-surface-500 text-xs">{{ data.user.email }}</span>
                    </div>
                </template>
            </Column>

            <Column
                field="leader"
                :header="$t('public.leader')"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    <div class="flex flex-col">
                        <span class="text-surface-950 dark:text-white">{{ data.first_leader_name }}</span>
                        <span class="text-surface-500 text-xs">{{ data.first_leader_email }}</span>
                    </div>
                </template>
            </Column>

            <Column
                field="application_form"
                :header="$t('public.application_form')"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    <div class="flex flex-col">
                      {{ data.application_form.title }}
                    </div>
                </template>
            </Column>

            <Column
                field="applicant"
                :header="$t('public.applicant')"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    <div class="flex flex-col">
                      {{ data.name }}
                    </div>
                </template>
            </Column>

            <Column
                field="applicant"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    <ApplicantTableAction
                        :applicant="data"
                    />
                </template>
            </Column>
        </template>
    </DataTable>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Role-->
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
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-400 select-none cursor-pointer bg-white dark:bg-transparent"
                        @click="clearDate"
                    >
                        <XCircleIcon class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- Filter Flight -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.flight') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['requires_flight'].value" inputId="requires_flight_yes" value="true" class="w-4 h-4" />
                        <label for="requires_flight_yes">{{ $t('public.yes') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['requires_flight'].value" inputId="requires_flight_no" value="false" class="w-4 h-4" />
                        <label for="requires_flight_no">{{ $t('public.no') }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter IB Training -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.ib_training') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['requires_ib_training'].value" inputId="requires_ib_training_yes" value="true" class="w-4 h-4" />
                        <label for="requires_ib_training_yes">{{ $t('public.yes') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['requires_ib_training'].value" inputId="requires_ib_training_no" value="false" class="w-4 h-4" />
                        <label for="requires_ib_training_no">{{ $t('public.no') }}</label>
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
