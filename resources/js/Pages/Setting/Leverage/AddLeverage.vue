<script setup>
import Button from "@/Components/Button.vue";
import {CogIcon} from "@heroicons/vue/solid";
import {ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import {useForm,usePage} from "@inertiajs/vue3";
import BaseListbox from "@/Components/BaseListbox.vue";

const addLeverageSettingModal = ref(false);

const configurePaymentSetting = () => {
    addLeverageSettingModal.value = true
}

const closeModal = () => {
    addLeverageSettingModal.value = false
}

const form = useForm({
    value: '',
    status: '',
})

const submit = () => {
    form.post(route('setting.addLeverageSetting'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const status = [
    { value: 'Active', label: "Active" },
    { value: 'Inactive', label: "Inactive" },
];

</script>

<template>
    <Button
        type="button"
        variant="primary"
        size="sm"
        class="items-center gap-2 max-w-md"
        v-slot="{ iconSizeClasses }"
        @click="configurePaymentSetting"
    >
        <CogIcon aria-hidden="true" :class="iconSizeClasses" />
        <span>Add Leverage</span>
    </Button>

    <Modal :show="addLeverageSettingModal" title="Add New Leverage Setting" max-width="2xl" @close="closeModal">
        <div class="w-full">
            <form
                @submit.prevent="submit"
                class="flex flex-col gap-5"
            >
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="value" value="Value" />
                    <div class="md:col-span-3">
                        <Input
                        id="value"
                        type="text"
                        placeholder="Enter value"
                        class="block w-full"
                        :class="form.errors.value ? 'border border-primary-500 dark:border-primary-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.value"
                    />
                </div>
                    <InputError :message="form.errors.value" class="mt-2" />
                </div>
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="status" value="Status" />
                    <BaseListbox
                        v-model="form.status"
                        :options=status
                        :error="form.errors.status"
                    />
                    <InputError :message="form.errors.status" class="mt-2" />
                </div>
                <div class="flex pt-8 gap-3 justify-end border-t dark:border-gray-700">
                    <Button variant="secondary" class="px-4 py-2 justify-center" @click="closeModal">
                        <span class="text-sm font-semibold">Cancel</span>
                    </Button>
                    <Button variant="primary" class="px-4 py-2 justify-center" :disabled="form.processing">
                        <span class="text-sm font-semibold">Confirm</span>
                    </Button>
                </div>
            </form>
        </div>
    </Modal>

</template>