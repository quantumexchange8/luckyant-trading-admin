<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {Wallet} from "@/Components/Icons/outline.jsx";
import {ChevronRightIcon} from "@heroicons/vue/outline";
import EditMember from "@/Pages/Member/MemberDetails/EditMember.vue";
import AdvancedEdit from "@/Pages/Member/MemberDetails/AdvancedEdit.vue";
import TradingAccount from "@/Pages/Member/MemberDetails/TradingAccount.vue";
import Action from "@/Pages/Member/MemberDetails/Partials/Action.vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    member_detail: Object,
    wallets: Object,
    countries: Array,
    ranks: Array,
    tradingAccounts: Object
})

const { formatAmount } = transactionFormat();
</script>

<template>
    <AuthenticatedLayout title="Member Details">
        <template #header>
            <div class="flex flex-row gap-2 items-center">
                <h2 class="text-sm font-semibold dark:text-gray-400">
                    <a class="dark:hover:text-white" href="/member/member_listing">Member Listing</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <h2 class="text-sm font-semibold dark:text-white">
                    {{ member_detail.name }} - View Details
                </h2>
            </div>
        </template>

        <div class="flex gap-5 items-center">
            <div class="flex p-5 bg-white rounded-lg shadow-md dark:bg-gray-900 w-3/4">
                <EditMember
                    :member_detail="member_detail"
                    :countries="countries"
                    :ranks="ranks"
                />
            </div>
            <div class="flex p-5 bg-white rounded-lg shadow-md dark:bg-gray-900 w-1/4">
                <AdvancedEdit
                    :member_detail="member_detail"
                    :ranks="ranks"
                />
            </div>
        </div>

        <div class="flex flex-col my-8">
            <div class="text-lg pb-4 border-b border-gray-600 mb-5">
                Trading Accounts
            </div>
            <TradingAccount
                :member_detail="member_detail"
                :tradingAccounts="tradingAccounts"
            />
        </div>

        <div class="flex flex-col my-8">
            <h3 class="text-lg pb-4 border-b border-gray-600 mb-5">
                Wallet
            </h3>
            <div class="overflow-x-auto grid grid-flow-col justify-start relative gap-5">
                <div v-for="wallet in props.wallets" class="flex flex-col overflow-hidden rounded-[20px] w-96 border border-gray-300 dark:border-gray-800">
                    <div
                        class="flex justify-between bg-gradient-to-bl from-primary-400 to-primary-600"
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
    </AuthenticatedLayout>
</template>
