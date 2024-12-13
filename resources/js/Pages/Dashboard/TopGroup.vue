<script setup>
import {transactionFormat} from "@/Composables/index.js";
import {ref} from "vue";
import Tag from "primevue/tag";

const { formatAmount } = transactionFormat();
const topGroups = ref(null);

const getWallets = async () => {
    try {
        const response = await axios.get('/getTopGroups');
        topGroups.value = response.data;
    } catch (error) {
        console.error('Error fetching top groups data:', error);
    }
};

getWallets();
</script>

<template>
    <div class="text-xl font-semibold">
        Top 5 Group Total Subscriptions
    </div>
    <div
        v-for="group in topGroups"
        class="flex flex-col gap-5 mt-5"
    >
        <div class="flex flex-col border border-gray-200 dark:border-gray-700 rounded-md p-4 gap-2">
            <div class="flex gap-2 items-center">
                <div class="flex justify-center items-center rounded-full bg-primary-400 grow-0 shrink-0 w-8 h-8">
                    <div class="font-semibold text-white text-lg">
                        {{ group.rank }}
                    </div>
                </div>
                <div class="flex flex-col">
                    <div class="flex gap-1 items-center">
                        <span class="font-semibold">{{ group.name }}</span>
                        <Tag severity="info" value="Leader" />
                    </div>
                    <div class="text-gray-400 dark:text-gray-500 text-xs">
                        {{ group.email }}
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 md:grid-cols-4 xl:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1 pt-2 items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Active Amount
                    </div>
                    <div class="text-success-500 font-semibold">
                        $ {{ formatAmount(group.total_meta_balance) }}
                    </div>
                </div>
                <div class="flex flex-col gap-1 pt-2 items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Real Fund
                    </div>
                    <div class="text-primary-500 font-semibold">
                        $ {{ formatAmount(group.total_real_fund) }}
                    </div>
                </div>
                <div class="flex flex-col gap-1 pt-2 items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Demo Fund
                    </div>
                    <div class="font-semibold">
                        $ {{ formatAmount(group.total_demo_fund) }}
                    </div>
                </div>
                <div class="flex flex-col gap-1 pt-2 items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Total Affiliate
                    </div>
                    <div>
                        {{ group.total_children }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
