<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {h, onMounted, ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import Badge from "primevue/badge";
import PendingDeposit from "@/Pages/Transaction/PendingTransaction/PendingDeposit/PendingDeposit.vue";
import PendingWithdrawal from "@/Pages/Transaction/PendingTransaction/PendingWithdrawal/PendingWithdrawal.vue";

const props = defineProps({
    pendingDepositCounts: Number,
    pendingWithdrawalCounts: Number,
})

const {formatType} = transactionFormat();

const tabs = ref([
    {
        type: 'Deposit',
        component: h(PendingDeposit),
        value: '0',
        count: props.pendingDepositCounts
    },
    {
        type: 'Withdrawal',
        component: h(PendingWithdrawal),
        value: '1',
        count: props.pendingWithdrawalCounts
    },
]);

const selectedType = ref('Deposit');
const activeIndex = ref('0');

onMounted(() => {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    if (params.status === 'deposit') {
        selectedType.value = 'Deposit';
        activeIndex.value = '0';
    } else if (params.status === 'withdrawal') {
        selectedType.value = 'Withdrawal';
        activeIndex.value = '1';
    }
});

// Watch for changes in selectedType and update the activeIndex accordingly
watch(activeIndex, (newIndex) => {
    const activeTab = tabs.value.find(tab => tab.value === newIndex);
    if (activeTab) {
        selectedType.value = activeTab.type;
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pending_transaction')">
        <template #header>
            <div class="text-xl font-semibold leading-tight">
                {{ $t('public.pending_transaction') }}
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <Tabs v-model:value="activeIndex" class="w-full">
                <TabList>
                    <Tab
                        v-for="tab in tabs"
                        :key="tab.type"
                        :value="tab.value"
                    >
                        <div class="flex items-center gap-2">
                            <span>{{ $t(`public.${formatType(tab.type).toLowerCase().replace(/\s+/g, '_')}`) }}</span>
                            <Badge :value="tab.count" />
                        </div>
                    </Tab>
                </TabList>
            </Tabs>

            <component
                :is="tabs[activeIndex]?.component"
                :selectedType="selectedType"
            />
        </div>
    </AuthenticatedLayout>
</template>
