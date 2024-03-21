<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import AddTnc from "@/Pages/Setting/Tnc/Partials/AddTnc.vue";
import TncTable from "@/Pages/Setting/Tnc/Partials/TncTable.vue";
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import { ref, watch } from "vue"
import { SearchIcon } from "@heroicons/vue/solid"
import InputIconWrapper from "@/Components/InputIconWrapper.vue";

const refresh = ref(false);
const isLoading = ref(false);
const search = ref('');
const date = ref('');
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

function refreshTable() {
    search.value = '';
    date.value = '';
    filter.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

</script>


<template>
    <AuthenticatedLayout title="T&C Setting">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    T&C Setting
                </h2>

                <div>
                    <AddTnc/>
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
                T&C Details
            </div>
            <div class="w-full">
                <TncTable
                    :refresh="refresh"
                    :isLoading="isLoading"
                    :search="search"
                    :date="date"
                    @update:loading="isLoading = $event"
                    @update:refresh="refresh = $event"
                    @update:export="exportStatus = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>