<script setup>
import DataTable from "primevue/datatable";
import {onMounted, ref, watchEffect} from "vue";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import Column from "primevue/column";
import Tag from "primevue/tag";
import { XCircleIcon, SearchIcon } from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import Loading from "@/Components/Loading.vue";
import {transactionFormat} from "@/Composables/index.js";
import PaymentGatewayTableAction from "@/Pages/Setting/PaymentGateway/PaymentGatewayTableAction.vue";
import SettingPaymentTableAction from "@/Pages/Setting/Payment/SettingPaymentTableAction.vue";

const isLoading = ref(false);
const paymentMethods = ref([]);
const {formatAmount} = transactionFormat();

const getResults = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/setting/getSettingPaymentMethods');
        paymentMethods.value = response.data.paymentMethods;
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
    if (usePage().props.toast !== null) {
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
            :value="paymentMethods"
            :paginator="paymentMethods?.length > 0"
            removableSort
            dataKey="id"
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50, 100]"
            tableStyle="md:min-width: 50rem"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
            :globalFilterFields="['payment_platform_name', 'payment_account_name', 'account_no']"
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
                    <span>No payment methods added</span>
                </div>
            </template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <Loading />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Loading payment methods</span>
                </div>
            </template>
            <template v-if="paymentMethods.length">
                <Column
                    field="payment_platform_name"
                    sortable
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">{{ $t('public.name') }}</span>
                    </template>
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col">
                                <span class="font-semibold">{{ slotProps.data.payment_platform_name }}</span>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column
                    field="account"
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">{{ $t('public.account') }}</span>
                    </template>
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col">
                                <span class="font-semibold">{{ slotProps.data.payment_account_name }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-600">{{ slotProps.data.account_no }}</span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column
                    field="platform"
                    class="table-cell"
                    :header="$t('public.platform')"
                >
                    <template #body="slotProps">
                        <Tag
                            :severity="slotProps.data.payment_method === 'Bank' ? 'info' : 'secondary'"
                            :value="slotProps.data.payment_method"
                        />
                    </template>
                </Column>
                <Column
                    field="success_transactions_sum_amount"
                    sortable
                    class="table-cell"
                >
                    <template #header>
                        <span class="block">Total Fund</span>
                    </template>
                    <template #body="slotProps">
                        $ {{ formatAmount(slotProps.data.success_transactions_sum_amount ?? 0) }}
                    </template>
                </Column>
                <Column
                    field="status"
                    class="table-cell"
                    :header="$t('public.status')"
                >
                    <template #body="slotProps">
                        <Tag
                            :severity="slotProps.data.status === 'Active' ? 'success' : 'danger'"
                            :value="slotProps.data.status"
                        />
                    </template>
                </Column>
                <Column
                    field="action"
                    header=""
                    style="width: 10%"
                    class="table-cell"
                >
                    <template #body="slotProps">
                        <SettingPaymentTableAction
                            :settingPayment="slotProps.data"
                        />
                    </template>
                </Column>
            </template>
        </DataTable>
    </div>
</template>
