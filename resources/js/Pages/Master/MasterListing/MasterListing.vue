<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import { SearchIcon } from "@heroicons/vue/outline";
import MasterTable from "@/Pages/Master/MasterListing/Partials/MasterTable.vue";
import { ref } from "vue";

const search = ref('');
</script>

<template>
 <AuthenticatedLayout title="Master Listing">
    <template #header>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight">
                Master Listing
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
        </div>
    </div>

    <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900  my-8">
        <div class="w-full">
            <MasterTable
            :search="search"
            />
        </div>
    </div>
 </AuthenticatedLayout>

</template>