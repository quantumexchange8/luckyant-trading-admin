<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import DailyRegisterTable from "@/Pages/Report/DailyRegister/DailyRegisterTable.vue";
</script>

<template>
    <AuthenticatedLayout :title="$t('public.daily_register') ">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.daily_register') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <DailyRegisterTable />
        </div>
    </AuthenticatedLayout>
</template>
