<script setup>
import { CogIcon } from "@heroicons/vue/solid"
import Button from "@/Components/Button.vue";
import { ref, watch } from "vue"
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    settings: Array,
    withdrawal: Object,
    search: String,
})

const { formatDateTime, formatAmount } = transactionFormat();
const configureWithdrawal = ref(false)

const form = useForm({
    id: '',
    value: props.withdrawal.value,
});

const configureSetting = (setting) => {
    
    if (setting.slug == 'withdrawal-fee') {
        configureWithdrawal.value = true;
        
        form.id = setting.id;
        form.value = setting.value;
    }
}

const closeModal = () => {
    configureWithdrawal.value = false
}

const submit = () => {
    const formData = {
        id: form.id,
        value: form.value,
    };
    form.post(route('setting.updateMasterSetting'), {
        data: formData,
        onSuccess: () => {
            closeModal();
        },
    })
}
</script>

<template>
    <div>
        <table class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" colspan="2" class="px-3 py-4">
                        Name
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-4">
                        Value
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-4">
                        Last Updated
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-4 text-center">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="setting in settings">
                    <td colspan="2" class="px-3 py-4 w-1/4">
                        {{ setting.name }}
                    </td>
                    <td colspan="2" class="px-3 py-4 w-1/4">
                        {{ setting.value }}
                    </td>
                    <td colspan="2" class="px-3 py-4 w-1/4">
                        {{ formatDateTime(setting.updated_at) }}
                    </td>
                    <td colspan="2" class="px-3 py-4 w-1/4 text-center">
                        <Button
                            type="button"
                            variant="primary"
                            size="sm"
                            class="items-center gap-2 max-w-md"
                            v-slot="{ iconSizeClasses }"
                            @click="configureSetting(setting)"
                        >
                            <CogIcon aria-hidden="true" :class="iconSizeClasses" />
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal :show="configureWithdrawal" :title="$t('Configuration Witdrawal')" @close="closeModal">
        <form class="space-y-2">
            <div class="space-y-2">
                <Label
                    for="withdrawal"
                    value="Withdrawal value"
                />
                <Input
                    id="withdrawal"
                    type="number"
                    class="block w-full"
                    v-model="form.value"
                    :invalid="form.errors.value"
                />
                <InputError :message="form.errors.value" />
            </div>
            <div class="pt-5 flex justify-end">
                <Button
                    class="flex justify-center"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{ $t('public.Save') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>