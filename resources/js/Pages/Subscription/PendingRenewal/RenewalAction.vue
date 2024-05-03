<script setup>
import {ref} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {MemberDetailIcon, alertTriangle} from "@/Components/Icons/outline.jsx";
import {CheckIcon, XIcon, BanIcon } from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    renewal: Object,
})

const transactionModal = ref(false);
const modalComponent = ref('');
const { formatDateTime, formatAmount, formatType } = transactionFormat();

const openTransactionModal = (componentType) => {
    transactionModal.value = true;
    if (componentType === 'approve') {
        modalComponent.value = 'Approve Renewal';
    } else if (componentType === 'reject') {
        modalComponent.value = 'Reject Renewal';
    } else if (componentType === 'rejectRemarks') {
        modalComponent.value = 'Reject Remark';
    } else if (componentType === 'view') {
        modalComponent.value = 'Renewal Details';
    }
}

const closeModal = () => {
    transactionModal.value = false
    modalComponent.value = null;
}

const form = useForm({
    id: props.renewal.id,
    subscriptionId: props.renewal.subscription_id,
    userId: props.renewal.user_id,
    remarks: '',
});

const submitForm = () => {
    let submitRoute;
    if (modalComponent.value === 'Approve Renewal') {
        submitRoute = route('subscription.approveRenewalSubscription');
    } else if (modalComponent.value === 'Reject Remark') {
        submitRoute = route('subscription.rejectRenewalSubscription');
    }

    if (submitRoute) {
        form.post(submitRoute, {
            onSuccess: () => {
                closeModal();
                form.reset();
            },
        });
    } else {
        console.error('Invalid modal component:', modalComponent);
    }
}

</script>

<template>
    <Tooltip content="Approve" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="success"
            @click="openTransactionModal('approve')"
        >
            <CheckIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">Approve</span>
        </Button>
    </Tooltip>
    <Tooltip content="Reject" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="danger"
            @click="openTransactionModal('reject')"
        >
            <XIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">Reject</span>
        </Button>
    </Tooltip>
<!--    <Tooltip content="View" placement="bottom">-->
<!--        <Button-->
<!--            type="button"-->
<!--            pill-->
<!--            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"-->
<!--            variant="gray"-->
<!--            @click="openTransactionModal('view')"-->
<!--        >-->
<!--            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />-->
<!--            <span class="sr-only">View</span>-->
<!--        </Button>-->
<!--    </Tooltip>-->

    <Modal :show="transactionModal" :title="modalComponent" @close="closeModal" max-width="lg">
        <div v-if="modalComponent === 'Approve Renewal'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve Renewal</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve this renewal request?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">Confirm</Button>
            </div>
        </div>

        <div v-if="modalComponent === 'Reject Renewal'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Reject Subscriber</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to reject this renewal request?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click="openTransactionModal('rejectRemarks')">Confirm</Button>
            </div>
        </div>

        <div v-if="modalComponent === 'Reject Remark'">
            <div class="flex gap-2 mt-3 mb-8">
                <Label class="text-sm text-black dark:text-white w-1/4 pt-0.5" for="remark" value="Remark" />
                <div class="flex flex-col w-full">
                    <Input
                        id="remark"
                        type="text"
                        placeholder="Enter remark (visible to member)"
                        class="block w-full"
                        :invalid="form.errors.remarks"
                        v-model="form.remarks"
                    />
                    <InputError :message="form.errors.remarks" class="mt-2" />
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4 border-t dark:border-gray-700">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">Confirm</Button>
            </div>
        </div>

        <div v-if="modalComponent === 'Renewal Details'">
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(subscriber.created_at) }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Subscription Number</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.subscription.subscription_number }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">User</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.subscription.meta_login }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Master</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.subscription.master.user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Master Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.subscription.master.meta_login }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Subscription Period</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.subscription.subscription_period }} Days</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Subscription Fee</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ subscriber.subscription.subscription_fee }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Expired Date</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(subscriber.subscription.expired_date) }}</span>
            </div>
        </div>
    </Modal>
</template>
