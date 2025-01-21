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

const isLoading = ref(false);
const subscriptions = ref([]);
const exportTable = ref('no');
const {formatAmount} = transactionFormat();

const getResults = async (filterFundType = null) => {
    isLoading.value = true;
    try {
        let url = `/pamm/getPammListingData?export=${exportTable.value}`;

        if (selectedLeader.value) {
            url += `&first_leader_id=${selectedLeader.value.id}`;
        }

        if (joinDatePicker.value.length > 0 && Array.isArray(joinDatePicker.value)) {
            const [startDate, endDate] = joinDatePicker.value;

            if (startDate !== null && endDate !== null) {
                url += `&joinStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&joinEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
            }
        }

        if (terminateDatePicker.value.length > 0 && Array.isArray(terminateDatePicker.value)) {
            const [startDate, endDate] = terminateDatePicker.value;

            if (startDate !== null && endDate !== null) {
                url += `&terminateStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&terminateEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
            }
        }

        if (filterFundType) {
            url += `&fundType=${filterFundType}`;
        }

        const response = await axios.get(url);
        subscriptions.value = response.data.subscriptions;
    } catch (error) {
        console.error('Error fetching subscriptions:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    getResults();
});

const filters = ref({
    global: {value: null, matchMode: FilterMatchMode.CONTAINS},
    master_meta_login: {value: null, matchMode: FilterMatchMode.EQUALS},
    status: {value: null, matchMode: FilterMatchMode.EQUALS},
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const getSeverity = (status) => {
    switch (status) {
        case 'Terminated':
            return 'danger';

        case 'Rejected':
            return 'danger';

        case 'Active':
            return 'success';

        case 'Pending':
            return 'info';

        case 'Expiring':
            return 'warning';
    }
}

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
    getMasters();
    getLeaders();
}

const masters = ref();
const loadingMasters = ref(false);
const selectedMaster = ref();

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

const leaders = ref();
const loadingLeaders = ref(false);
const selectedLeader = ref();
const joinDatePicker = ref([]);
const terminateDatePicker = ref([]);

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

watch([selectedMaster, selectedLeader, joinDatePicker, terminateDatePicker], ([newMaster, newLeader, newDateRange, newTerminateRange], [oldMaster, oldLeader, oldDateRange, oldTerminateRange]) => {
    if (newMaster) {
        filters.value['master_meta_login'].value = newMaster.meta_login;
    }

    if (newLeader) {
        getResults();
    }

    if (Array.isArray(newDateRange) && newDateRange !== oldDateRange) {
        const [startDate, endDate] = newDateRange;

        if (startDate !== null && endDate !== null) {
            getResults();
        }
    }

    if (Array.isArray(newTerminateRange) && newTerminateRange !== oldTerminateRange) {
        const [startDate, endDate] = newTerminateRange;

        if (startDate !== null && endDate !== null) {
            getResults();
        }
    }
});

const clearJoinDate = () => {
    joinDatePicker.value = [];
}

const clearTerminateDate = () => {
    terminateDatePicker.value = [];
}

const selectedFundType = ref('');

watch(selectedFundType, (newFundType) => {
    getResults(newFundType);
})

const clearAll = () => {
    filters.value['master_meta_login'].value = null;
    filters.value['status'].value = null;
    selectedMaster.value = null;
    selectedLeader.value = null;
    joinDatePicker.value = [];
    terminateDatePicker.value = [];
    selectedFundType.value = '';
}

const exportReport = () => {
    let url = `/pamm/getPammListingData?export=yes`;

    if (joinDatePicker.value?.length > 0) {
        const [startDate, endDate] = joinDatePicker.value;
        url += `&joinStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&joinEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
    }

    if (terminateDatePicker.value?.length > 0) {
        const [startDate, endDate] = terminateDatePicker.value;
        url += `&terminateStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&terminateEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
    }

    if (selectedMaster.value) {
        url += `&master_meta_login=${selectedMaster.value.meta_login}`;
    }

    if (selectedLeader.value) {
        url += `&first_leader_id=${selectedLeader.value.id}`;
    }

    if (selectedFundType.value) {
        url += `&fundType=${selectedFundType.value}`;
    }

    if (filters.value['status'].value) {
        url += `&status=${filters.value['status'].value}`;
    }

    window.location.href = url;
}
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    v-model:filters="filters"
                    :value="subscriptions"
                    :paginator="subscriptions?.length > 0"
                    removableSort
                    dataKey="id"
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    tableStyle="md:min-width: 50rem"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                    :globalFilterFields="['user.name', 'user.email', 'meta_login', 'master_meta_login']"
                    ref="dt"
                    :loading="isLoading"
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
                            <span>No subscriptions</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">Loading subscriptions</span>
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
                            class="table-cell min-w-36"
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
                            class="table-cell min-w-52"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.account_type') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="text-xs font-semibold uppercase">{{ $t(`public.${slotProps.data.master.trading_user.from_account_type.slug}`) }}</span>
                            </template>
                        </Column>
                        <Column
                            field="type"
                            sortable
                            class="table-cell"
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
                    v-model="selectedMaster"
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
                    v-model="selectedLeader"
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
                        v-model="joinDatePicker"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    />
                    <div
                        v-if="joinDatePicker && joinDatePicker.length > 0"
                        class="absolute top-2/4 -mt-2.5 right-4 text-gray-400 select-none cursor-pointer bg-white"
                        @click="clearJoinDate"
                    >
                        <XIcon class="w-4 h-4" />
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
                        v-model="terminateDatePicker"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    />
                    <div
                        v-if="terminateDatePicker && terminateDatePicker.length > 0"
                        class="absolute top-2/4 -mt-2.5 right-4 text-gray-400 select-none cursor-pointer bg-white"
                        @click="clearTerminateDate"
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
                        <RadioButton v-model="selectedFundType" inputId="demo_fund" value="demo_fund" class="w-4 h-4" />
                        <label for="demo_fund">Demo</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="selectedFundType" inputId="real_fund" value="real_fund" class="w-4 h-4" />
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
