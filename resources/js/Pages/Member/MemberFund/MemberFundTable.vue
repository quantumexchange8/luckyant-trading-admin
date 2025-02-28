<script setup>
import {onMounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {FilterMatchMode} from "@primevue/core/api";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import dayjs from "dayjs";
import Popover from "primevue/popover";
import {CloudDownloadIcon, SearchLgIcon, SlidersOneIcon, XIcon} from "@/Components/Icons/outline.jsx";
import Loading from "@/Components/Loading.vue";
import Select from "primevue/select";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Button from "primevue/button";
import Column from "primevue/column";
import Card from "primevue/card";
import DataTable from "primevue/datatable";
import DatePicker from "primevue/datepicker";
import Tag from "primevue/tag";
import Input from "@/Components/Input.vue";
import {XCircleIcon} from "@heroicons/vue/outline";
import {useLangObserver} from "@/Composables/localeObserver.js";
import {
    IconDownload
} from "@tabler/icons-vue";

const isLoading = ref(false);
const dt = ref(null);
const users = ref([]);
const {formatAmount, formatType} = transactionFormat();
const {locale} = useLangObserver();
const totalRecords = ref(0);
const first = ref(0);
const totalDeposit = ref(null);
const totalWithdrawal = ref(null);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    leader_id: { value: null, matchMode: FilterMatchMode.EQUALS },
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
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('member.getMemberFundData', params);
            const response = await fetch(url);
            const results = await response.json();

            users.value = results?.data.users;
            totalRecords.value = results?.data?.pagination.total;
            totalDeposit.value = results?.totalDeposit;
            totalWithdrawal.value = results?.totalWithdrawal;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        users.value = [];
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
    getCountries();
    getRanks();
}

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

const selectedDate = ref([]);

const clearJoinDate = () => {
    selectedDate.value = [];
}

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_date'].value = startDate;
        filters.value['end_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

const countries = ref();
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/getCountries');
        countries.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

const ranks = ref();
const loadingRanks = ref(false);

const getRanks = async () => {
    loadingRanks.value = true;
    try {
        const response = await axios.get('/getRanks');
        ranks.value = response.data;
    } catch (error) {
        console.error('Error fetching ranks:', error);
    } finally {
        loadingRanks.value = false;
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

watch([filters.value['leader_id']], () => {
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['leader_id'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;

    selectedDate.value = [];
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

const exportStatus = ref(false);

// Function to start export and get Job ID
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

    const url = route('report.getStandardBonusData', params);

    try {

        window.location.href = url;
    } catch (e) {
        console.error('Error occurred during export:', e);
    } finally {
        isLoading.value = false;
        exportStatus.value = false;
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
                    :value="users"
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
                    :globalFilterFields="['name', 'email', 'username']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-start self-stretch pb-3 md:pb-5">
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
                            <div class="flex justify-between items-start self-stretch w-full gap-3">
                                <Button
                                    class="w-full md:w-28 flex gap-2"
                                    severity="secondary"
                                    outlined
                                    @click="toggle"
                                >
                                    <SlidersOneIcon class="w-4 h-4" />
                                    Filter
                                </Button>
<!--                                <div class="flex items-center space-x-4 w-full md:w-auto mt-4 md:mt-0">-->
<!--                                    &lt;!&ndash; Export button &ndash;&gt;-->
<!--                                    <Button-->
<!--                                        type="button"-->
<!--                                        severity="info"-->
<!--                                        class="w-full md:w-auto"-->
<!--                                        @click="exportTable"-->
<!--                                        :disabled="exportStatus"-->
<!--                                    >-->
<!--                                        <span class="pr-1">{{ $t('public.export') }}</span>-->
<!--                                        <IconDownload size="16" stroke-width="1.5"/>-->
<!--                                    </Button>-->
<!--                                </div>-->
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="flex flex-col">
                            <span>No users</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Loading users</span>
                        </div>
                    </template>
                    <template v-if="users?.length > 0">
                        <Column
                            field="name"
                            sortable
                            frozen
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.name') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data" class="flex flex-col">
                                        <div class="flex gap-1 items-center">
                                            <span class="font-semibold">{{ slotProps.data.name }}</span>
                                            <Tag
                                                v-if="slotProps.data.leader_status"
                                                class="!text-xxs"
                                                severity="info"
                                                :value="$t('public.leader')"
                                            />
                                        </div>
                                        <span class="text-gray-400">{{ slotProps.data.email }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="leader_id"
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
                            field="total_deposit"
                            class="table-cell min-w-32"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.total_deposit') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="font-bold">
                                    $ {{ formatAmount(slotProps.data.total_deposits) }}
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="total_withdrawals"
                            class="table-cell min-w-32"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.total_withdrawal') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="font-bold">
                                    $ {{ formatAmount(slotProps.data.total_withdrawals) }}
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="total_group_deposits"
                            class="table-cell min-w-32"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.total_group_deposit') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="font-bold">
                                    $ {{ formatAmount(slotProps.data.total_group_deposits) }}
                                </div>
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
