<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import PendingPammTable from "@/Pages/Pamm/PendingPamm/PendingPammTable.vue";

defineProps({
    pendingSubscriptionsCount: Number
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pending_pamm_request')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        {{ $t('public.pending_pamm_request') }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <PendingPammTable
                :pendingSubscriptionsCount="pendingSubscriptionsCount"
            />
        </div>
    </AuthenticatedLayout>
</template>
