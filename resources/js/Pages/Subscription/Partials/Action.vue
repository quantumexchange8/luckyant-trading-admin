<script setup>
import Tooltip from "@/Components/Tooltip.vue";
import {MemberDetailIcon} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import StatusBadge from "@/Components/StatusBadge.vue";

const props = defineProps({
    penalty: Object
})

const penaltyDetailModal = ref(false);
const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const openPenaltyDetailModal = () => {
    penaltyDetailModal.value = true
}

const closeModal = () => {
    penaltyDetailModal.value = false
}
</script>

<template>
    <Tooltip :content="$t('public.view_details')" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openPenaltyDetailModal"
        >
            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">view</span>
        </Button>
    </Tooltip>

    <Modal :show="penaltyDetailModal" :title="$t('public.view_details')" @close="closeModal" max-width="lg">
        <div class="flex flex-col items-start gap-3 self-stretch">
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="text-lg font-semibold dark:text-white">
                    <div v-if="currentLocale === 'en'">
                        {{ penalty.master.trading_user.name }} - {{ penalty.master_meta_login }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ penalty.master.trading_user.company ? penalty.master.trading_user.company : penalty.master.trading_user.name }} - {{ penalty.master_meta_login }}
                    </div>
                </div>
                <StatusBadge
                    value="Terminated"
                    width="w-20"
                />
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.account_number')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ penalty.meta_login }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.subscription_number')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ penalty.subscription_number }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.join_date')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ formatDateTime(penalty.approval_date, false) }}
                </div>
            </div>
            <div class="flex items-start justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.termination_date')}}
                </div>
                <div class="text-sm sm:text-base text-error-500 font-bold">
                    {{ formatDateTime(penalty ? penalty.termination_date : '-', false) }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.join_day')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ penalty.join_days }} {{ $t('public.days') }} / {{ penalty.management_period }} {{ $t('public.days') }}
                </div>
            </div>
            <div class="flex items-start justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.amount')}}
                </div>
                <div class="text-sm sm:text-base text-primary-500 dark:text-primary-400 font-bold">
                    $ {{ formatAmount(penalty.subscription_batch_amount) }}
                </div>
            </div>
            <div class="flex items-start justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.management_fee')}} (<span :class="{'text-error-500': penalty.penalty_percent > 0}">{{ formatAmount(penalty.penalty_percent, 0) }}%</span>)
                </div>
                <div class="text-sm sm:text-base text-error-500 font-bold">
                    $ {{ formatAmount(penalty ? penalty.penalty_amount : 0) }}
                </div>
            </div>
            <div class="flex items-start justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.return_amount')}}
                </div>
                <div class="text-sm sm:text-base text-success-500 font-bold">
                    $ {{ formatAmount(penalty.return_amount) }}
                </div>
            </div>
        </div>
    </Modal>
</template>
