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
import {useForm, usePage} from "@inertiajs/vue3";
import Button from "primevue/button";
import {
    SlidersOneIcon,
    CloudDownloadIcon,
    XIcon,
    CreditCardCheckIcon,
    CreditCardXIcon
} from "@/Components/Icons/outline.jsx"
import dayjs from "dayjs";
import Popover from "primevue/popover";
import Select from "primevue/select";
import DatePicker from "primevue/datepicker";
import Tag from "primevue/tag";
import PendingTransactionTableAction from "@/Pages/Transaction/PendingTransaction/PendingTransactionTableAction.vue";
import Dialog from "primevue/dialog";

const props = defineProps({
    selectedType: String
})

const isLoading = ref(false);
const pendings = ref([]);
const exportTable = ref('no');
const {formatAmount} = transactionFormat();

const filters = ref({
    global: {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const getResults = async () => {
    isLoading.value = true;
    try {
        let url = `/transaction/getPendingTransaction?type=${props.selectedType}`;

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

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
    getLeaders();
}

const leaders = ref();
const loadingLeaders = ref(false);
const selectedLeader = ref();
const joinDatePicker = ref([]);
const selectedPendings = ref();

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
    let url = `/transaction/getPendingTransaction?type=${props.selectedType}&export=yes`;

    if (selectedLeader.value) {
        url += `&first_leader_id=${selectedLeader.value.id}`;
    }

    if (joinDatePicker.value?.length > 0) {
        const [startDate, endDate] = joinDatePicker.value;
        url += `&joinStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&joinEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
    }

    window.location.href = url;
}

const form = useForm({
    id: null,
    type: '',
})
const totalAmount = ref(0);
const selectedPendingIds = ref([]);

watch(selectedPendings, (newChecked) => {
    selectedPendingIds.value = newChecked.map(item => item.id);

    totalAmount.value = newChecked.reduce((sum, item) => sum + parseFloat(item.amount), 0);
})

const visible = ref(false);
const dialogType = ref('');

const openDialog = (action) => {
    visible.value = true;
    dialogType.value = action;
}

const submitForm = () => {
    form.id = selectedPendingIds.value;

    if (dialogType.value === 'approve') {
        form.type = 'approve_selected';
        form.post(route('transaction.approveTransaction'), {
            onSuccess: () => {
                closeDialog();
            }
        })
    } else if (dialogType.value === 'reject') {
        form.type = 'reject_selected';
        form.post(route('transaction.rejectTransaction'), {
            onSuccess: () => {
                closeDialog();
            }
        })
    }
}

const closeDialog = () => {
    visible.value = false;
    dialogType.value = '';
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
                    v-model:selection="selectedPendings"
                    :value="pendings"
                    :paginator="pendings?.length > 0"
                    removableSort
                    dataKey="id"
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    tableStyle="md:min-width: 50rem"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                    :globalFilterFields="['user.name', 'user.email', 'to_wallet_address', 'transaction_number']"
                    ref="dt"
                    :loading="isLoading"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch md:pb-5">
                            <div class="relative w-full md:w-60">
                                <InputIconWrapper class="md:col-span-2">
                                    <template #icon>
                                        <SearchIcon aria-hidden="true" class="w-5 h-5"/>
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
                        <div class="flex flex-col">
                            <span>No pendings</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading/>
                            <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">Loading pendings</span>
                            <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                        </div>
                    </template>
                    <template v-if="pendings.length">
                        <Column frozen selectionMode="multiple" headerStyle="width: 3rem"></Column>
                        <Column
                            field="created_at"
                            :header="$t('public.date')"
                            class="min-w-32"
                        >
                            <template #body="slotProps">
                                {{ dayjs(slotProps.data.created_at).format('YYYY-MM-DD HH:mm:ss') }}
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
                            field="from_wallet"
                            :header="$t('public.from')"
                            class="min-w-32"
                        >
                            <template #body="slotProps">
                                {{ $t(`public.${slotProps.data.from_wallet.type}`) }}
                            </template>
                        </Column>
                        <Column
                            field="to_payment_account"
                            :header="$t('public.to')"
                        >
                            <template #body="slotProps">
                                <div class="flex flex-col gap-1">
                                    <span class="font-semibold">{{ slotProps.data.payment_account.payment_account_name }}</span>
                                    <span class="text-xs text-gray-400">{{ slotProps.data.to_wallet_address }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="transaction_number"
                            sortable
                            :header="$t('public.transaction_no')"
                            class="min-w-48"
                        >
                            <template #body="slotProps">
                                {{ slotProps.data.transaction_number }}
                            </template>
                        </Column>
                        <Column
                            field="method"
                            :header="$t('public.method')"
                        >
                            <template #body="slotProps">
                                <Tag
                                    :severity="slotProps.data.payment_method == 'Crypto' ? 'info' : 'secondary'"
                                    :value="slotProps.data.payment_method"
                                />
                            </template>
                        </Column>
                        <Column
                            field="profitAmount"
                            class="table-cell min-w-32"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.profit') }}</span>
                            </template>
                            <template #body="slotProps">
                                $ {{ formatAmount(slotProps.data.profitAmount ?? 0) }}
                            </template>
                        </Column>
                        <Column
                            field="bonusAmount"
                            class="table-cell min-w-32"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.bonus') }}</span>
                            </template>
                            <template #body="slotProps">
                                $ {{ formatAmount(slotProps.data.bonusAmount ?? 0) }}
                            </template>
                        </Column>
                        <Column
                            field="amount"
                            sortable
                            :header="$t('public.amount')"
                            class="table-cell min-w-40"
                        >
                            <template #body="slotProps">
                                <span class="text-primary-500">$ {{ formatAmount(slotProps.data.amount ?? 0) }}</span>
                            </template>
                        </Column>
                        <Column
                            field="transaction_charges"
                            sortable
                            :header="$t('public.fee')"
                            class="table-cell min-w-28"
                        >
                            <template #body="slotProps">
                                <span class="text-error-500">$ {{ formatAmount(slotProps.data.transaction_charges ?? 0) }}</span>
                            </template>
                        </Column>
                        <Column
                            field="transaction_amount"
                            sortable
                            :header="$t('public.receive')"
                            class="table-cell min-w-28"
                        >
                            <template #body="slotProps">
                                <span class="font-semibold">$ {{ formatAmount(slotProps.data.transaction_amount ?? 0) }}</span>
                            </template>
                        </Column>
                        <Column
                            field="action"
                        >
                            <template #header>
                                <span class="block"></span>
                            </template>
                            <template #body="slotProps">
                                <PendingTransactionTableAction
                                    :pending="slotProps.data"
                                />
                            </template>
                        </Column>
                    </template>
                </DataTable>

                <!-- Selected Action -->
                <div
                    v-if="selectedPendings?.length"
                    class="flex gap-3 flex-col pt-5"
                >
                    <div class="font-bold text-lg">
                        {{ $t('public.total_amount') }}: $ {{ formatAmount(totalAmount)}}
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            severity="success"
                            size="small"
                            @click="openDialog('approve')"
                        >
                            {{ $t('public.approve') }}
                        </Button>
                        <Button
                            severity="danger"
                            size="small"
                            @click="openDialog('reject')"
                        >
                            {{ $t('public.reject') }}
                        </Button>
                    </div>
                </div>
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
                    {{ $t('public.join_date') }}
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
                        <XIcon class="w-4 h-4"/>
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
        :header="$t(`public.${dialogType}_withdrawal`)"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col gap-5 items-center self-stretch">
            <div v-if="dialogType === 'approve'" class="rounded-full flex items-center justify-center w-[72px] h-[72px] bg-success-100">
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <CreditCardCheckIcon class="text-success-500 w-8 h-8" />
                </div>
            </div>
            <div v-if="dialogType === 'reject'" class="rounded-full flex items-center justify-center w-[72px] h-[72px] bg-error-100">
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-error-200">
                    <CreditCardXIcon class="text-error-500 w-8 h-8" />
                </div>
            </div>
            <div>
                Do you want to <span class="uppercase font-bold" :class="{'text-success-500': dialogType === 'approve', 'text-error-500': dialogType === 'reject'}">{{ dialogType }}</span> a total withdrawal of <strong>${{ formatAmount(totalAmount) }}</strong>?
            </div>

            <div class="flex gap-3 items-center justify-end self-stretch">
                <Button
                    severity="secondary"
                    size="small"
                    class="w-full md:w-fit"
                    @click="closeDialog"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    size="small"
                    class="w-full md:w-fit"
                    @click="submitForm"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
