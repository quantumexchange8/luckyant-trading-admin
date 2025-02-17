<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import SubscriptionOverview from "@/Pages/CopyTrading/Listing/SubscriptionOverview.vue";
import SubscriptionTable from "@/Pages/CopyTrading/Listing/SubscriptionTable.vue";
import {ref} from "vue";

defineProps({
    subscriptionBatchesCount: Number
})

const filters = ref({
    global: null,
    master_meta_login: null,
    leader_id: null,
    start_join_date: null,
    end_join_date: null,
    start_terminate_date: null,
    end_terminate_date: null,
    fund_type: null,
    status: null,
});

const handleOverview = (data) => {
    filters.value['global'] = data.global?.value || null;
    filters.value['master_meta_login'] = data.master_meta_login?.value
        ? data.master_meta_login.value['meta_login']
        : null;
    filters.value['leader_id'] = data.leader_id?.value || null;
    filters.value['start_join_date'] = data.start_join_date?.value || null;
    filters.value['end_join_date'] = data.end_join_date?.value || null;
    filters.value['start_terminate_date'] = data.start_terminate_date?.value || null;
    filters.value['end_terminate_date'] = data.end_terminate_date?.value || null;
    filters.value['fund_type'] = data.fund_type?.value || null;
    filters.value['status'] = data.status?.value || null;
};
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
                :filters="filters"
                :subscriptionBatchesCount="subscriptionBatchesCount"
            />

            <!-- Subscription Table -->
            <SubscriptionTable
                :subscriptionBatchesCount="subscriptionBatchesCount"
                @update:filters="handleOverview"
            />
        </div>
    </AuthenticatedLayout>
</template>
