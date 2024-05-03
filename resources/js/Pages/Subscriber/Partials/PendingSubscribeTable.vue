<script setup>
import Loading from "@/Components/Loading.vue";
import {ref} from "vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import PendingSubscriber from "@/Pages/Subscriber/Partials/PendingSubscribers.vue"
import SubscriptionRenewal from "@/Pages/Subscriber/Partials/SubscriptionRenewal.vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
})

const type = ref('pending_subscriber');
const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};

</script>

<template>
    <div>
        <TabGroup>
            <TabList class="max-w-md flex py-1">
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        @click="updateTransactionType('pending_subscriber')"
                        :class="[
                                'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                            ]"
                    >
                        Pending Subscriptions
                    </button>
                </Tab>
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        @click="updateTransactionType('subscriber_history')"
                        :class="[
                                'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                            ]"
                    >
                        Pending Renewal
                    </button>
                </Tab>
            </TabList>
            <TabPanels>
                <TabPanel>
                    <PendingSubscriber
                        :refresh="refresh"
                        :isLoading="isLoading"
                        :search="search"
                        :date="date"
                        @update:loading="$emit('update:loading', $event)"
                        @update:refresh="$emit('update:refresh', $event)"
                    />
                </TabPanel>
                <TabPanel>
                    <SubscriptionRenewal
                        :refresh="refresh"
                        :isLoading="isLoading"
                        :search="search"
                        :date="date"
                        @update:loading="$emit('update:loading', $event)"
                        @update:refresh="$emit('update:refresh', $event)"
                    />
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>
</template>
