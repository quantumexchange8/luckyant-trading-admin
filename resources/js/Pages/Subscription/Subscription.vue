<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import {ref} from "vue";
import {CloudDownloadIcon} from "@/Components/Icons/outline.jsx";
import {SearchIcon, RefreshIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import PendingSubscribeTable from "@/Pages/Subscription/Partials/PendingSubscribeTable.vue";

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

function refreshTable() {
    search.value = '';
    date.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

const exportTransaction = () => {
    exportStatus.value = true;
}
</script>

<template>
    <AuthenticatedLayout title="Subscription">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Subscription Request
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Manage all pending subscription.
                    </p>
                </div>

                <div>
                    <Button
                        type="button"
                        class="justify-center w-full gap-2 border border-gray-600 text-white text-sm dark:hover:bg-gray-600"
                        variant="transparent"
                        v-slot="{ iconSizeClasses }"
                        @click="exportTransaction"
                    >
                        <div class="inline-flex items-center">
                            <CloudDownloadIcon
                                aria-hidden="true"
                                class="mr-2 w-5 h-5"
                            />
                            <span class="text-black dark:text-gray-300">Export as Excel</span>
                        </div>
                    </Button>
                </div>

            </div>
        </template>

        <div class="pt-3 md:flex md:justify-end items-center">
            <div class="flex flex-wrap md:flex-nowrap md:items-center gap-3 mt-3 md:mt-0">
                <div class="w-full col-span-5 md:col-span-2">
                    <InputIconWrapper>
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
                    </InputIconWrapper>
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                    />
                </div>
                <div>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="refreshTable"
                        class="w-full md:w-auto flex items-center justify-center px-3 py-2 border border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                    >
                        Clear
                    </Button>
                </div>
            </div>
        </div>

        <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <PendingSubscribeTable
                :refresh="refresh"
                :isLoading="isLoading"
                :search="search"
                :date="date"
                :exportStatus="exportStatus"
                @update:loading="isLoading = $event"
                @update:refresh="refresh = $event"
                @update:export="exportStatus = $event"
            />
        </div>
    </AuthenticatedLayout>
</template>