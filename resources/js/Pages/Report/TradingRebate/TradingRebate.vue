<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
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
    </AuthenticatedLayout>
</template>
