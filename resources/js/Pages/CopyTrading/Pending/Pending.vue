<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import {h, ref} from "vue";
import PendingSubscription from "@/Pages/CopyTrading/Pending/PendingSubscription/PendingSubscription.vue";

const props = defineProps({
    pendingSubscriptionsCount: Number
});

const tabs = ref([
    {
        title: 'subscription',
        component: h(PendingSubscription, {
            pendingSubscriptionsCount: props.pendingSubscriptionsCount,
        }),
        value: '0'
    },
    {
        title: 'renewal',
        value: '1'
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
                    {{ $t(`public.${tab.title}`) }}
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
