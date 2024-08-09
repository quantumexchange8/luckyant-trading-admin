<script setup>
import Button from "@/Components/Button.vue";
import {useForm} from "@inertiajs/vue3";
import {WarningIcon} from "@/Components/Icons/outline.jsx";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    tradingListing: Object
})
const emit = defineEmits(['update:tradingModal']);

const form = useForm({
    id: props.tradingListing.id,
    trade_user: props.tradingListing.trading_user.id,
    meta_login: props.tradingListing.meta_login,
    remarks: '',
})

const submitForm = () => {
    form.delete(route('member.deleteAccount'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
            // location.reload();
        },
    })
}

const closeModal = () => {
    emit('update:tradingModal', false);
}
</script>

<template>
    <div>
        <WarningIcon aria-hidden="true" class="w-12 h-12" />
    </div>
    <div class="mt-5">
        <h1 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">
            Delete account
        </h1>
        <p class="dark:text-gray-400 text-sm">
            Are you sure you want to delete this account? This action cannot be undone.
        </p>
    </div>
    <div class="m-5">
        <Label class="text-sm dark:text-white w-1/4 pt-0.5" for="remarks" value="Remarks" />
        <div class="flex flex-col w-full">
            <Input
                id="remarks"
                type="text"
                placeholder="Enter remark"
                class="block w-full"
                v-model="form.remarks"
                :invalid="form.errors.remarks"
            />
            <InputError :message="form.errors.remarks" class="mt-2" />
        </div>
    </div>
    <div class="mt-5 flex gap-3 justify-center">
        <Button variant="secondary" class="px-6 w-1/2 justify-center" @click="closeModal">
            <span class="text-sm font-semibold">Cancel</span>
        </Button>
        <Button class="px-6 w-1/2 justify-center" variant="danger" @click.prevent="submitForm" :disabled="form.processing">
            <span class="text-sm font-semibold">Delete</span>
        </Button>
    </div>
</template>
