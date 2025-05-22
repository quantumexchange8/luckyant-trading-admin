<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ref, watch} from "vue";
import ProfitSharingOverview from "@/Pages/Report/ProfitSharing/ProfitSharingOverview.vue";
import ProfitSharingTable from "@/Pages/Report/ProfitSharing/ProfitSharingTable.vue";
import TabList from "primevue/tablist";
import AddMember from "@/Pages/Member/Listing/Partials/AddMember.vue";
import Tab from "primevue/tab";
import Badge from "primevue/badge";
import MemberTable from "@/Pages/Member/Listing/MemberTable.vue";
import Tabs from "primevue/tabs";

const activeCapital = ref(null);
const totalProfitSharing = ref(null);

const handleUpdateTotals = (data) => {
    activeCapital.value = data.activeCapital;
    totalProfitSharing.value = data.totalProfitSharing;
};

const tabs = ref([
    {
        type: 'copytrade',
        value: '0',
    },
    {
        type: 'pamm',
        value: '1',
    },
]);

const selectedType = ref('copytrade');
const activeIndex = ref('0');

// Watch for changes in selectedType and update the activeIndex accordingly
watch(activeIndex, (newIndex) => {
    const activeTab = tabs.value.find(tab => tab.value === newIndex);
    if (activeTab) {
        selectedType.value = activeTab.type;
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.profit_sharing')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.profit_sharing') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <ProfitSharingOverview
                :activeCapital="activeCapital"
                :totalProfitSharing="totalProfitSharing"
            />

            <Tabs v-model:value="activeIndex" class="w-full">
                <TabList>
                    <Tab
                        v-for="tab in tabs"
                        :key="tab.type"
                        :value="tab.value"
                    >
                        {{ $t(`public.${tab.type}`) }}
                    </Tab>
                </TabList>
            </Tabs>

            <ProfitSharingTable
                :selectedType="selectedType"
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
