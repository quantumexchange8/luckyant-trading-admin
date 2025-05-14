<script setup>
import Card from "primevue/card"
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Tag from "primevue/tag";
import {onMounted, ref, watch, watchEffect} from "vue";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import dayjs from "dayjs";
import DatePicker from "primevue/datepicker"
import debounce from "lodash/debounce.js";
import {
    IconCircleX,
} from "@tabler/icons-vue";
import NoData from "@/Components/NoData.vue";
import ProgressSpinner from "primevue/progressspinner";
import {transactionFormat} from "@/Composables/index.js";
import {CloudDownloadIcon, SearchLgIcon, SlidersOneIcon} from "@/Components/Icons/outline.jsx";
import Input from "@/Components/Input.vue";
import {XCircleIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Button from "primevue/button";
import Select from "primevue/select";
import Popover from "primevue/popover";
import RadioButton from "primevue/radiobutton";

const isLoading = ref(false);
const dt = ref(null);
const histories = ref([]);
const exportTable = ref('no');
const {formatAmount} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);
const totalPerformanceIncentive = ref();
const totalUser = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    category: { value: null, matchMode: FilterMatchMode.EQUALS },
    type: { value: null, matchMode: FilterMatchMode.EQUALS },
    leader_id: {value: null, matchMode: FilterMatchMode.EQUALS},
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

            const url = route('report.getPerformanceIncentive', params);
            const response = await fetch(url);
            const results = await response.json();

            histories.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalPerformanceIncentive.value = results?.totalPerformanceIncentive;
            totalUser.value = results?.totalUser;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        histories.value = [];
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

const clearDate = () => {
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

watch([filters.value['type'], filters.value['leader_id'], filters.value['category'], filters.value['type']], () => {
    loadLazyData()
});


const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['category'].value = null;
    filters.value['type'].value = null;
    filters.value['leader_id'].value = null;
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

const emit = defineEmits(['update-totals']);

watch([totalPerformanceIncentive, totalUser], () => {
    emit('update-totals', {
        totalPerformanceIncentive: totalPerformanceIncentive.value,
        totalUser: totalUser.value,
    });
});

const exportStatus = ref(false);

const exportReport = () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = {...lazyParams.value, first: event?.first || first.value};

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
    };

    const url = route('report.getPerformanceIncentive', params);  // Construct the export URL

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
                    :value="histories"
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
                    :globalFilterFields="['username', 'email', 'meta_login']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch md:pb-5">
                            <div class="relative w-full md:w-60">
                                <InputIconWrapper class="md:col-span-2">
                                    <template #icon>
                                        <SearchLgIcon aria-hidden="true" class="w-5 h-5"/>
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
                                    <SlidersOneIcon class="w-4 h-4"/>
                                    Filter
                                </Button>
                                <div class="w-full flex justify-end">
                                    <Button
                                        class="w-full md:w-28 flex gap-2"
                                        severity="secondary"
                                        @click="exportReport"
                                        :disabled="exportTable==='yes'"
                                    >
                                        <CloudDownloadIcon class="w-4 h-4"/>
                                        Export
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <NoData
                            v-if="!isLoading"
                        />
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <ProgressSpinner
                                strokeWidth="4"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('public.loading') }}</span>
                        </div>
                    </template>
                    <template v-if="histories?.length > 0">
                        <Column
                            field="created_at"
                            sortable
                            class="table-cell min-w-32"
                            :header="$t('public.date')"
                        >
                            <template #body="{data}">
                                {{ dayjs(data.created_at).format('YYYY-MM-DD') }}
                                <div class="text-xs text-gray-500">
                                    {{ dayjs(data.created_at).format('HH:mm:ss') }}
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="name"
                            :header="$t('public.name')"
                        >
                            <template #body="{ data }">
                                <div v-if="data.user" class="flex flex-col">
                                    <span>{{ data.user.name ?? '-' }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ data.user.email }}</span>
                                </div>
                                <div v-else>
                                    -
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
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ slotProps.data.first_leader_email }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="type"
                            class="table-cell min-w-24"
                            :header="$t('public.category')"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :severity="data.category === 'pamm' ? 'secondary' : 'info'"
                                    :value="$t(`public.${data.category}`)"
                                />
                            </template>
                        </Column>

                        <Column
                            field="meta_login"
                            class="table-cell min-w-24"
                            :header="$t('public.from')"
                        >
                            <template #body="{ data }">
                                <div v-if="data.meta_login" class="flex gap-1 items-center">
                                    <span class="text-sm font-semibold">{{ data.meta_login }}</span>
                                    <Tag
                                        severity="contrast"
                                        :value="$t('public.personal')"
                                    />
                                </div>
                                <div
                                    v-else-if="data.category === 'pamm'"
                                    class="flex flex-col"
                                >
                                    <div class="flex items-center gap-1">
                                        <span class="text-sm font-semibold">{{ data.pamm_subscription.meta_login }}</span>
                                        <Tag
                                            severity="warn"
                                            :value="$t('public.network')"
                                        />
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ data.pamm_subscription.user.email }}</span>
                                </div>
                                <div
                                    v-else
                                    class="flex flex-col"
                                >
                                    <div class="flex items-center gap-1">
                                        <span class="text-sm font-semibold">{{ data.subscription.meta_login }}</span>
                                        <Tag
                                            severity="warn"
                                            :value="$t('public.network')"
                                        />
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ data.subscription.user.email }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="subscription_number"
                            class="table-cell min-w-24"
                            sortable
                            :header="$t('public.subscription_no')"
                        >
                            <template #body="{ data }">
                                <span class="font-medium">{{ data.subscription_number }}</span>
                            </template>
                        </Column>

                        <Column
                            field="subscription_profit_amt"
                            class="table-cell min-w-24"
                            sortable
                            :header="$t('public.profit')"
                        >
                            <template #body="{ data }">
                                <span class="font-medium">${{ formatAmount(data.subscription_profit_amt) }}</span>
                            </template>
                        </Column>

                        <Column
                            field="personal_bonus_percent"
                            class="table-cell min-w-24"
                            sortable
                            :header="$t('public.incentive') + ' %'"
                        >
                            <template #body="{ data }">
                                <span class="font-medium">{{ data.personal_bonus_percent % 1 === 0 ? formatAmount(data.personal_bonus_percent, 0) : formatAmount(data.personal_bonus_percent) }}%</span>
                            </template>
                        </Column>

                        <Column
                            field="personal_bonus_amt"
                            class="table-cell min-w-32"
                            sortable
                            :header="$t('public.performance_incentive')"
                        >
                            <template #body="{ data }">
                                <span class="font-medium text-success-500">${{ formatAmount(data.personal_bonus_amt) }}</span>
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

            <!-- Filter Request Date-->
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
                        @click="clearDate"
                    >
                        <IconCircleX size="16" />
                    </div>
                </div>
            </div>

            <!-- Filter Category -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_category') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton
                            v-model="filters['category'].value"
                            inputId="copytrade"
                            value="copytrade"
                            class="w-4 h-4"/>
                        <label for="copytrade">{{ $t('public.copytrade') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton
                            v-model="filters['category'].value"
                            inputId="pamm"
                            value="pamm"
                            class="w-4 h-4"
                        />
                        <label for="pamm">{{ $t('public.pamm') }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter Type -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_type') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton
                            v-model="filters['type'].value"
                            inputId="personal"
                            value="personal"
                            class="w-4 h-4"
                        />
                        <label for="personal">{{ $t('public.personal') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton
                            v-model="filters['type'].value"
                            inputId="network"
                            value="network"
                            class="w-4 h-4"
                        />
                        <label for="network">{{ $t('public.network') }}</label>
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
