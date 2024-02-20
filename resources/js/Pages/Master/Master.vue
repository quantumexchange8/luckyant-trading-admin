<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import {onMounted, ref} from "vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import { RefreshIcon, SearchIcon } from "@heroicons/vue/outline";
import Input from "@/Components/Input.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import PendingRequest from "@/Pages/Master/Partials/PendingRequest.vue";
import RequestHistory from "@/Pages/Master/Partials/RequestHistory.vue";
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";

const refresh = ref(false);
const isLoading = ref(false);
const search = ref('');
const date = ref('');
const filter = ref('');
const exportStatus = ref(false);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const statusList = [
    {value:'Success', label:"Success"},
    {value:'Rejected', label:"Rejected"},
];

function refreshTable() {
    search.value = '';
    date.value = '';
    filter.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

const exportTransaction = () => {
    exportStatus.value = true;
}
// const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const type = ref('Pending');
const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};

</script>

<template>
    <AuthenticatedLayout title="Master">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Master Request
                </h2>
            </div>
        </template>

        <div class="pt-3 md:flex md:justify-end items-center">
            <div class="flex items-center gap-5">
                <div class="w-full lg:w-[280px]">
                    <InputIconWrapper class="md:col-span-2">
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
                <div class="w-full md:w-[240px]">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                    />
                </div>
                
                <div v-if="type != 'Pending'" class="w-full md:w-auto">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full md:w-[155px] dark:text-white dark:bg-gray-600"
                        v-model="filter"
                        :options="statusList"
                        placeholder="Filter status"
                    />
                </div>
                <div class="md:w-auto">
                    <Button
                        type="button"
                        variant="secondary"
                        size="lg"
                        @click="refreshTable"
                    >
                        <span class="text-lg">Clear</span>
                    </Button>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900  my-8">
            <div class="w-full">
                <TabGroup>
                    <TabList class="max-w-md flex py-1">
                        <Tab
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                @click="updateTransactionType('Pending')"
                                :class="[
                                    'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                    selected
                                        ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                        : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                            >
                                Pending Request
                            </button>
                        </Tab>
                        <Tab
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                @click="updateTransactionType('History')"
                                :class="[
                                        'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                        'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                        selected
                                            ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                            : 'border-b border-gray-300 dark:border-gray-700',
                                    ]"
                            >
                                Master Request History
                            </button>
                        </Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel>
                            <PendingRequest
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                            />
                        </TabPanel>
                        <TabPanel>
                            <RequestHistory
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :filter="filter"
                                :exportStatus="exportStatus"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                                @update:export="exportStatus = $event"
                            />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </AuthenticatedLayout>
</template>