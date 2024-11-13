<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AddPaymentGateway from "@/Pages/Setting/PaymentGateway/AddPaymentGateway.vue";
import PaymentGatewayTable from "@/Pages/Setting/PaymentGateway/PaymentGatewayTable.vue";
import Card from "primevue/card"

defineProps({
    payment_gateways_count: Number,
})
</script>

<template>
    <AuthenticatedLayout title="Payment Gateway">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Payment Gateway
                </h2>

                <AddPaymentGateway />
            </div>
        </template>

        <Card class="w-full">
            <template #content>
                <PaymentGatewayTable
                    :payment_gateways_count="payment_gateways_count"
                />
            </template>
        </Card>

    </AuthenticatedLayout>
</template>
