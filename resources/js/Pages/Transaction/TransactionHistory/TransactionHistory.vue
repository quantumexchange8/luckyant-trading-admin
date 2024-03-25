<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ref} from "vue";
import TransactionHistory from "@/Pages/Transaction/TransactionHistory/TransactionHistoryTable.vue";
import {SearchIcon, RefreshIcon} from "@heroicons/vue/outline";
import {CloudDownloadIcon} from "@/Components/Icons/outline.jsx";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import TransactionHistoryTable from "@/Pages/Transaction/TransactionHistory/TransactionHistoryTable.vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    transactionTypes: Array,
})

const refresh = ref(false);
const isLoading = ref(false);
const search = ref('');
const date = ref('');
const type = ref('');
const fund_type = ref('');
const status = ref('');
const methods = ref('');
const transactionType = ref('');
const exportStatus = ref(false);
const { formatType } = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const statusList = [
    {value:'Success', label:"Success"},
    {value:'Rejected', label:"Rejected"},
];

const fundTypes = [
    {value: '', label:"All"},
    {value: 'DemoFund', label:"Demo Fund"},
    {value: 'RealFund', label:"Real Fund"},
];

const paymentMethods = [
    {value: '', label:"All"},
    {value: 'Bank', label:"Bank"},
    {value: 'Crypto', label:"Crypto"},
    {value: 'Payment Merchant', label:"Payment Merchant"},
];

function refreshTable() {
    search.value = '';
    date.value = '';
    status.value = '';
    transactionType.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

const exportTransaction = () => {
    exportStatus.value = true;
}


const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};

const selectedTab = ref(0);
function changeTab(index) {
    selectedTab.value = index;
}
</script>

<template>
    <AuthenticatedLayout title="Transaction History">
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Transaction History
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Track all transaction history carried out by your members.
                    </p>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-start self-stretch">
            <div class="flex flex-col md:flex-row items-center gap-4 w-full">
                <div class="w-full">
                    <InputIconWrapper class="w-full">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
                    </InputIconWrapper>
                </div>
                <div class="w-full">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-600"
                        v-model="methods"
                        :options="paymentMethods"
                        placeholder="Filter Payment Methods"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-600"
                        v-model="fund_type"
                        :options="fundTypes"
                        placeholder="Filter Fund Type"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-600"
                        v-model="status"
                        :options="statusList"
                        placeholder="Filter status"
                    />
                </div>
            </div>
            <div class="flex justify-end gap-4 items-center w-full">
                <Button
                    type="button"
                    variant="secondary"
                    @click="refreshTable"
                >
                    <span class="text-lg">Clear</span>
                </Button>
                <Button
                    type="button"
                    variant="gray"
                    class="flex gap-1 justify-center"
                    v-slot="{ iconSizeClasses }"
                    @click="exportTransaction"
                >
                    <CloudDownloadIcon class="w-5 h-5" />
                    Export
                </Button>
            </div>
        </div>

        <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="w-full">
                <TabGroup :selectedIndex="selectedTab" @change="changeTab">
                    <TabList class="flex py-1 w-full flex-col gap-3 sm:flex-row sm:justify-between">
                        <div class="w-full">
                            <Tab
                                v-for="transactionType in transactionTypes"
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    @click="updateTransactionType(transactionType.value)"
                                    class="w-full sm:w-40"
                                    :class="[
                                    'py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                       selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                                >
                                    {{ formatType(transactionType.label) }}
                                </button>
                            </Tab>
                        </div>
                    </TabList>

                    <TabPanels>
                        <TabPanel
                            v-for="transactionType in transactionTypes"
                        >
                            <TransactionHistoryTable
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :fund_type="fund_type"
                                :methods="methods"
                                :status="status"
                                :transactionType=transactionType.value
                                :exportStatus="exportStatus"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                                @update:export="exportStatus = $event"
                            />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
<!--            <TransactionHistory-->
<!--                :refresh="refresh"-->
<!--                :isLoading="isLoading"-->
<!--                :search="search"-->
<!--                :date="date"-->
<!--                :status="status"-->
<!--                :transactionType="transactionType"-->
<!--                :transactionTypes="transactionTypes"-->
<!--                :exportStatus="exportStatus"-->
<!--                @update:loading="isLoading = $event"-->
<!--                @update:refresh="refresh = $event"-->
<!--                @update:export="exportStatus = $event"-->
<!--            />-->
        </div>
    </AuthenticatedLayout>
</template>
