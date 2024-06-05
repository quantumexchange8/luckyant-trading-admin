<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {CloudDownloadIcon, CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";
import {onMounted, ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import Combobox from "@/Components/Combobox.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import PendingDeposit from "@/Pages/Transaction/TransactionPending/PendingDeposit/PendingDeposit.vue";
import PendingWithdrawal from "@/Pages/Transaction/TransactionPending/PendingWithdrawal/PendingWithdrawal.vue";

const transactionTypes = ref([
    {value: 'Deposit', label: 'Deposit'},
    {value: 'Withdrawal', label: 'Withdrawal'}
])

const totalPendingDeposits = ref(null);
const totalPendingWithdrawals = ref(null);
const { formatAmount } = transactionFormat();
const search = ref('');
const leader = ref(null);
const date = ref('');
const exportStatus = ref('')
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

function loadUsers(query, setOptions) {
    fetch('/member/getAllLeaders?query=' + query)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(user => {
                    return {
                        value: user.id,
                        label: user.name,
                        img: user.profile_photo
                    }
                })
            )
        });
}

const clearFilter = () => {
    search.value = '';
    date.value = '';
    leader.value = null;
}

const type = ref('Deposit');
const selectedTab = ref(0);
function changeTab(index) {
    selectedTab.value = index;
}

const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};

onMounted(() => {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    if (params.status === 'deposit'){
        selectedTab.value = 0;
        type.value = 'Deposit';
    } else if (params.status === 'withdrawal') {
        selectedTab.value = 1;
        type.value = 'Withdrawal';
    }
});

const exportTransaction = () => {
    exportStatus.value = 'yes'
}
</script>

<template>
    <AuthenticatedLayout title="Pending Transactions">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Pending Transactions
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Manage all pending transactions.
                    </p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Pending Deposits
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalPendingDeposits !== null">
                          $ {{ formatAmount(totalPendingDeposits ? totalPendingDeposits : 0) }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <CurrencyDollarCircleIcon class="text-success-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Pending Withdrawals
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalPendingWithdrawals !== null">
                          $ {{ formatAmount(totalPendingWithdrawals ? totalPendingWithdrawals : 0) }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-purple-200">
                    <CurrencyDollarCircleIcon class="text-purple-500 w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
                <div class="w-full">
                    <InputIconWrapper class="w-full">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input
                            withIcon
                            id="search"
                            type="text"
                            class="block w-full"
                            placeholder="Search"
                            v-model="search"
                        />
                    </InputIconWrapper>
                </div>
                <div class="w-full">
                    <Combobox
                        :load-options="loadUsers"
                        v-model="leader"
                        placeholder="Leader"
                        image
                    />
                </div>
                <div class="w-full">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                    />
                </div>
                <div class="w-full flex justify-end gap-4 items-center">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="clearFilter"
                    >
                        <span class="text-lg">Clear</span>
                    </Button>
                    <Button
                        type="button"
                        variant="gray"
                        class="flex gap-1 justify-center"
                        @click="exportTransaction"
                    >
                        <CloudDownloadIcon class="w-5 h-5" />
                        Export
                    </Button>
                </div>
            </div>
        </div>

        <div class="w-full">
            <TabGroup :selectedIndex="selectedTab" @change="changeTab">
                <TabList class="flex space-x-1 max-w-md rounded-xl bg-gray-200 dark:bg-gray-900 p-1">
                    <Tab
                        v-for="type in transactionTypes"
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                                'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                'ring-white/60 ring-offset-2 ring-offset-primary-400 dark:ring-offset-gray-400 focus:outline-none focus:ring-2',
                                selected
                                ? 'bg-white dark:bg-gray-700 text-primary-800 dark:text-white shadow'
                                : 'text-gray-600 hover:bg-white/[0.12] hover:text-primary-500',
                            ]"
                            @click="updateTransactionType(type.value)"
                        >
                            {{ type.label }}
                        </button>
                    </Tab>
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        v-for="transactionType in transactionTypes"
                    >
                        <template v-if="transactionType.value === 'Deposit'">
                            <PendingDeposit
                                :search="search"
                                :leader="leader"
                                :date="date"
                                :type="type"
                                :exportStatus="exportStatus"
                                @update:totalPendingDeposits="totalPendingDeposits = $event"
                                @update:totalPendingWithdrawals="totalPendingWithdrawals = $event"
                                @update:exportStatus="exportStatus = $event"
                            />
                        </template>

                        <template v-else>
                            <PendingWithdrawal
                                :search="search"
                                :leader="leader"
                                :date="date"
                                :type="type"
                                :exportStatus="exportStatus"
                                @update:totalPendingDeposits="totalPendingDeposits = $event"
                                @update:totalPendingWithdrawals="totalPendingWithdrawals = $event"
                                @update:exportStatus="exportStatus = $event"
                            />
                        </template>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>
    </AuthenticatedLayout>
</template>
