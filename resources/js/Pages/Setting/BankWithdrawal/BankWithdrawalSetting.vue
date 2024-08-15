<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import {h, ref, watch, watchEffect, onMounted } from "vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import TanStackTable from "@/Components/TanStackTable.vue";
import debounce from "lodash/debounce.js";
import {
  Tab, TabGroup, TabList, TabPanel, TabPanels
} from '@headlessui/vue'
import ActiveLeaders from "@/Pages/Setting/BankWithdrawal/ActiveLeaders.vue";
import InactiveLeaders from "@/Pages/Setting/BankWithdrawal/InactiveLeaders.vue";

const search = ref('');
const status = ref('Active');
const updateWithdrawalStatus = (withdrawal_status) => {
    status.value = withdrawal_status
};

</script>

<template>
<AuthenticatedLayout title="Bank Withdrawal Setting">
    <template #header>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight">
                Bank Withdrawal Setting
            </h2>
        </div>
    </template>

    <div class="flex justify-end items-center self-stretch pb-2">
        <div>
            <InputIconWrapper class="w-full md:w-[280px]">
                <template #icon>
                    <SearchIcon aria-hidden="true" class="w-5 h-5" />
                </template>
                <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
            </InputIconWrapper>
        </div>
    </div>

    <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900 my-8">
        <div class="text-lg font-semibold pb-4">
            Leader Listing
        </div>
        <div class="w-full">
            <TabGroup>
                <TabList class="max-w-md flex py-1">
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            @click="updateWithdrawalStatus('Active')"
                            :class="[
                                'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                            ]"
                        >
                            Active
                        </button>
                    </Tab>
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            @click="updateWithdrawalStatus('Inactive')"
                            :class="[
                                    'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                    selected
                                        ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                        : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                        >
                            Inactive
                        </button>
                    </Tab>
                </TabList>
                <TabPanels>
                    <TabPanel>
                        <ActiveLeaders
                            :search="search"
                            :status="status"
                        />
                    </TabPanel>
                    <TabPanel>
                        <InactiveLeaders
                            :search="search"
                            :status="status"
                        />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>
    </div>

</AuthenticatedLayout>
</template>