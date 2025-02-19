<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Card from "primevue/card"
import AddPaymentMethod from "@/Pages/Setting/Payment/Partials/AddPaymentMethod.vue";
import SettingPaymentTable from "@/Pages/Setting/Payment/SettingPaymentTable.vue";
</script>

<template>
    <AuthenticatedLayout :title="$t('public.payment_setting')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.payment_setting') }}
                </h2>

                <AddPaymentMethod />
            </div>
        </template>

        <Card class="w-full">
            <template #content>
                <SettingPaymentTable
                />
            </template>
        </Card>

    </AuthenticatedLayout>
</template>
