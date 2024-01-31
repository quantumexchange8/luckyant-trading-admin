<script setup>
import {CheckIcon, XIcon, QuestionMarkCircleIcon, ClipboardListIcon} from "@heroicons/vue/outline";
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import Input from "@/Components/Input.vue";
import Label from "@/Components/Label.vue";

const props = defineProps({
    member: Object
})

const kycApprovalModal = ref(false);
const modalComponent = ref(null);
const approvalType = ref('');
const { formatDateTime } = transactionFormat();

const openKycApprovalModal = (ibId, componentType) => {
    kycApprovalModal.value = true;
    if (componentType === 'approve') {
        approvalType.value = 'approve'
        modalComponent.value = 'Approve KYC';
    } else if (componentType === 'reject') {
        approvalType.value = 'reject'
        modalComponent.value = 'Reject KYC';
    } else if (componentType === 'view') {
        modalComponent.value = 'KYC Details';
    }
}

const form = useForm({
    id: props.member.id,
    type: '',
    remark: ''
});

const closeModal = () => {
    kycApprovalModal.value = false
    modalComponent.value = null;
}

const submitForm = () => {
    form.type = approvalType.value;
    form.post(route('member.verify_member'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const handleButton = (type) => {
    if (type === 'reject') {
        approvalType.value = 'reject'
        modalComponent.value = 'Reject KYC';
    } else if (type === 'approve') {
        approvalType.value = 'approve'
        modalComponent.value = 'Approve KYC';
    }
}
</script>

<template>
    <div class="inline-flex justify-center items-center gap-2">
        <Tooltip content="Approve" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="success"
                @click="openKycApprovalModal(member.id, 'approve')"
            >
                <CheckIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">View</span>
            </Button>
        </Tooltip>
        <Tooltip content="Reject" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="danger"
                @click="openKycApprovalModal(member.id, 'reject')"
            >
                <XIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">Transfer Upline</span>
            </Button>
        </Tooltip>
        <Tooltip content="View" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="gray"
                @click="openKycApprovalModal(member.id, 'view')"
            >
                <ClipboardListIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">Reset</span>
            </Button>
        </Tooltip>
    </div>

    <Modal :show="kycApprovalModal" :title="modalComponent" @close="closeModal" :max-width="modalComponent === 'KYC Details' ? '4xl' : 'lg'">

        <div v-if="modalComponent === 'Approve KYC'">
            <div class="px-2 space-y-2">
                <div class="flex justify-center">
                    <div class="flex items-center justify-center rounded-full w-14 h-14 grow-0 shrink-0 bg-warning-300">
                        <QuestionMarkCircleIcon class="w-14 text-white" />
                    </div>
                </div>
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve KYC</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve KYC and verify this member?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">Confirm</Button>
            </div>
        </div>

        <!-- Reject -->
        <div v-if="modalComponent === 'Reject KYC'">
            <div class="flex gap-2 mt-3 mb-8">
                <Label class="text-sm dark:text-white w-1/4 pt-0.5" for="remark" value="Remark" />
                <div class="flex flex-col w-full">
                    <Input
                        id="remark"
                        type="text"
                        placeholder="Enter remark (visible to member)"
                        class="block w-full"
                        :class="form.errors.remark ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.remark"
                    />
                    <InputError :message="form.errors.remark" class="mt-2" />
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4 border-t dark:border-gray-700">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">Confirm</Button>
            </div>
        </div>

        <!-- View -->
        <div v-if="modalComponent === 'KYC Details'">
            <div class="p-5 mt-3 bg-white overflow-hidden md:overflow-visible rounded-xl dark:bg-gray-700">
                <div class="flex flex-col justify-center items-center">
                    <img :src="member.profile_photo_url ? member.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-16 h-16 rounded-full" alt="">
                    <div class="text-xl font-semibold dark:text-white mt-3">
                        {{ member.name }}
                    </div>
                    <div class="text-sm font-normal dark:text-gray-400">
                        {{ member.identity_number }}
                    </div>
                </div>
            </div>
            <div class="py-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="flex flex-col gap-2">
                        <div class="text-sm text-left w-full dark:text-white">
                            Proof of Identity (FRONT)
                        </div>
                        <div class="dark:bg-white rounded-lg w-full flex justify-center border-2 dark:border-white">
                            <img :src="member.front_identity" class="max-h-64 rounded-lg" alt="">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="text-sm text-left w-full dark:text-white">
                            Proof of Identity (BACK)
                        </div>
                        <div class="dark:bg-white rounded-lg w-full flex justify-center border-2 dark:border-white">
                            <img :src="member.back_identity" class="max-h-64 rounded-lg" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-sm font-normal dark:text-gray-400 pb-8">
                Uploaded on {{ formatDateTime(member.kyc_upload_date, false) }}
            </div>
            <div class="pt-8 border-t dark:border-gray-700">
                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="danger"
                        class="flex justify-center px-6"
                        @click="handleButton('reject')"
                    >
                        Reject
                    </Button>
                    <Button
                        type="button"
                        variant="success"
                        class="flex justify-center px-6"
                        @click="handleButton('approve')"
                    >
                        Approve KYC
                    </Button>
                </div>
            </div>
        </div>

    </Modal>
</template>
