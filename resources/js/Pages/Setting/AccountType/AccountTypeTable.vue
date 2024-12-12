<script setup>
import DataTable from "primevue/datatable";
import {onMounted, ref, watchEffect} from "vue";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import Column from "primevue/column";
import InputText from "@/Components/Input.vue"
import { XCircleIcon, SearchIcon } from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import Loading from "@/Components/Loading.vue";
import {transactionFormat} from "@/Composables/index.js";
import AccountTypeTableAction from "@/Pages/Setting/AccountType/Partials/AccountTypeTableAction.vue";

const isLoading = ref(false);
const accountTypes = ref([]);

const getResults = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/setting/getAccountTypes');
        accountTypes.value = response.data.accountTypes;
    } catch (error) {
        console.error('Error fetching payment gateways:', error);
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
</script>

<template>
    <div
        class="w-full"
    >
        <DataTable
            v-model:filters="filters"
            :value="accountTypes"
            :paginator="accountTypes?.length > 0"
            removableSort
            dataKey="id"
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50, 100]"
            tableStyle="md:min-width: 50rem"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            :globalFilterFields="['name', 'platform']"
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
                </div>
            </template>
            <template #empty>
                <div class="flex flex-col">
                    <span>No payment gateway added</span>
                </div>
            </template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loading />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Loading payment gateway</span>
                </div>
            </template>
            <template v-if="accountTypes.length">
                <Column
                    field="name"
                    sortable
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">{{ $t('public.name') }}</span>
                    </template>
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col">
                                <span class="font-semibold">{{ slotProps.data.name }}</span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column
                    field="platform"
                    sortable
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">Display</span>
                    </template>
                    <template #body="slotProps">
                        <span class="uppercase">{{ slotProps.data.slug }}</span>
                    </template>
                </Column>
                <Column
                    field="success_transactions_sum_amount"
                    sortable
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">Max Acc</span>
                    </template>
                    <template #body="slotProps">
                        {{ slotProps.data.maximum_account_number }}
                    </template>
                </Column>
                <Column
                    field="action"
                    header=""
                    style="width: 10%"
                    class="table-cell"
                >
                    <template #body="slotProps">
                        <AccountTypeTableAction
                            :accountType="slotProps.data"
                        />
                    </template>
                </Column>
            </template>
        </DataTable>
    </div>
</template>
