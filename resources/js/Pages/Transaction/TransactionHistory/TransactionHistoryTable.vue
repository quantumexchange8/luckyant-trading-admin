<script setup>
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import {ref} from "vue";
import DepositHistory from "@/Pages/Transaction/TransactionHistory/DepositHistory.vue";
import WithdrawalHistory from "@/Pages/Transaction/TransactionHistory/WithdrawalHistory.vue";
import WalletAdjustmentHistory from "@/Pages/Transaction/TransactionHistory/WalletAdjustmentHistory.vue";
import InternalTransferHistory from "@/Pages/Transaction/TransactionHistory/InternalTransferHistory.vue";


const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    filter: String,
    exportStatus: Boolean,
})

const type = ref('Deposit');

const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);

const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};
</script>

<template>
    <div class="flex gap-4 mb-5">
        <span class="flex items-center text-xs font-normal text-gray-900 dark:text-white"><span class="flex w-2 h-2 bg-green-500 dark:bg-error-500 rounded-full mr-2 flex-shrink-0"></span>Rejected</span>
        <span class="flex items-center text-xs font-normal text-gray-900 dark:text-white"><span class="flex w-2 h-2 bg-red-500 dark:bg-success-500 rounded-full mr-2 flex-shrink-0"></span>Success</span>
    </div>
    <TabGroup>
        <TabList class="max-w-xl flex py-1">
            <Tab
                as="template"
                v-slot="{ selected }"
            >
                <button
                    @click="updateTransactionType('Deposit')"
                    :class="[
                              'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                              'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                              selected
                                ? 'dark:text-white border-b-2'
                                : 'border-b border-gray-400',
                           ]"
                >
                    Deposit
                </button>
            </Tab>
            <Tab
                as="template"
                v-slot="{ selected }"
            >
                <button
                    @click="updateTransactionType('Withdrawal')"
                    :class="[
                              'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                              'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                              selected
                                ? 'dark:text-white border-b-2'
                                : 'border-b border-gray-400',
                           ]"
                >
                    Withdrawal
                </button>
            </Tab>
            <Tab
                as="template"
                v-slot="{ selected }"
            >
                <button
                    @click="updateTransactionType('WalletAdjustment')"
                    :class="[
                              'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                              'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                              selected
                                ? 'dark:text-white border-b-2'
                                : 'border-b border-gray-400',
                           ]"
                >
                    Wallet Adjustment
                </button>
            </Tab>
            <Tab
                as="template"
                v-slot="{ selected }"
            >
                <button
                    @click="updateTransactionType('InternalTransfer')"
                    :class="[
                              'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                              'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                              selected
                                ? 'dark:text-white border-b-2'
                                : 'border-b border-gray-400',
                           ]"
                >
                    Internal Transfer
                </button>
            </Tab>
        </TabList>
        <TabPanels>
            <TabPanel>
                <DepositHistory
                    :refresh="refresh"
                    :isLoading="isLoading"
                    :search="search"
                    :date="date"
                    :filter="filter"
                    :exportStatus="exportStatus"
                    @update:loading="$emit('update:loading', $event)"
                    @update:refresh="$emit('update:refresh', $event)"
                    @update:export="$emit('update:export', $event)"
                />
            </TabPanel>
            <TabPanel>
                <WithdrawalHistory
                    :refresh="refresh"
                    :isLoading="isLoading"
                    :search="search"
                    :date="date"
                    :filter="filter"
                    :exportStatus="exportStatus"
                    @update:loading="$emit('update:loading', $event)"
                    @update:refresh="$emit('update:refresh', $event)"
                    @update:export="$emit('update:export', $event)"
                />
            </TabPanel>
            <TabPanel>
                <WalletAdjustmentHistory
                    :refresh="refresh"
                    :isLoading="isLoading"
                    :search="search"
                    :date="date"
                    :exportStatus="exportStatus"
                    @update:loading="$emit('update:loading', $event)"
                    @update:refresh="$emit('update:refresh', $event)"
                    @update:export="$emit('update:export', $event)"
                />
            </TabPanel>
            <TabPanel>
                <InternalTransferHistory
                    :refresh="refresh"
                    :isLoading="isLoading"
                    :search="search"
                    :date="date"
                    :exportStatus="exportStatus"
                    @update:loading="$emit('update:loading', $event)"
                    @update:refresh="$emit('update:refresh', $event)"
                    @update:export="$emit('update:export', $event)"
                />
            </TabPanel>
        </TabPanels>
    </TabGroup>
</template>
