<script setup>
import StatusBadge from "@/Components/StatusBadge.vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    masterConfigurations: Object,
    subscriberCount: Number
})

const { formatAmount } = transactionFormat();

</script>

<template>
    <div class="flex flex-col items-start gap-5 bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
        <div class="flex items-center gap-3">
            <img
                class="object-cover w-12 h-12 rounded-full"
                :src="masterConfigurations.user.profile_photo_url ? masterConfigurations.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                alt="userPic"
            />
            <div class="flex flex-col items-start">
                <div class="flex items-center gap-2">
                    <div class="text-lg">
                        {{ masterConfigurations.trading_user.name }}
                    </div>
                    <StatusBadge :value="masterConfigurations.status" />
                </div>
                <div class="text-sm text-gray-400">
                    {{ masterConfigurations.meta_login }}
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-5 self-stretch">
            <div class="flex flex-row-reverse justify-between sm:justify-center sm:flex-col items-center w-full">
                <div class="text-xl font-semibold">
                    {{ subscriberCount }}
                </div>
                <div class="text-sm text-gray-400">
                    Real Subscribers
                </div>
            </div>
            <div class="flex flex-row-reverse justify-between sm:justify-center sm:flex-col items-center w-full">
                <div class="text-xl font-semibold">
                    $ {{ formatAmount(masterConfigurations.total_fund_size) }}
                </div>
                <div class="text-sm text-gray-400">
                    Real Fund Size
                </div>
            </div>
            <div class="flex flex-row-reverse justify-between sm:justify-center sm:flex-col items-center w-full">
                <div
                    class="text-xl font-semibold"
                    :class="{
                        'text-success-500': masterConfigurations.subscribe_percentage > 0,
                        'text-error-500': masterConfigurations.subscribe_percentage < 0,
                    }"
                >
                    {{ formatAmount(masterConfigurations.subscribe_percentage, 2) }}%
                </div>
                <div class="text-sm text-gray-400">
                    Subscribing Percentage
                </div>
            </div>
        </div>
    </div>
</template>
