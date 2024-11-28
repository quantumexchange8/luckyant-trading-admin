<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';
import {h, ref} from "vue";
import PendingSubscription from "@/Pages/CopyTrading/Pending/PendingSubscription/PendingSubscription.vue";

const props = defineProps({
    pendingSubscriptionsCount: Number,
    pendingRenewalCount: Number,
});

const tabs = ref([
    {
        title: 'subscription',
        component: h(PendingSubscription, {
            pendingSubscriptionsCount: props.pendingSubscriptionsCount,
        }),
        value: '0',
        count: props.pendingSubscriptionsCount
    },
    {
        title: 'renewal',
        value: '1',
        count: props.pendingRenewalCount
    },
]);
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pending')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        {{ $t('public.pending') }}
                    </h2>
                </div>
            </div>
        </template>

        <!-- Tabs -->
        <Tabs value="0">
            <TabList>
                <Tab v-for="tab in tabs" :key="tab.title" :value="tab.value">
                    <div class="flex items-center gap-2">
                        <span>{{ $t(`public.${tab.title}`) }}</span>
                        <Badge :value="tab.count" />
                    </div>
                </Tab>
            </TabList>
            <TabPanels>
                <TabPanel v-for="tab in tabs" :value="tab.value">
                    <component :is="tab.component" />
                </TabPanel>
            </TabPanels>
        </Tabs>
    </AuthenticatedLayout>
</template>
