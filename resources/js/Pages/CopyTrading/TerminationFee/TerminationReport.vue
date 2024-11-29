<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import TerminationOverview from "@/Pages/CopyTrading/TerminationFee/TerminationOverview.vue";
import TerminationTable from "@/Pages/CopyTrading/TerminationFee/TerminationTable.vue";

defineProps({
    terminationsCount: Number,
    routeName: String,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.termination_report')">
        <template #header>
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        {{ $t('public.termination_report') }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <!-- Terminate Overview -->
            <TerminationOverview
                :terminationsCount="terminationsCount"
                :routeName="routeName"
            />

            <!-- Terminate Table -->
            <TerminationTable
                :routeName="routeName"
            />
        </div>
    </AuthenticatedLayout>
</template>
