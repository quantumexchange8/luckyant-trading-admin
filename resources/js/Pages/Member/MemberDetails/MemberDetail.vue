<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ChevronRightIcon} from "@heroicons/vue/outline";
import Card from "primevue/card";
import EditMember from "@/Pages/Member/MemberDetails/EditMember.vue";
import MemberRank from "@/Pages/Member/MemberDetails/MemberRank.vue";
import MemberGroup from "@/Pages/Member/MemberDetails/MemberGroup.vue";
import MemberKyc from "@/Pages/Member/MemberDetails/MemberKyc.vue";
import MemberWallet from "@/Pages/Member/MemberDetails/MemberWallet.vue";
import ResetPasswordForm from "@/Pages/Member/MemberDetails/ResetPasswordForm.vue";

const props = defineProps({
    member_detail: Object,
    firstLeader: Object,
    frontIdentity: String,
    backIdentity: String,
    wallets: Object,
})
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

        <div class="grid grid-cols-6 lg:grid-cols-12 gap-3 md:gap-5 w-full items-start self-stretch">
            <div class="flex flex-col gap-3 md:gap-5 col-span-6 lg:col-span-8 w-full">
                <!-- Edit member -->
                <EditMember
                    :member="member_detail"
                />

                <!-- KYC -->
                <MemberKyc
                    :member="member_detail"
                    :frontIdentity="frontIdentity"
                    :backIdentity="backIdentity"
                />

                <!-- Wallets -->
                <MemberWallet
                    :member="member_detail"
                    :wallets="wallets"
                />
            </div>

            <div class="flex flex-col gap-3 md:gap-5 col-span-6 lg:col-span-4 w-full">
                <!-- Rank -->
                <MemberRank
                    :member="member_detail"
                />
                <!-- Group -->
                <MemberGroup
                    :member="member_detail"
                    :firstLeader="firstLeader"
                />

                <Card>
                    <template #content>
                        <div class="flex flex-col gap-5 items-center self-stretch">
                            <div class="flex flex-col gap-3 items-center self-stretch">
                                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.reset_password') }}</span>

                                <ResetPasswordForm
                                    :member="member_detail"
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
