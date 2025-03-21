<script setup>
import {transactionFormat} from "@/Composables/index.js";
import Action from "@/Pages/Member/MemberDetails/Partials/Action.vue";

const props = defineProps({
    member: Object,
    wallets: Object,
});

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="overflow-x-auto grid grid-flow-col justify-start relative gap-5">
        <div v-for="wallet in props.wallets" class="flex flex-col overflow-hidden rounded-[20px] w-96 border border-gray-00 dark:border-gray-800">
            <div
                class="flex justify-between h-32"
                :class="{
                            'bg-gradient-to-bl from-primary-400 to-primary-600': wallet.type === 'cash_wallet',
                            'bg-gradient-to-bl from-purple-300 to-purple-500': wallet.type === 'bonus_wallet',
                            'bg-gradient-to-bl from-gray-300 to-gray-500': wallet.type === 'e_wallet',
                        }"
            >
                <div class="py-5 px-4 flex flex-col gap-2">
                    <div class="flex flex-col">
                        <div class="text-base font-semibold text-gray-100 dark:text-white">
                            {{ wallet.name }}
                        </div>
                        <div class="text-xl font-semibold text-gray-100 dark:text-white">
                            $ {{ formatAmount(wallet.balance) }}
                        </div>
                    </div>
                    <div class="h-6">
                        <Action
                            type="wallet"
                            :member_detail="member"
                            :wallet="wallet"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
