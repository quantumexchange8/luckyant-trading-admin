<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "primevue/button";
import TabList from "primevue/tablist";
import Tab from "primevue/tab";
import Tabs from "primevue/tabs";
import {h, ref, watch} from "vue";
import MemberTable from "@/Pages/Member/Listing/MemberTable.vue";
import Badge from "primevue/badge";

const props = defineProps({
    kycCounts: Object,
})

const tabs = ref([
    {
        title: 'all',
        type: 'All',
        value: '0',
        count: props.kycCounts['Unverified'] + props.kycCounts['Verified'] + props.kycCounts['Pending']
    },
    {
        title: 'pending',
        type: 'Pending',
        value: '1',
        count: props.kycCounts['Pending'] ?? 0
    },
    {
        title: 'verified',
        type: 'Verified',
        value: '2',
        count: props.kycCounts['Verified']
    },
    {
        title: 'unverified',
        type: 'Unverified',
        value: '3',
        count: props.kycCounts['Unverified']
    },
]);

const selectedType = ref('All');
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
    <AuthenticatedLayout :title="$t('public.member_listing')">
        <template #header>
            <div class="text-xl font-semibold leading-tight">
                {{ $t('public.member_listing') }}
            </div>
        </template>

        <div class="flex flex-col gap-5 w-full">
            <div class="flex flex-col-reverse gap-3 items-center md:flex-row md:justify-between">
                <Tabs v-model:value="activeIndex" class="w-full">
                    <TabList>
                        <Tab
                            v-for="tab in tabs"
                            :key="tab.title"
                            :value="tab.value"
                        >
                            <div class="flex items-center gap-2">
                                <span>{{ $t(`public.${tab.title}`) }}</span>
                                <Badge :value="tab.count" />
                            </div>
                        </Tab>
                    </TabList>
                </Tabs>
<!--                <Button-->
<!--                    size="small"-->
<!--                    label="Add member"-->
<!--                    class="w-full md:max-w-32"-->
<!--                />-->
            </div>

            <MemberTable
                :kycStatus="selectedType"
            />
        </div>
    </AuthenticatedLayout>
</template>
