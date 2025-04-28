<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AllocationTable from "@/Pages/WorldPool/Allocation/AllocationTable.vue";
import CurrentPool from "@/Pages/WorldPool/Allocation/CurrentPool.vue";

defineProps({
    active_pamm_capital: Number,
    active_subscriptions_capital: Number,
    extra_fund_sum: Number,
    world_pool: Object,
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
                :active_pamm_capital="active_pamm_capital"
                :active_subscriptions_capital="active_subscriptions_capital"
                :extra_fund_sum="extra_fund_sum"
                :world_pool="world_pool"
            />

            <!-- Allocation Table -->
            <AllocationTable
                :active_pamm_capital="active_pamm_capital"
                :active_subscriptions_capital="active_subscriptions_capital"
                :extra_fund_sum="extra_fund_sum"
            />
        </div>
    </AuthenticatedLayout>
</template>
