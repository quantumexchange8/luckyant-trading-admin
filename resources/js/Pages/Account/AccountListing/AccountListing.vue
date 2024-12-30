<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AccountTable from "@/Pages/Account/AccountListing/AccountTable.vue";
import toast from "@/Composables/toast.js"

defineProps({
    accountsCount: Number,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.account_listing')">
        <template #header>
            <div class="text-xl font-semibold leading-tight">
                {{ $t('public.account_listing') }}
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <AccountTable
            />
        </div>
    </AuthenticatedLayout>
</template>
