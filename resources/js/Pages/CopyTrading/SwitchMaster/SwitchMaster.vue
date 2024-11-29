<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import SwitchMasterTable from "@/Pages/CopyTrading/SwitchMaster/SwitchMasterTable.vue";
import NoData from "@/Components/NoData.vue";

defineProps({
    switchMastersCount: Number
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.switch_master')">
        <template #header>
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        {{ $t('public.switch_master') }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5 w-full">
            <div v-if="switchMastersCount > 0" class="w-full">
                <SwitchMasterTable />
            </div>
            <div v-else>
                <NoData />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
