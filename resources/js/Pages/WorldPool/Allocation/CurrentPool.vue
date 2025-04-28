<script setup>
import Card from "primevue/card";
import {transactionFormat} from "@/Composables/index.js";

defineProps({
    active_pamm_capital: Number,
    active_subscriptions_capital: Number,
    extra_fund_sum: Number,
    world_pool: Object,
});

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
        <Card class="w-full">
            <template #content>
                <div class="flex flex-col gap-3 items-center p-5 self-stretch">
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[200px] text-gray-500 text-xs font-medium">
                            {{ $t('public.total_capital') }}
                        </div>
                        <div class="text-primary-500 text-sm font-bold">
                            ${{ formatAmount(active_pamm_capital + active_subscriptions_capital) }}
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[200px] text-gray-500 text-xs font-medium">
                            {{ $t('public.copy_trade') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            ${{ formatAmount(active_subscriptions_capital) }}
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[200px] text-gray-500 text-xs font-medium">
                            {{ $t('public.pamm') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            ${{ formatAmount(active_pamm_capital) }}
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[200px] text-gray-500 text-xs font-medium">
                            {{ $t('public.extra_fund') }} ({{ $t('public.until_today') }})
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            ${{ formatAmount(extra_fund_sum) }}
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <div class="flex flex-col items-center gap-5 self-stretch w-full">
            <Card
                class="w-full"
                v-for="(amount, rank) in world_pool"
            >
                <template #content>
                    <div class="flex flex-col items-center self-stretch">
                        <span class="text-lg font-semibold dark:text-white">${{ formatAmount(amount) }}</span>
                        <span class="text-sm text-gray-500">{{ rank }}</span>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
