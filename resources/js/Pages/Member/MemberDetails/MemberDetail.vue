<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {Wallet} from "@/Components/Icons/outline.jsx";
import {ChevronRightIcon} from "@heroicons/vue/outline";
import EditMember from "@/Pages/Member/MemberDetails/EditMember.vue";
import AdvancedEdit from "@/Pages/Member/MemberDetails/AdvancedEdit.vue";
import TradingAccount from "@/Pages/Member/MemberDetails/TradingAccount.vue";
import Action from "@/Pages/Member/MemberDetails/Partials/Action.vue";
import {transactionFormat} from "@/Composables/index.js";
import Badge from "@/Components/Badge.vue";
import {ref, watchEffect} from "vue";
import Modal from "@/Components/Modal.vue";
import PaymentAccount from "@/Pages/Member/MemberDetails/Partials/PaymentAccount.vue"
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    member_detail: Object,
    wallets: Array,
    countries: Array,
    ranks: Array,
    tradingAccounts: Object,
    nationalities: Array,
    paymentAccounts: Array,
    currencies: Array,
})

const { formatAmount } = transactionFormat();
const paymentModal = ref(false);
const paymentDetails = ref(null);

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}

const openModal = (paymentAccount) => {
    paymentModal.value = true;
    paymentDetails.value = paymentAccount;
}

const closeModal = () => {
    paymentModal.value = false
}
</script>

<template>
    <AuthenticatedLayout title="Member Details">
        <template #header>
            <div class="flex flex-row gap-2 items-center">
                <h2 class="text-xl font-semibold dark:text-gray-400">
                    <a class="hover:text-primary-500 dark:hover:text-white" href="/member/member_listing">Member Listing</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <h2 class="text-xl font-semibold dark:text-white">
                    {{ member_detail.name }} - View Details
                </h2>
            </div>
        </template>

        <div class="flex flex-wrap gap-5 items-stretch md:flex-nowrap">
            <div class="flex p-5 bg-white rounded-lg shadow-md dark:bg-gray-900 w-full md:w-3/4">
                <EditMember
                    :member_detail="member_detail"
                    :countries="countries"
                    :nationalities="nationalities"
                />
            </div>
            <div class="flex p-5 bg-white rounded-lg shadow-md dark:bg-gray-900 w-full md:w-1/4 md:self-stretch">
                <AdvancedEdit
                    :member_detail="member_detail"
                    :ranks="ranks"
                />
            </div>
        </div>

        <!-- <div class="flex flex-col my-8">
            <div class="text-lg pb-4 border-b border-gray-600 mb-5">
                Trading Accounts
            </div>
            <TradingAccount
                :member_detail="member_detail"
                :tradingAccounts="tradingAccounts"
            />
        </div> -->

        <div class="flex flex-col my-8">
            <h3 class="pb-2 border-b border-gray-300 mb-5 text-xl font-semibold">
                Wallet
            </h3>
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
                                    :member_detail="member_detail"
                                    :wallet="wallet"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col my-8">
            <h3 class="pb-2 border-b border-gray-300 mb-5 text-xl font-semibold">
                Payment Account
            </h3>
            <div v-if="paymentAccounts.length === 0" class="flex justify-center">
                No Payment Accounts
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div v-for="paymentAccount in paymentAccounts"
                    class="card text-gray-300 w-full hover:brightness-90 transition-all cursor-pointer group bg-gradient-to-tl from-gray-600 to-gray-800 hover:from-gray-800 hover:to-gray-700 dark:from-gray-900 dark:to-gray-950 dark:hover:from-gray-800 dark:hover:to-gray-950 border-r-2 border-t-2 border-gray-600 dark:border-gray-900 rounded-lg overflow-hidden relative"
                    @click="openModal(paymentAccount)"
                >
                    <div class="px-8 py-5">
                        <div class="flex justify-between items-center mb-4">
                            <div class="bg-orange-500 w-10 h-10 rounded-full rounded-tl-none group-hover:-translate-y-1 group-hover:shadow-xl group-hover:shadow-red-900 transition-all"></div>
                            <div>
                                <Badge :variant="statusVariant(paymentAccount.status)">
                                    {{ paymentAccount.status }}
                                </Badge>
                            </div>
                        </div>

                        <div class="uppercase font-bold text-xl">
                            {{ paymentAccount.payment_platform_name }}
                        </div>
                        <div class="text-gray-300 uppercase tracking-widest">
                            {{ paymentAccount.payment_account_name }}
                        </div>
                        <div class="text-gray-400 mt-6">
                            <p class="font-bold">{{ paymentAccount.currency }}</p>
                            <p>{{ paymentAccount.account_no }}</p>
                        </div>
                    </div>

                    <div class="h-2 w-full bg-gradient-to-l via-yellow-500 group-hover:blur-xl blur-2xl m-auto rounded transition-all absolute bottom-0"></div>
                    <div class="h-0.5 group-hover:w-full bg-gradient-to-l via-yellow-700 dark:via-yellow-950 group-hover:via-yellow-500 w-[70%] m-auto rounded transition-all"></div>
                </div>
            </div>
        </div>

        <Modal :show="paymentModal" title="Payment Account Details" @close="closeModal" max-width="lg">
            <PaymentAccount :paymentDetails="paymentDetails" :countries="countries" :currencies="currencies" @closeModal="closeModal"/>
        </Modal>


    </AuthenticatedLayout>
</template>
