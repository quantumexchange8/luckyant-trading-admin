<script setup>
import InputLabel from "@/Components/Label.vue";
import Password from "primevue/password";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";

const props = defineProps({
    account: Object
});

const emit = defineEmits(['update:visible']);

const form = useForm({
    id: props.account.id,
    master_password: undefined,
    investor_password: undefined,
    meta_login: props.account.meta_login,
    user_id: props.account.user.id,
})

const submitForm = () => {
    form.post(route('account.change_password'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    })
}

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <form>
        <div class="flex flex-col items-center gap-8 self-stretch md:gap-10">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div
                    class="flex flex-col justify-center items-center py-4 px-8 gap-2 self-stretch bg-gray-200 dark:bg-gray-950">
                    <div class="w-full text-gray-500 text-center text-sm font-medium">
                        {{ account.trading_user.name }}
                    </div>
                    <div class="w-full text-gray-950 dark:text-white text-center text-xl font-semibold">
                        {{ account.meta_login }}
                    </div>
                </div>

                <!-- Master password -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="master_password"
                        :value="$t('public.master_password')"
                    />
                    <Password
                        input-id="master_password"
                        v-model="form.master_password"
                        toggleMask
                        placeholder="••••••••"
                        :invalid="!!form.errors.master_password"
                        :feedback="false"
                    />
                    <InputError :message="form.errors.master_password"/>
                </div>

                <!-- Investor password -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="investor_password"
                        :value="$t('public.investor_password')"
                    />
                    <Password
                        input-id="investor_password"
                        v-model="form.investor_password"
                        toggleMask
                        placeholder="••••••••"
                        :invalid="!!form.errors.investor_password"
                        :feedback="false"
                    />
                    <InputError :message="form.errors.investor_password"/>
                </div>
            </div>
            <div class="flex gap-3 w-full justify-end">
                <Button
                    type="button"
                    variant="transparent"
                    class="w-full md:w-auto justify-center"
                    @click="closeDialog"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary"
                    :disabled="form.processing"
                    class="w-full md:w-auto justify-center"
                    @click.prevent="submitForm"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </form>
</template>
