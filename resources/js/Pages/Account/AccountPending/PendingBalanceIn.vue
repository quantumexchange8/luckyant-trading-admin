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
import Tag from "primevue/tag"
import PendingAction from "@/Pages/Account/AccountPending/PendingAction.vue";

const props = defineProps({
    pendingBalanceInCount: Number
})

const isLoading = ref(false);
const pendings = ref([]);
const exportTable = ref('no');
const {formatAmount, formatType} = transactionFormat();

const getResults = async () => {
    isLoading.value = true;
    try {
        let url = `/account/getAccountPendingData?transaction_type=BalanceIn`;

        if (selectedLeader.value) {
            url += `&first_leader_id=${selectedLeader.value.id}`;
        }

        if (joinDatePicker.value.length > 0 && Array.isArray(joinDatePicker.value)) {
            const [startDate, endDate] = joinDatePicker.value;

            if (startDate !== null && endDate !== null) {
                url += `&joinStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&joinEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
            }
        }

        const response = await axios.get(url);
        pendings.value = response.data.pendings;
    } catch (error) {
        console.error('Error fetching pendings:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    getResults();
});

const filters = ref({
    global: {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

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
        const response = await axios.get('/getMasters');
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

watch([selectedLeader, joinDatePicker], ([newLeader, newDateRange]) => {
    if (newLeader) {
        getResults();
    }

    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;

        if (startDate !== null && endDate !== null) {
            getResults();
        }
    }
});

const clearJoinDate = () => {
    joinDatePicker.value = [];
}

const clearAll = () => {
    filters.value['global'].value = null;
    selectedLeader.value = null;
    joinDatePicker.value = [];
}

const exportReport = () => {
    let url = `/account/getAccountPendingData?transaction_type=BalanceIn&export=yes`;

    if (selectedLeader.value) {
        url += `&first_leader_id=${selectedLeader.value.id}`;
    }

    if (joinDatePicker.value?.length > 0) {
        const [startDate, endDate] = joinDatePicker.value;
        url += `&joinStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&joinEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
    }

    window.location.href = url;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    v-model:filters="filters"
                    :value="pendings"
                    :paginator="pendings?.length > 0"
                    removableSort
                    dataKey="id"
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    tableStyle="md:min-width: 50rem"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                    :globalFilterFields="['user.name', 'user.email', 'to_meta_login.meta_login', 'transaction_number']"
                    ref="dt"
                    :loading="isLoading"
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
                                        :disabled="exportTable ==='yes'"
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
                            <span>No pendings</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">Loading pendings</span>
                            <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                        </div>
                    </template>
                    <template v-if="pendings.length">
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
                            field="transaction_number"
                            sortable
                            class="table-cell min-w-48"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.transaction_no') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="uppercase">{{ slotProps.data.transaction_number }}</span>
                            </template>
                        </Column>
                        <Column
                            field="from_wallet_id"
                            class="table-cell min-w-40"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.wallet') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="font-semibold">{{ $t(`public.${slotProps.data.from_wallet.type}`) }}</span>
                            </template>
                        </Column>
                        <Column
                            field="to_meta_login.meta_login"
                            class="table-cell min-w-40"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.account') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="font-semibold mr-1">{{ slotProps.data.to_meta_login.meta_login }}</span>
                                <Tag
                                    severity="secondary"
                                    :value="$t(`public.${slotProps.data.to_meta_login.account_type.slug}`)"
                                />
                            </template>
                        </Column>
                        <Column
                            field="amount"
                            sortable
                            class="md:table-cell hidden min-w-52"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.amount') }}</span>
                            </template>
                            <template #body="slotProps">
                                $ {{ formatAmount(slotProps.data.amount ?? 0) }}
                                <Tag
                                    :severity="slotProps.data.fund_type === 'DemoFund' ? 'secondary' : 'info'"
                                    :value="$t(`public.${formatType(slotProps.data.fund_type).toLowerCase().replace(/\s+/g, '_')}`)"
                                />
                            </template>
                        </Column>
                        <Column
                            field="action"
                            class="table-cell"
                            frozen
                            align-frozen="right"
                        >
                            <template #body="slotProps">
                                <PendingAction
                                    :pending="slotProps.data"
                                />
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
                    {{ $t('public.filter_date') }}
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
