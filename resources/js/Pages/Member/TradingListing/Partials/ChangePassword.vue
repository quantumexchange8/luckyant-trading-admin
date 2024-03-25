<script setup>
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import {ref} from "vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import { KeyIcon, EyeIcon, EyeOffIcon } from "@heroicons/vue/outline"

const props = defineProps({
    tradingListing: Object,
    leverageSel: Array,
})

const form = useForm({
    id: props.tradingListing.id,
    master_password: undefined,
    investor_password: undefined,
    meta_login: props.tradingListing.meta_login,
    user_id: props.tradingListing.user.id,
})

const submit = () => {
    form.post(route('member.change_password'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const showPassword = ref(false);
const showPassword2 = ref(false);

const toggleMasterPasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const toggleMasterPasswordVisibilityConfirm = () => {
    showPassword2.value = !showPassword2.value;
}

</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="grid grid-cols-3 items-center gap-2">
            <Label 
                for="master_password"
                value="Master Password"
            />
            <div class="flex flex-col w-full col-span-2">
                <div class="relative">
                    <Input
                        id="master_password"
                        :type="showPassword ? 'text' : 'password'"
                        :placeholder="$t('public.new_password')"
                        class="block w-full"
                        v-model="form.master_password"
                        :invalid="form.errors.master_password"
                    />
                    <div
                        class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                        @click="toggleMasterPasswordVisibility"
                    >
                        <template v-if="showPassword">
                            <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                        </template>
                        <template v-else>
                            <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                        </template>
                    </div>
                </div>
                <InputError :message="form.errors.master_password" class="mt-2" />
            </div>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <Label 
                for="investor_password"
                value="Investor Password"
            />
            <div class="flex flex-col w-full col-span-2">
            <div class="relative">
                <Input
                    id="investor_password"
                    :type="showPassword2 ? 'text' : 'password'"
                    :placeholder="$t('public.new_password')"
                    class="block w-full"
                    v-model="form.investor_password"
                    :invalid="form.errors.investor_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleInvestorPasswordVisibility"
                >
                    <template v-if="showPassword2">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.investor_password" class="mt-2" />
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 items-center gap-2">
        <div class="mt-6 flex justify-end">
            <Button
                type="button"
                variant="transparent"
                @click="closeModal">
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary"
                class="ml-3"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                @click="submit"
            >
                {{ $t('public.process') }}
            </Button>
        </div>
    </div>

</template>