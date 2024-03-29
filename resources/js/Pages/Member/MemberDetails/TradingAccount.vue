<script setup>
import Button from "@/Components/Button.vue";
import Badge from "@/Components/Badge.vue";
import {transactionFormat} from "@/Composables/index.js";
import {CreditCardAddIcon} from "@/Components/Icons/outline.jsx";
import {onMounted, onUnmounted, ref} from "vue";
import {RefreshIcon} from "@heroicons/vue/solid";
import Loading from "@/Components/Loading.vue";

const props = defineProps({
    member_detail: Object,
    tradingAccounts: Object,
})

const { formatAmount } = transactionFormat();

const tradingAccounts = ref([]);
const countdown = ref(10);

const refreshData = async () => {
    try {
        const response = await axios.get('/member/refreshTradingAccountsData?id=' + props.member_detail.id);
        tradingAccounts.value = response.data;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    }
};

onMounted(() => {
    // Initial data fetch when the component is mounted
    refreshData();

    // Schedule periodic refresh every 10 seconds
    const intervalId = setInterval(() => {
        refreshData();
        countdown.value = 10; // Reset countdown
    }, 10000); // 10 seconds = 10000 milliseconds

    // Update countdown every second
    const countdownIntervalId = setInterval(() => {
        countdown.value -= 1;
    }, 1000); // 1 second = 1000 milliseconds

    // Clear intervals when component is unmounted
    onUnmounted(() => {
        clearInterval(intervalId);
        clearInterval(countdownIntervalId);
    });
});
</script>

<template>
    <div
        v-if="tradingAccounts.length === 0"
        class="flex flex-col items-start gap-3 border border-gray-300 dark:border-gray-600 rounded-lg p-5 animate-pulse w-96 mx-auto sm:mx-0 shadow-lg"
    >
        <div class="flex justify-between items-center self-stretch">
            <div class="flex items-center gap-3">
                <div class="flex items-center">
                    <div>
                        <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-32 mb-2"></div>
                        <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                    </div>
                </div>
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div class="flex items-center justify-between w-full">
            <div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
            </div>
            <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
        </div>
    </div>

    <div
        v-else
        class="overflow-x-auto grid grid-flow-col justify-start gap-5"
    >
        <div
            v-for="account in tradingAccounts"
            class="flex flex-col items-start gap-3 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-96 shadow-lg"
        >
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col items-start">
                        <div class="text-sm font-semibold">
                            {{ account.account_type.name }}
                        </div>
                        <div class="text-xs">
                            {{ account.meta_login }}
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <Badge variant="success">Active</Badge>
                </div>
            </div>
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3">
                    <div class="border-r pr-3 border-gray-400 dark:border-gray-600 text-xs font-normal">
                        {{ account.margin_leverage }}
                    </div>
                    <div class="text-xs font-normal">
                        Credit: $ {{ formatAmount(account.credit ? account.credit : 0) }}
                    </div>
                </div>
                <div class="text-xl">
                    $ {{ formatAmount(account.balance ? account.balance : 0) }}
                </div>
            </div>
            <div class="flex items-center gap-10 w-full">
                <div class="flex items-center gap-3">
<!--                    <Action-->
<!--                        :account="account"-->
<!--                        :walletSel="walletSel"-->
<!--                        :tradingAccounts="tradingAccounts"-->
<!--                    />-->
                </div>
                <div class="flex items-center gap-2 justify-end w-full"> <!-- Adjust the width as needed -->
                    <Loading class="w-5 h-5" />
                    <div class="text-xs">Refreshing in {{ countdown }}s</div>
                </div>
            </div>
        </div>

    </div>
</template>
