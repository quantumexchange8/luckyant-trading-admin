<script setup>
import Tooltip from "@/Components/Tooltip.vue";
import Button from '@/Components/Button.vue'
import {CheckIcon, XIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import {alertTriangle} from "@/Components/Icons/outline.jsx";
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    request: Object
})

const pendingRequestModal = ref(false);
const modalComponent = ref(null);

const openPendingRequestModal = (requestId, componentType) => {
    pendingRequestModal.value = true;
    if (componentType === 'approve') {
        modalComponent.value = 'Approve Request';
    } else if (componentType === 'reject') {
        modalComponent.value = 'Reject Request';
    } else if (componentType === 'rejectRemarks') {
        modalComponent.value = 'Reject Remark';
    } else if (componentType === 'view') {
        modalComponent.value = 'Transaction Details';
    }
}

const closeModal = () => {
    pendingRequestModal.value = false
    modalComponent.value = null;
}

const form = useForm({
    id: props.request.id,
    remarks: '',
});

const submitForm = () => {
    let submitRoute;
    if (modalComponent.value === 'Approve Request') {
        submitRoute = route('master.approveRequest');
    } else if (modalComponent.value === 'Reject Remark') {
        submitRoute = route('master.rejectRequest');
    }

    if (submitRoute) {
        form.post(submitRoute, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        console.error('Invalid modal component:', modalComponent);
    }
}
</script>

<template>
    <div class="inline-flex justify-center items-center gap-2">
        <Tooltip content="Approve" placement="bottom" class="relative">
            <Button
                type="button"
                iconOnly
                pill
                size="sm"
                class="justify-center gap-2"
                variant="success"
                v-slot="{ iconSizeClasses }"
                @click="openPendingRequestModal(request.id, 'approve')"
            >
                <CheckIcon aria-hidden="true" :class="iconSizeClasses" />
                <span class="sr-only">approve</span>
            </Button>
        </Tooltip>
        <Tooltip content="Reject" placement="bottom" class="relative">
            <Button
                type="button"
                iconOnly
                pill
                size="sm"
                class="justify-center gap-2"
                variant="danger"
                v-slot="{ iconSizeClasses }"
                @click="openPendingRequestModal(request.id, 'reject')"
            >
                <XIcon aria-hidden="true" :class="iconSizeClasses" />
                <span class="sr-only">reject</span>
            </Button>
        </Tooltip>
    </div>

    <Modal :show="pendingRequestModal" :title="modalComponent" @close="closeModal" max-width="lg">
        <!-- Approve -->
        <div v-if="modalComponent === 'Approve Request'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve Request</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve LOGIN: <span class="font-semibold">{{ request.trading_account.meta_login }}</span> to become MASTER?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button
                    class="px-6 justify-center"
                    @click.prevent="submitForm"
                    :disabled="form.processing"
                >
                    Confirm
                </Button>
            </div>
        </div>

        <!-- Reject -->
        <div v-if="modalComponent === 'Reject Request'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Reject Request</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to reject LOGIN: <span class="font-semibold">{{ request.trading_account.meta_login }}</span> to become MASTER?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click="openPendingRequestModal(request.id, 'rejectRemarks')">Confirm</Button>
            </div>
        </div>

        <!-- Reject Remarks -->
        <div v-if="modalComponent === 'Reject Remark'">
            <div class="flex gap-2 mt-3 mb-8">
                <Label class="text-sm dark:text-white w-1/4 pt-0.5" for="remarks" value="Remarks" />
                <div class="flex flex-col w-full">
                    <Input
                        id="remarks"
                        type="text"
                        placeholder="Enter remark (visible to member)"
                        class="block w-full"
                        v-model="form.remarks"
                        :invalid="form.errors.remarks"
                    />
                    <InputError :message="form.errors.remarks" class="mt-2" />
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button
                    class="px-6 justify-center"
                    @click.prevent="submitForm"
                    :disabled="form.processing"
                >
                    Confirm
                </Button>
            </div>
        </div>
    </Modal>
</template>
