<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import ApplicationListView from "@/Pages/Application/Listing/ApplicationListView.vue";
import CreateApplicationForm from "@/Pages/Application/Listing/Partials/CreateApplicationForm.vue";

defineProps({
    applicationsCount: Number,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.application_listing')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <h2 class="text-2xl font-semibold leading-tight">
                    {{ $t('public.application_listing') }}
                </h2>

                <CreateApplicationForm />
            </div>
        </template>

        <ApplicationListView
            :applicationsCount="applicationsCount"
        />
    </AuthenticatedLayout>
</template>
