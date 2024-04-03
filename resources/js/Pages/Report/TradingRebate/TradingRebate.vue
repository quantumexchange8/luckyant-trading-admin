<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import TanstackTable from "@/Components/TanstackTable.vue";
</script>

<template>
    <AuthenticatedLayout title="Transaction History">
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Trading Rebate
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Maximize Returns: Explore Your Trading Rebates Report
                    </p>
                </div>
            </div>
        </template>

        <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <TanstackTable />
        </div>
    </AuthenticatedLayout>
</template>
