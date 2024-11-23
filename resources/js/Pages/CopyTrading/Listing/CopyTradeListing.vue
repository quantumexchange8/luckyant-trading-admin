<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import SubscriptionOverview from "@/Pages/CopyTrading/Listing/SubscriptionOverview.vue";
import SubscriptionTable from "@/Pages/CopyTrading/Listing/SubscriptionTable.vue";

defineProps({
    subscriptionBatchesCount: Number
})
</script>

<template>
    <AuthenticatedLayout title="Copy Trading Listing">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Copy Trading Listing
                    </h2>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <!-- Subscription Overview -->
            <SubscriptionOverview
                :subscriptionBatchesCount="subscriptionBatchesCount"
            />

            <!-- Subscription Table -->
            <SubscriptionTable
                :subscriptionBatchesCount="subscriptionBatchesCount"
            />
        </div>
    </AuthenticatedLayout>
</template>
