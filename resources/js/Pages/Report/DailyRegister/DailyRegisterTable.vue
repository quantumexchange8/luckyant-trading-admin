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
import DailyChildRegisterTable from "@/Pages/Report/DailyRegister/DailyChildRegisterTable.vue";

const isLoading = ref(false);
const dt = ref(null);
const leaders = ref([]);
const exportTable = ref('no');
const {formatAmount} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
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

            const url = route('report.getDailyRegisterData', params);
            const response = await fetch(url);
            const results = await response.json();

            leaders.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        leaders.value = [];
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
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    :value="leaders"
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
                    :globalFilterFields="['name', 'email']"
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
                        </div>
                    </template>
                    <template #empty>
                        <div class="flex flex-col">
                            <span>No leaders</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">Loading leaders</span>
                            <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                        </div>
                    </template>
                    <template v-if="leaders?.length > 0">
                        <Column
                            field="name"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.name') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex flex-col">
                                    <span class="font-semibold">{{ slotProps.data.name }}</span>
                                    <span class="text-gray-400">{{ slotProps.data.email }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="total_downline"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.total_clients') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span>{{ slotProps.data.total_downline }}</span>
                            </template>
                        </Column>
                        <Column
                            field="today_register"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.today_registration') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span>{{ slotProps.data.today_register }}</span>
                            </template>
                        </Column>
                        <Column
                            field="action"
                            class="table-cell"
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

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.view_details')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center gap-4 self-stretch">
            <div class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full bg-gray-100 dark:bg-gray-800 p-5">
                <div class="flex flex-col items-start w-full">
                    <span class="text-gray-950 dark:text-white text-lg font-medium">{{ detail.name }}</span>
                    <span class="text-gray-500 text-sm">{{ detail.email }}</span>
                </div>
            </div>

            <DailyChildRegisterTable
                :leaderId="detail.id"
            />
        </div>
    </Dialog>
</template>
