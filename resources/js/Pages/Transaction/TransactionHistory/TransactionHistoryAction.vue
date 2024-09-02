<script setup>
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import {MemberDetailIcon} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    transaction: Object
})

const transactionModal = ref(false);
const { formatDateTime, formatAmount, formatType } = transactionFormat();

const openTransactionModal = () => {
    transactionModal.value = true;
}


const closeModal = () => {
    transactionModal.value = false
}
</script>

<template>
    <Tooltip
        content="Details"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openTransactionModal"
        >
            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">view</span>
        </Button>
    </Tooltip>

    <Modal :show="transactionModal" :title="$t('public.transaction_details')" @close="closeModal" max-width="xl">
        <div class="flex flex-col gap-5">
            <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.date')}}
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                            {{ formatDateTime(transaction.created_at, false) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.transaction_type')}}
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                            {{ $t('public.' + formatType(transaction.transaction_type).toLowerCase().replace(/\s+/g, '_')) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.transaction_no')}}
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                            {{ transaction.transaction_number ?? '-' }}
                        </div>
                    </div>
                    <div v-if="transaction.transaction_type === 'Withdrawal'" class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.withdrawal_amount')}}
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                            $ {{ transaction.amount }}
                        </div>
                    </div>
                    <div v-if="transaction.transaction_type === 'Withdrawal'" class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.withdrawal_charges')}}
                        </div>
                        <div class="text-sm sm:text-base text-error-500 font-semibold">
                            - $ {{ transaction.transaction_charges }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.amount')}}
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                            $ {{ transaction.transaction_amount }}
                        </div>
                    </div>
                    <div v-if="transaction.payment_method === 'Bank'" class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            Conversion Rate
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                            {{
                                transaction.conversion_rate > 1 ? transaction.currency_symbol : '$ '
                            }} {{ formatAmount(transaction.conversion_amount) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch" v-if="transaction.transaction_type === 'WalletAdjustment'">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.wallet')}}
                        </div>
                        <div class="text-sm sm:text-base font-semibold">
                            <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">{{ $t('public.' + transaction.from_wallet.type) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch" v-if="(transaction.from_wallet || transaction.from_meta_login) && transaction.transaction_type !== 'WalletAdjustment' && transaction.transaction_type !== 'PerformanceIncentive'">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.transfer_from')}}
                        </div>
                        <div class="text-sm sm:text-base font-semibold">
                            <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="transaction.transaction_type !== 'Transfer'">{{ transaction.from_wallet ? $t('public.' + transaction.from_wallet.type) : transaction.from_meta_login.meta_login }}</span>
                            <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="transaction.transaction_type === 'Transfer'">{{ transaction.from_wallet ? transaction.from_wallet.wallet_address : transaction.from_meta_login }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch" v-if="(transaction.to_wallet || transaction.to_meta_login || transaction.payment_account) && transaction.transaction_type !== 'WalletAdjustment'">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.transfer_to')}}
                        </div>
                        <div class="text-sm sm:text-base font-semibold">
                            <div
                                v-if="transaction.transaction_type === 'Withdrawal'"
                                class="flex flex-col text-sm sm:text-base text-gray-800 dark:text-white font-semibold"
                            >
                                <div>
                                    {{ transaction.payment_account ? (transaction.payment_account.payment_account_name) : $t('public.' + transaction.to_wallet.type) }} -
                                </div>
                                <div class="break-words w-40 md:w-full md:break-normal">
                                    {{ transaction.payment_account ? (transaction.payment_account.account_no) : $t('public.' + transaction.to_wallet.type) }}
                                </div>
                            </div>
                            <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="transaction.transaction_type !== 'Transfer' && transaction.transaction_type !== 'Withdrawal'">{{ transaction.to_wallet ? $t('public.' + transaction.to_wallet.type) : transaction.to_meta_login.meta_login }}</span>
                            <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="transaction.transaction_type === 'Transfer'">{{ transaction.to_wallet ? transaction.to_wallet.wallet_address : transaction.to_meta_login }}</span>
                        </div>
                    </div>
                    <div v-if="transaction.transaction_type === 'Withdrawal'" class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{ $t(`public.${transaction.payment_type}`) }}
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 font-semibold">
                            {{ transaction.payment_account.payment_platform_name }}
                        </div>
                    </div>
                    <div v-if="transaction.payment_method === 'Bank'" class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            Bank Branch
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 font-semibold">
                            {{ transaction.payment_account.bank_sub_branch }}
                        </div>
                    </div>
                    <div v-if="transaction.transaction_type === 'Withdrawal'" class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.profit')}}
                        </div>
                        <div class="text-sm sm:text-base text-primary-500 font-semibold">
                            $ {{ transaction.profit_amount }}
                        </div>
                    </div>
                    <div v-if="transaction.transaction_type === 'Withdrawal'" class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.bonus')}}
                        </div>
                        <div class="text-sm sm:text-base text-primary-500 font-semibold">
                            $ {{ transaction.bonus_amount }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            {{$t('public.status')}}
                        </div>
                        <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                            <StatusBadge :value="transaction.status" width="w-20"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
