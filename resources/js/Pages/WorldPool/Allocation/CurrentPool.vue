<script setup>
import Card from "primevue/card";
import {transactionFormat} from "@/Composables/index.js";

defineProps({
    world_pool: Object,
    active_subscriptions_capital: String
});

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row items-center gap-5 w-full">
        <Card
            class="w-full"
            v-for="(amount, rank) in world_pool"
        >
            <template #content>
                <div class="flex flex-col items-center self-stretch">
                    <span class="text-xl font-semibold dark:text-white">$ {{ formatAmount(Number(active_subscriptions_capital) + Number(amount)) }}</span>
                    <span class="text-lg text-primary-500">($ {{ formatAmount(active_subscriptions_capital) }} + ${{ formatAmount(amount) }})</span>
                    <span class="text-sm text-gray-500">{{ rank }}</span>
                </div>
            </template>
        </Card>
    </div>
</template>
