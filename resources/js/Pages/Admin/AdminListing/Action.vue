<script setup>
import {TrashIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Tooltip from "@/Components/Tooltip.vue";
import {ref} from "vue";
import {trans} from "laravel-vue-i18n";
import Modal from "@/Components/Modal.vue";
import {alertTriangle} from "@/Components/Icons/outline.jsx";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    id: Number
})

const adminActionModal = ref(false);
const modalComponent = ref('');

const openAdminActionModal = (componentType) => {
    adminActionModal.value = true;
    if (componentType === 'remove') {
        modalComponent.value = 'remove_admin';
    }
}

const closeModal = () => {
    adminActionModal.value = false
    modalComponent.value = '';
}

const form = useForm({
    id: props.id,
});

const submitForm = () => {
    form.post(route('admin.remove_admin'), {
        onSuccess: () => {
            closeModal();
        },
    });
}
</script>

<template>
    <div class="inline-flex justify-center items-center gap-2">
        <Tooltip :content="$t('public.remove')" placement="bottom" class="relative">
            <Button
                type="button"
                iconOnly
                pill
                size="sm"
                class="justify-center gap-2"
                variant="danger"
                v-slot="{ iconSizeClasses }"
                @click="openAdminActionModal('remove')"
            >
                <TrashIcon aria-hidden="true" :class="iconSizeClasses" />
                <span class="sr-only">remove</span>
            </Button>
        </Tooltip>
    </div>

    <Modal :show="adminActionModal" :title="$t('public.' + modalComponent)" @close="closeModal" max-width="lg">
        <!-- Remove Admin -->
        <div v-if="modalComponent === 'remove_admin'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">{{ $t('public.remove_admin') }}</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    {{ $t('public.remove_admin_confirmation') }}?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    {{ $t('public.cancel') }}
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
