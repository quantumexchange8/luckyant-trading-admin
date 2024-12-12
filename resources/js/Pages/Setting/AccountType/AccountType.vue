<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Card from "primevue/card";
import NoData from "@/Components/NoData.vue";
import AccountTypeTable from "@/Pages/Setting/AccountType/AccountTypeTable.vue";

defineProps({
    accountTypesCount: Number
})
</script>

<template>
    <AuthenticatedLayout title="Account Type">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Account Type
                </h2>
            </div>
        </template>

        <Card v-if="accountTypesCount > 0" class="w-full">
            <template #content>
                <AccountTypeTable />
            </template>
        </Card>

        <div
            v-else
            class="w-full flex items-center justify-center"
        >
            <NoData />
        </div>
    </AuthenticatedLayout>
</template>
