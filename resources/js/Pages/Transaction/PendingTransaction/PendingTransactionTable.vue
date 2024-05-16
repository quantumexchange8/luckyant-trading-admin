<script setup>
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import {onMounted, ref} from "vue";
import PendingDeposit from "@/Pages/Transaction/PendingTransaction/PendingDeposit.vue";
import PendingWithdrawal from "@/Pages/Transaction/PendingTransaction/PendingWithdrawal.vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    exportStatus: Boolean,
    leader: Object,
})

const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const type = ref('Deposit');
const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};

onMounted(() => {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    if (params.status === 'deposit'){
        selectedTab.value = 0;
    } else if (params.status === 'withdrawal') {
        selectedTab.value = 1;
    }
});

const selectedTab = ref(0);
function changeTab(index) {
    selectedTab.value = index;
}
</script>

<template>
    <TabGroup :selectedIndex="selectedTab" @change="changeTab">
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
                                ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                : 'border-b border-gray-300 dark:border-gray-700',
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
                                ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                : 'border-b border-gray-300 dark:border-gray-700',
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
                    :leader="leader"
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
                    :leader="leader"
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
