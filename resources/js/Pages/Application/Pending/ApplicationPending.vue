<script setup>
import Card from "primevue/card";
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import NoData from "@/Components/NoData.vue";
import ApplicantTable from "@/Pages/Application/Pending/ApplicantTable.vue";

defineProps({
    applicantsCount: Number,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pending_application')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <h2 class="text-2xl font-semibold leading-tight">
                    {{ $t('public.pending_application') }}
                </h2>
            </div>
        </template>

        <div v-if="applicantsCount > 0">
            <Card class="w-full">
                <template #content>
                    <ApplicantTable
                    />
                </template>
            </Card>
        </div>

        <div v-else class="flex items-center justify-center w-full">
            <NoData />
        </div>

    </AuthenticatedLayout>
</template>
