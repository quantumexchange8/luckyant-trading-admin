<script setup>
import {transactionFormat} from "@/Composables/index.js";
import Action from "@/Pages/Member/MemberDetails/Partials/Action.vue";
import WalletAdjustment from "@/Pages/Member/MemberDetails/Partials/WalletAdjustment.vue";

const props = defineProps({
    member: Object,
    wallets: Object,
});

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="overflow-x-auto grid grid-flow-col justify-start relative gap-5">
        <div v-for="wallet in props.wallets" class="flex flex-col gap-3 overflow-hidden rounded-lg w-80 border border-gray-00 dark:border-gray-800">
            <div
                class="flex justify-between"
                :class="{
                    'bg-primary-500 dark:bg-primary-800': wallet.type === 'cash_wallet',
                    'bg-purple-500 dark:bg-purple-700': wallet.type === 'bonus_wallet',
                    'bg-gray-500 dark:bg-gray-700': wallet.type === 'e_wallet',
                }"
            >
                <div class="py-5 px-4 flex flex-col w-full">
                    <div class="flex flex-col gap-3 w-full">
                        <div class="flex w-full items-center justify-between">
                            <div class="text-base font-semibold text-gray-100 dark:text-white">
                                {{ wallet.name }}
                            </div>
                            <WalletAdjustment
                                :wallet="wallet"
                            />
                        </div>
                        <div
                            :class="[
                                'flex flex-col gap-1 self-stretch p-3 w-full',
                                {
                                    'bg-primary-100 dark:bg-primary-900': wallet.type === 'cash_wallet',
                                    'bg-purple-100 dark:bg-purple-800': wallet.type === 'bonus_wallet',
                                    'bg-gray-100 dark:bg-gray-800': wallet.type === 'e_wallet',
                                }
                            ]"
                        >
                            <div class="text-xs font-medium">
                                {{ $t('public.balance') }}
                            </div>
                            <div class="text-lg font-bold">
                                $ {{ formatAmount(wallet.balance) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
