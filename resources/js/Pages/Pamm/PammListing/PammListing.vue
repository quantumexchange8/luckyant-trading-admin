<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import PammListingTable from "@/Pages/Pamm/PammListing/PammListingTable.vue";
import PammOverview from "@/Pages/Pamm/PammListing/PammOverview.vue";

defineProps({
    subscriptionsCount: Number
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pamm_listing')">
        <template #header>
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        {{ $t('public.pamm_listing') }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <!-- Subscription Overview -->
            <PammOverview
                :subscriptionsCount="subscriptionsCount"
            />

            <!-- Subscription Table -->
            <PammListingTable
            />
        </div>
    </AuthenticatedLayout>
</template>
