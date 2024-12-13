<script setup>
import {onMounted, ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {FilterMatchMode} from "@primevue/core/api";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import Column from "primevue/column";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import {IconFileSearch} from "@tabler/icons-vue";
import {SearchLgIcon} from "@/Components/Icons/outline.jsx";
import {XCircleIcon} from "@heroicons/vue/outline";
import Loading from "@/Components/Loading.vue";

const props = defineProps({
    leaderId: Number
})

const isLoading = ref(false);
const dt = ref(null);
const registrations = ref([]);
const exportTable = ref('no');
const {formatAmount} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    leader_id: { value: props.leaderId, matchMode: FilterMatchMode.EQUALS },
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

            const url = route('report.getDailyChildRegisterData', params);
            const response = await fetch(url);
            const results = await response.json();

            registrations.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        registrations.value = [];
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
</script>

<template>
    <div
        class="w-full"
    >
        <DataTable
            :value="registrations"
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
<!--            <template #header>-->
<!--                <div class="flex flex-col md:flex-row gap-3 items-center self-stretch md:pb-5">-->
<!--                    <div class="relative w-full md:w-60">-->
<!--                        <InputIconWrapper class="md:col-span-2">-->
<!--                            <template #icon>-->
<!--                                <SearchLgIcon aria-hidden="true" class="w-5 h-5" />-->
<!--                            </template>-->
<!--                            <Input-->
<!--                                withIcon-->
<!--                                id="search"-->
<!--                                type="text"-->
<!--                                class="block w-full"-->
<!--                                placeholder="Search"-->
<!--                                v-model="filters['global'].value"-->
<!--                            />-->
<!--                        </InputIconWrapper>-->
<!--                        <div-->
<!--                            v-if="filters['global'].value !== null"-->
<!--                            class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"-->
<!--                            @click="clearFilterGlobal"-->
<!--                        >-->
<!--                            <XCircleIcon aria-hidden="true" class="w-4 h-4"/>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </template>-->
            <template #empty>
                <div class="flex flex-col">
                    <span>No registrations</span>
                </div>
            </template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loading />
                    <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">Loading registrations</span>
                    <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                </div>
            </template>
            <template v-if="registrations?.length > 0">
                <Column
                    field="registration_date"
                    sortable
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">{{ $t('public.join_date') }}</span>
                    </template>
                    <template #body="slotProps">
                        {{ slotProps.data.registration_date }}
                    </template>
                </Column>
                <Column
                    field="total_registers"
                    sortable
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">{{ $t('public.total_registration') }}</span>
                    </template>
                    <template #body="slotProps">
                        <span>{{ slotProps.data.total_registers }}</span>
                    </template>
                </Column>
            </template>
        </DataTable>
    </div>
</template>
