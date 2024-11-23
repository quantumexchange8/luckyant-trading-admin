<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "primevue/button";
import MasterOverview from "@/Pages/Master/MasterListing/MasterOverview.vue";
import MasterListView from "@/Pages/Master/MasterListing/MasterListView.vue";
import AddMaster from "@/Pages/Master/MasterListing/Partials/AddMaster.vue";

defineProps({
    mastersCount: Number
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.master')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <h2 class="text-2xl font-semibold leading-tight">
                    {{ $t('public.master') }}
                </h2>

                <AddMaster />
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <!-- Master Overview -->
            <MasterOverview
                :mastersCount="mastersCount"
            />

            <!-- Master List -->
            <MasterListView
                :mastersCount="mastersCount"
            />
        </div>
    </AuthenticatedLayout>
</template>
