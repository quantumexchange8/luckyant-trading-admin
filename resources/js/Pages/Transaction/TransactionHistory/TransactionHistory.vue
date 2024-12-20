<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {h, ref, watch} from "vue";
import {Coins01Icon, CreditCardCheckIcon, CreditCardXIcon} from "@/Components/Icons/outline.jsx";
import {transactionFormat} from "@/Composables/index.js";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import DepositTable from "@/Pages/Transaction/TransactionHistory/DepositTable.vue";
import WithdrawalTable from "@/Pages/Transaction/TransactionHistory/WithdrawalTable.vue";
import TransferTable from "@/Pages/Transaction/TransactionHistory/TransferTable.vue";

const props = defineProps({
    transactionTypes: Array,
})

const totalAmount = ref(null);
const successAmount = ref(null);
const rejectedAmount = ref(null);

const {formatAmount} = transactionFormat();

const tabs = ref([
    {
        title: 'deposit',
        type: 'Deposit',
        value: '0',
        component: h(DepositTable)
    },
    {
        title: 'withdrawal',
        type: 'Withdrawal',
        value: '1',
        component: h(WithdrawalTable)
    },
    {
        title: 'transfer',
        type: 'Transfer',
        value: '2',
        component: h(TransferTable)
    },
    // {
    //     title: 'internal_transfer',
    //     type: 'InternalTransfer',
    //     value: '3'
    // },
]);

const selectedType = ref('deposit');
const activeIndex = ref('0');

// Watch for changes in selectedType and update the activeIndex accordingly
watch(activeIndex, (newIndex) => {
    const activeTab = tabs.value.find(tab => tab.value === newIndex);
    if (activeTab) {
        selectedType.value = activeTab.type;
    }
});

const handleUpdateTotals = (data) => {
    totalAmount.value = data.totalAmount;
    successAmount.value = data.successAmount;
    rejectedAmount.value = data.rejectedAmount;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.transaction_history')">
        <template #header>
            <div class="text-xl font-semibold leading-tight">
                {{ $t('public.transaction_history') }}
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <Tabs v-model:value="activeIndex" class="w-full">
                <TabList>
                    <Tab
                        v-for="tab in tabs"
                        :key="tab.title"
                        :value="tab.value"
                    >
                        {{ $t(`public.${tab.title}`) }}
                    </Tab>
                </TabList>
            </Tabs>

            <div class="grid grid-cols-1 sm:grid-cols-3 w-full gap-4">
                <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="flex flex-col gap-4">
                        <div>
                            Total Amount
                        </div>
                        <div class="text-xl font-bold">
                        <span v-if="totalAmount !== null">
                          $ {{ formatAmount(totalAmount ? totalAmount : 0) }}
                        </span>
                            <span v-else>
                          Loading...
                        </span>
                        </div>
                    </div>
                    <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                        <Coins01Icon class="text-primary-500 w-8 h-8" />
                    </div>
                </div>
                <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="flex flex-col gap-4">
                        <div>
                            Success Amount
                        </div>
                        <div class="text-xl font-bold">
                        <span v-if="successAmount !== null">
                          $ {{ formatAmount(successAmount ? successAmount : 0) }}
                        </span>
                            <span v-else>
                          Loading...
                        </span>
                        </div>
                    </div>
                    <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                        <CreditCardCheckIcon class="text-success-500 w-8 h-8" />
                    </div>
                </div>
                <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="flex flex-col gap-4">
                        <div>
                            Rejected Amount
                        </div>
                        <div class="text-xl font-bold">
                        <span v-if="rejectedAmount !== null">
                          $ {{ formatAmount(rejectedAmount ? rejectedAmount : 0) }}
                        </span>
                            <span v-else>
                          Loading...
                        </span>
                        </div>
                    </div>
                    <div class="rounded-full flex items-center justify-center w-14 h-14 bg-error-200">
                        <CreditCardXIcon class="text-error-500 w-8 h-8" />
                    </div>
                </div>
            </div>

            <component
                :is="tabs[activeIndex]?.component"
                :selectedType="selectedType"
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
