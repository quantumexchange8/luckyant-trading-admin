<script setup>
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import {ref} from "vue";
import PendingDeposit from "@/Pages/Transaction/PendingTransaction/PendingDeposit.vue";
import PendingWithdrawal from "@/Pages/Transaction/PendingTransaction/PendingWithdrawal.vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    exportStatus: Boolean,
})

const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const type = ref('Deposit');
const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};
</script>

<template>
    <TabGroup>
        <TabList class="max-w-xs flex py-1">
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
        </TabList>
        <TabPanels>
            <TabPanel>
                <PendingDeposit
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
                <PendingWithdrawal
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
