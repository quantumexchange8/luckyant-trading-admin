<script setup>
import Button from "@/Components/Button.vue";
import {UserUp01Icon} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Combobox from "@/Components/Combobox.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    roleLists: Array
})

const assignUserModal = ref(false);
const selectedUser = ref(null);

const openAssignUserModal = () => {
    assignUserModal.value = true
}

const closeModal = () => {
    assignUserModal.value = false
}

const form = useForm({
    user_id: '',
    role: ''
})

function loadUsers(query, setOptions) {
    fetch('/member/getAllUsers?query=' + query)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(user => {
                    return {
                        value: user.id,
                        label: user.email,
                        img: user.profile_photo
                    }
                })
            )
        });
}

const submitForm = () => {
    if (selectedUser.value !== null) {
        form.user_id = selectedUser.value.value
    }
    form.post(route('admin.assign_user'), {
        onSuccess: () => {
            closeModal();
        },
    });
}
</script>

<template>
    <Button
        type="button"
        variant="primary"
        class="flex gap-2"
        v-slot="{ iconSizeClasses }"
        @click="openAssignUserModal"
    >
        <UserUp01Icon aria-hidden="true" :class="iconSizeClasses" />
        <div>{{ $t('public.assign_user') }}</div>
    </Button>

    <Modal :show="assignUserModal" :title="$t('public.assign_user')" @close="closeModal">
        <form class="space-y-2">
            <div class="space-y-2">
                <Label
                    class="text-sm dark:text-white"
                    for="user"
                    :value="$t('public.user')"
                />
                <div class="md:col-span-3">
                    <Combobox
                        :load-options="loadUsers"
                        v-model="selectedUser"
                        :error="form.errors.user_id"
                        image
                    />
                </div>
                <InputError :message="form.errors.user_id" />
            </div>

            <div class="space-y-2 pb-5">
                <Label
                    class="text-sm dark:text-white"
                    for="role"
                    :value="$t('public.role')"
                />
                <div class="md:col-span-3">
                    <BaseListbox
                        v-model="form.role"
                        :options="roleLists"
                        :error="!!form.errors.role"
                    />
                </div>
                <InputError :message="form.errors.role" />
            </div>

            <div class="pt-5 text-gray-600 dark:text-gray-400">
                {{ $t('public.assign_user_note') }}
            </div>

            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    {{ $t('public.cancel') }}
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
