<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import MemberFundOverview from "@/Pages/Member/MemberFund/MemberFundOverview.vue";
import MemberFundTable from "@/Pages/Member/MemberFund/MemberFundTable.vue";
import {ref} from "vue";

const totalDeposit = ref(null);
const totalWithdrawal = ref(null);
const totalGroupDeposit = ref(null);

const handleOverview = (data) => {
    totalDeposit.value = data.totalDeposit;
    totalWithdrawal.value = data.totalWithdrawal;
    totalGroupDeposit.value = data.totalGroupDeposit;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.member_fund')">
        <template #header>
            <div class="text-xl font-semibold leading-tight">
                {{ $t('public.member_fund') }}
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <MemberFundOverview />
            <MemberFundTable />
        </div>
    </AuthenticatedLayout>
</template>
