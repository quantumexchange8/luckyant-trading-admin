<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AccountTable from "@/Pages/Account/AccountListing/AccountTable.vue";
import Button from "primevue/button";
import toast from "@/Composables/toast.js"

defineProps({
    accountsCount: Number,
})

const resendEmail = async () => {
    try {
        const response = await axios.post(route('account.resendCreateAccountEmail'));

        console.log(response.status);
        if (response.status === 200) {
            toast.add({
                title: 'Success',
                message: 'Email resent successfully.',
                type: 'success',
            });
        } else {
            toast.add({
                title: 'Warning',
                message: 'Internal Server Error',
                type: 'warning',
            });
        }
    } catch (error) {
        if (error.response) {
            // Server responded with a status other than 2xx
            console.error('Error:', error.response.data.message || 'Failed to resend email.');
        } else if (error.request) {
            // No response was received from the server
            console.error('No response received:', error.request);
        } else {
            // Error occurred while setting up the request
            console.error('Request error:', error.message);
        }
    }
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.account_listing')">
        <template #header>
            <div class="flex flex-col md:flex-row md:justify-between items-center">
                <div class="text-xl font-semibold leading-tight">
                    {{ $t('public.account_listing') }}
                </div>
                <Button
                    v-if="$page.props.auth.user.id === 1"
                    severity="info"
                    outlined
                    size="small"
                    @click="resendEmail"
                >
                    Resend Email
                </Button>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <AccountTable
            />
        </div>
    </AuthenticatedLayout>
</template>
