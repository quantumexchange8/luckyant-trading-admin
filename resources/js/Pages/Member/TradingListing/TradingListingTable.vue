<script setup>
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import Loading from "@/Components/Loading.vue";
import Button from "@/Components/Button.vue";
import AllTradingAccount from "@/Pages/Member/TradingListing/Partials/AllTradingAccount.vue"

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    exportStatus: Boolean,
    leverageSel: Array,
})

</script>

<template>
    <div>
        <TabGroup>
            <TabList class="max-w-52 flex py-1">
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
                        Trading Account
                    </button>
                </Tab>
            </TabList>
            <TabPanels>
                <TabPanel>
                    <AllTradingAccount
                    :leverageSel="leverageSel"
                    :refresh="refresh"
                    :isLoading="isLoading"
                    :search="search"
                    :date="date"
                    :exportStatus="exportStatus"
                    @update:loading="isLoading = $event"
                    @update:refresh="refresh = $event"
                    @update:export="exportStatus = $event"
                    />
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>

</template>