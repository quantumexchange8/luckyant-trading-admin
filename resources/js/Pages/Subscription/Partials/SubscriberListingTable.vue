<script setup>
import Loading from "@/Components/Loading.vue";
import {ref} from "vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import AllSubscriber from "@/Pages/Subscription/Partials/AllSubscriber.vue"

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
})

</script>

<template>
    <div>
        <TabGroup>
            <TabList class="max-w-xs flex py-1">
                <Tab
                    as="template"
                    v-slot="{ selected }"
                >
                    <button
                        :class="[
                                'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                            ]"
                    >
                        All Subscribers
                    </button>
                </Tab>
            </TabList>
            <TabPanels>
                <TabPanel>
                    <AllSubscriber/>
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>
</template>