<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import {onMounted, ref} from "vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import { RefreshIcon, SearchIcon } from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import AdminTable from "@/Pages/Admin/Partials/AdminTable.vue";

const search = ref('');
const isLoading = ref(false);
const refresh = ref(false);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

</script>

<template>
<AuthenticatedLayout title="Admin">
    <template #header>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight">
                    Admin
                </h2>
            </div>
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
        </div>
    </div>

    <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900  my-8">
        <div class="w-full">
            <AdminTable/>
        </div>
    </div>
</AuthenticatedLayout>

</template>