<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import IncentiveOverview from "@/Pages/Report/PerformanceIncentive/IncentiveOverview.vue";
import IncentiveTable from "@/Pages/Report/PerformanceIncentive/IncentiveTable.vue";
import {ref} from "vue";

const totalUser = ref(null);
const totalPerformanceIncentive = ref(null);

const handleUpdateTotals = (data) => {
    totalUser.value = data.totalUser;
    totalPerformanceIncentive.value = data.totalPerformanceIncentive;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.performance_incentive')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.performance_incentive') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <IncentiveOverview
                :totalUser="totalUser"
                :totalPerformanceIncentive="totalPerformanceIncentive"
            />

            <IncentiveTable
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
