<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AllocationTable from "@/Pages/WorldPool/Allocation/AllocationTable.vue";
import CurrentPool from "@/Pages/WorldPool/Allocation/CurrentPool.vue";

defineProps({
    last_allocate_date: String,
    world_pool: Object,
    active_subscriptions_capital: String
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.world_pool_allocation')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <h2 class="text-2xl font-semibold leading-tight">
                    {{ $t('public.world_pool_allocation') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <!-- Current World Pool -->
            <CurrentPool
                :world_pool="world_pool"
                :active_subscriptions_capital="active_subscriptions_capital"
            />

            <!-- Allocation Table -->
            <AllocationTable
                :last_allocate_date="last_allocate_date"
            />
        </div>
    </AuthenticatedLayout>
</template>
