<script setup>
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import DatePicker from 'primevue/datepicker';
import ProgressSpinner from 'primevue/progressspinner';
import { onMounted, ref, watch, watchEffect } from 'vue';
import dayjs from 'dayjs';
import { FilterMatchMode } from '@primevue/core/api';
import EmptyData from '@/Components/NoData.vue';
import {useForm, usePage} from '@inertiajs/vue3';
import {XCircleIcon} from "@heroicons/vue/outline";
import Card from "primevue/card";
import AllocatePool from "@/Pages/WorldPool/Allocation/AllocatePool.vue";
import {transactionFormat} from "@/Composables/index.js";
import Button from "primevue/button";
import {IconEdit} from "@tabler/icons-vue";
import Dialog from "primevue/dialog";
import InputLabel from "@/Components/Label.vue";
import InputNumber from "primevue/inputnumber";
import InputError from "@/Components/InputError.vue";
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';

// Extend dayjs with the isSameOrBefore plugin
dayjs.extend(isSameOrBefore);

defineProps({
    active_pamm_capital: Number,
    active_subscriptions_capital: Number,
    extra_fund_sum: Number,
})

const isLoading = ref(false);
const dt = ref(null);
const first = ref(0);
const allocations = ref([]);
const totalRecords= ref(0);
const {formatAmount} = transactionFormat();

const filters = ref({
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    amount: { value: null, matchMode: FilterMatchMode.EQUALS },
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

            const url = route('world_pool.getAllocationData', params);
            const response = await fetch(url);

            const results = await response.json();
            allocations.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    } catch (e) {
        allocations.value = [];
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

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

const visible = ref(false);
const allocationData = ref(false);

const openDialog = (allocation) => {
    visible.value = true;
    allocationData.value = allocation;
    form.allocation_amount = Number(allocation.allocation_amount);
}

const form = useForm({
    allocation_id: '',
    allocation_amount: null,
});

const submitForm = () => {
    form.allocation_id = allocationData.value.id;

    form.post(route('world_pool.updateWorldPool'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <Card class="w-full">
        <template #content>
            <DataTable
                :value="allocations"
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
                        <div class="flex flex-col md:flex-row gap-3 w-full md:max-w-60">
                            <div class="relative w-full">
                                <DatePicker
                                    v-model="selectedDate"
                                    dateFormat="dd/mm/yy"
                                    class="w-full font-normal"
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

                        <div class="flex items-center space-x-4 w-full md:w-auto mt-4 md:mt-0">
                            <AllocatePool
                                :active_pamm_capital="active_subscriptions_capital"
                                :active_subscriptions_capital="active_subscriptions_capital"
                                :extra_fund_sum="extra_fund_sum"
                            />
                        </div>
                    </div>
                </template>

                <template #empty>
                    <EmptyData
                        v-if="!isLoading"
                    />
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

                <template v-if="allocations?.length > 0">
                    <Column
                        field="allocation_date"
                        class="table-cell"
                        dataType="date"
                        :header="$t('public.date')"
                        sortable
                    >
                        <template #body="{ data }">
                            {{ dayjs(data.allocation_date).format('YYYY-MM-DD') }}
                        </template>
                    </Column>
                    <Column
                        field="allocation_amount"
                        class="table-cell"
                        :header="$t('public.extra_fund')"
                        sortable
                    >
                        <template #body="{ data }">
                            ${{ formatAmount(data.allocation_amount) }}
                        </template>
                    </Column>
                    <Column
                        field="world_pool_amount"
                        class="table-cell"
                        :header="$t('public.world_pool')"
                        sortable
                    >
                        <template #body="{ data }">
                            <div v-if="data.world_pool_amount">
                                ${{ formatAmount(data.world_pool_amount) }}
                            </div>
                            <div v-else>
                                -
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="action"
                        class="w-20"
                    >
                        <template #body="{ data }">
                            <Button
                                severity="secondary"
                                type="button"
                                size="small"
                                rounded
                                class="!p-2"
                                @click="openDialog(data)"
                                :disabled="dayjs(data.allocation_date).isSameOrBefore(dayjs(), 'day')"
                            >
                                <IconEdit size="16" stroke-width="1.5" />
                            </Button>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </template>
    </Card>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.edit_amount')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center gap-5 self-stretch">
            <div class="flex flex-col items-center gap-1 self-stretch bg-gray-100 dark:bg-gray-800 p-5">
                <span class="text-lg font-semibold dark:text-white">${{ formatAmount(allocationData.allocation_amount) }}</span>
                <span class="text-sm text-gray-500">{{ dayjs(allocationData.allocation_date).format('YYYY-MM-DD') }}</span>
            </div>

            <form class="w-full flex flex-col gap-5 items-center self-stretch">
                <div class="flex flex-col items-start gap-1 self-stretch w-full">
                    <InputLabel
                        for="allocation_amount"
                        :value="$t('public.pool_amount')"
                    />
                    <InputNumber
                        v-model="form.allocation_amount"
                        inputId="allocation_amount"
                        class="w-full"
                        :min="0"
                        :step="100"
                        fluid
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        placeholder="$0.00"
                        :invalid="!!form.errors.allocation_amount"
                    />
                    <InputError :message="form.errors.allocation_amount"/>
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    size="small"
                    @click.prevent="submitForm"
                    :label="$t('public.save')"
                />
            </form>
        </div>
    </Dialog>
</template>
