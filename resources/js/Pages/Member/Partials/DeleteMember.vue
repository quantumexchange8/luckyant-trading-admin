<script setup>
import Button from "@/Components/Button.vue";
import {useForm} from "@inertiajs/vue3";
import {WarningIcon} from "@/Components/Icons/outline.jsx";

const props = defineProps({
    members: Object
})
const emit = defineEmits(['update:memberDetailModal']);

const form = useForm({
    user_id: props.members.id,
})

const deleteUser = () => {
    form.delete(route('member.deleteMember'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
            // location.reload();
        },
        onFinish: () => {
            form.reset();
            location.reload();
        }
    })
}

const closeModal = () => {
    emit('update:memberDetailModal', false);
}
</script>

<template>
    <div>
        <WarningIcon aria-hidden="true" class="w-12 h-12" />
    </div>
    <div class="mt-5">
        <h1 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">
            Delete member
        </h1>
        <p class="dark:text-gray-400 text-sm">
            Are you sure you want to delete this member? This action cannot be undone.
        </p>
    </div>
    <div class="mt-5 flex gap-3 justify-center">
        <Button variant="secondary" class="px-6 w-1/2 justify-center" @click="closeModal">
            <span class="text-sm font-semibold">Cancel</span>
        </Button>
        <Button class="px-6 w-1/2 justify-center" variant="danger" @click.prevent="deleteUser">
            <span class="text-sm font-semibold">Delete</span>
        </Button>
    </div>
</template>
