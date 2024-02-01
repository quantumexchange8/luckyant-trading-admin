<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ChevronRightIcon} from "@heroicons/vue/outline";
import EditMember from "@/Pages/Member/MemberDetails/EditMember.vue";
import AdvancedEdit from "@/Pages/Member/MemberDetails/AdvancedEdit.vue";
import TradingAccount from "@/Pages/Member/MemberDetails/TradingAccount.vue";

const props = defineProps({
    member_detail: Object,
    countries: Array,
    ranks: Array,
})
</script>

<template>
    <AuthenticatedLayout title="Member Details">
        <template #header>
            <div class="flex flex-row gap-2 items-center">
                <h2 class="text-sm font-semibold dark:text-gray-400">
                    <a class="dark:hover:text-white" href="/member/member_listing">Member Listing</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-6 h-6" />
                <h2 class="text-sm font-semibold dark:text-white">
                    {{ member_detail.name }} - View Details
                </h2>
            </div>
        </template>

        <div class="flex gap-5 items-center">
            <div class="flex p-5 bg-white rounded-lg shadow-md dark:bg-dark-eval-1 w-3/4 ">
                <EditMember
                    :member_detail="member_detail"
                    :countries="countries"
                    :ranks="ranks"
                />
            </div>
            <div class="flex p-5 bg-white rounded-lg shadow-md dark:bg-dark-eval-1 w-1/4 ">
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
            />
        </div>
    </AuthenticatedLayout>
</template>
