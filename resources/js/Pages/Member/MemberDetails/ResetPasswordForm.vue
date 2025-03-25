<script setup>
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Password from "primevue/password";
import {useForm} from "@inertiajs/vue3";
import Button from "primevue/button";

const props = defineProps({
    member: Object
})

const form = useForm({
    user_id: props.member.id,
    password: '',
    password_confirmation: '',
});

const emit = defineEmits(["update:visible"]);

const submitForm = () => {
    form.put(route('member.updateMemberPassword'), {
        onSuccess: () => {
            form.reset();
            emit("update:visible", false);
        }
    })
}
</script>

<template>
    <div class="flex flex-col gap-1 items-start self-stretch">
        <InputLabel
            for="password"
            :invalid="!!form.errors.password"
        >
            {{ $t('public.password') }}
        </InputLabel>
        <Password
            input-id="password"
            v-model="form.password"
            toggleMask
            placeholder="••••••••"
            :invalid="!!form.errors.password"
        />
        <InputError :message="form.errors.password" />
    </div>

    <div class="flex flex-col gap-1 items-start self-stretch">
        <InputLabel
            for="password_confirmation"
            :invalid="!!form.errors.password_confirmation"
        >
            {{ $t('public.confirm_password') }}
        </InputLabel>
        <Password
            input-id="password_confirmation"
            v-model="form.password_confirmation"
            toggleMask
            placeholder="••••••••"
            :invalid="!!form.errors.password_confirmation"
        />
    </div>

    <div class="flex justify-end w-full pt-3">
        <Button
            type="submit"
            :disabled="form.processing"
            @click.prevent="submitForm"
            class="px-10 w-full md:w-auto"
            size="small"
        >
            <span>Save</span>
        </Button>
    </div>
</template>
