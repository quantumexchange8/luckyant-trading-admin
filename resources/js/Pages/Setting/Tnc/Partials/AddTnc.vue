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
import TipTapEditor from "@/Components/TipTapEditor.vue";

const configureSetting = ref(false)
const addTnCSettingModal = ref(false);

const previewTitle = ref('');
const previewContents = ref('');

const configurePaymentSetting = () => {
    addTnCSettingModal.value = true
}

const closeModal = () => {
    addTnCSettingModal.value = false
}

const form = useForm({
    type: '',
    title: '',
    contents: '',
})

watch(form, (watchFormSubject) => {
    previewTitle.value = watchFormSubject.title;
    previewContents.value = watchFormSubject.contents;
});

const submit = () => {
    form.post(route('setting.addTnCSetting'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const tncSetting = [
    { value: 'register', label: "Register" },
    { value: 'subscribe', label: "Subscribe" },
    { value: 'pamm_esg', label: "ESG Investment" },
    { value: 'trading_account', label: "Add Trading Account" },
    { value: 'terminate', label: "Terminate" },
    { value: 'stop_renewal', label: "Stop Renewal" },
    { value: 'deposit', label: "Deposit" },
    { value: 'become_master', label: "Become Master" },
    { value: 'withdrawal', label: "Withdrawal" },
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
        <span>Add T&C </span>
    </Button>

    <Modal :show="addTnCSettingModal" title="Add new T&C Setting" max-width="6xl" @close="closeModal">
        <div class="grid grid-rows-2 md:grid-rows-1 md:grid-cols-2 gap-5 w-full">
            <form
                @submit.prevent="submit"
                class="flex flex-col gap-5"
            >
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="type" value="Type" />
                    <div class="md:col-span-3">
                    <BaseListbox
                        v-model="form.type"
                        :options=tncSetting
                        :error="!!form.errors.type"
                    />
                </div>
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="title" value="Title" />
                    <Input
                        id="title"
                        type="text"
                        placeholder="Enter title"
                        class="block w-full"
                        :class="form.errors.title ? 'border border-primary-500 dark:border-primary-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.title"
                    />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="content" value="Contents" />
                    <TipTapEditor
                        v-model="form.contents"
                    />
                    <InputError :message="form.errors.contents" class="mt-2" />
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
            <div>
                <h3 class="font-semibold dark:text-white text-base pb-3 border-b dark:border-gray-700">Preview</h3>

                <div class="pt-8">
                    <h3 class="font-semibold text-sm dark:text-white">{{ previewTitle }}</h3>
                    <div class="mt-5 dark:text-gray-400 prose max-w-none leading-3 text-xs" v-html="previewContents"></div>
                </div>
            </div>
        </div>
    </Modal>

</template>
