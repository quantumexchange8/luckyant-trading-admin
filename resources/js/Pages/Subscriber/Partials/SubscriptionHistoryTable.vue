<script setup>
import Loading from "@/Components/Loading.vue";
import {computed, onUnmounted, ref, watch, watchEffect} from "vue";
import SubscriptionRenewal from "@/Pages/Subscription/Partials/SubscriptionRenewal.vue";
import SubscriptionHistory from "@/Pages/Subscription/Partials/SubscriptionHistory.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    filter: String,
    date: String,
    exportStatus: Boolean,
    leader: Object,
})

const type = ref('Renewal');
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);

const updateSubscriptionType = (subscription_type) => {
    type.value = subscription_type
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
                    @click="updateSubscriptionType('History')"
                    :class="[
                              'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                              'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                               selected
                                ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                : 'border-b border-gray-300 dark:border-gray-700',
                           ]"
                >
                    Subscription History
                </button>
            </Tab>
        </TabList>
        <TabPanels>
            
            <TabPanel>
                <SubscriptionHistory
                    :refresh="refresh"
                    :isLoading="isLoading"
                    :search="search"
                    :date="date"
                    :filter="filter"
                    :leader="leader"
                    :exportStatus="exportStatus"
                    @update:loading="$emit('update:loading', $event)"
                    @update:refresh="$emit('update:refresh', $event)"
                />
            </TabPanel>
        </TabPanels>
    </TabGroup>
</template>