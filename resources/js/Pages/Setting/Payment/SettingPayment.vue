<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {useForm} from "@inertiajs/vue3";
import {
  RadioGroup, RadioGroupLabel, RadioGroupOption,
  Tab, TabGroup, TabList, TabPanel, TabPanels
} from '@headlessui/vue'
import { ref, watch } from "vue"
import { CogIcon, SearchIcon, RefreshIcon } from "@heroicons/vue/solid"
import Modal from "@/Components/Modal.vue";
import PaymentHistory from "@/Pages/Setting/Payment/Partials/PaymentHistory.vue";
import PaymentActive from "@/Pages/Setting/Payment/Partials/PaymentActive.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import AddPaymentMethod from "@/Pages/Setting/Payment/Partials/AddPaymentMethod.vue";

const props = defineProps({
    countries: Array,
    paymentDetails: Object,
    paymentHistories: Array,
})

const refresh = ref(false);
const isLoading = ref(false);
const search = ref('');
const date = ref('');
const filter = ref('');
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const transactionVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}

function refreshTable() {
    search.value = '';
    date.value = '';
    filter.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

const type = ref('Active');
const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};
</script>

<template>
    <AuthenticatedLayout title="Setting Payment">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Payment Setting
                </h2>

                <div>
                    <AddPaymentMethod
                        :countries="countries"
                        :paymentDetails="paymentDetails"
                    />
                </div>
            </div>

        </template>

        <div class="pt-3 md:flex md:justify-end items-center">
            <div class="flex flex-wrap items-center md:flex-nowrap gap-3 mt-3 md:mt-0">
                <div class="w-full">
                    <InputIconWrapper class="w-full md:w-[280px]">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
                    </InputIconWrapper>
                </div>
                <div class="w-full">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                        class="w-full"
                    />
                </div>
                <!-- <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full md:w-[155px] dark:text-white dark:bg-gray-600"
                        v-model="filter"
                        :options="statusList"
                        placeholder="Filter status"
                    />
                </div> -->
                <div>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="refreshTable"
                    >
                        Clear
                    </Button>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900 my-8">
            <div class="text-lg font-semibold pb-4">
                Payment Details
            </div>
            <div class="w-full">
                <TabGroup>
                    <TabList class="max-w-md flex py-1">
                        <Tab
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                @click="updateTransactionType('Active')"
                                :class="[
                                    'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                    selected
                                        ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                        : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                            >
                                Active Payment
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
                                Payment History
                            </button>
                        </Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel>
                            <PaymentActive
                                :paymentHistories="paymentHistories"
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :filter="filter"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                                @update:export="exportStatus = $event"
                            />

                        </TabPanel>
                        <TabPanel>
                            <PaymentHistory
                                :paymentHistories="paymentHistories"
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :filter="filter"
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
