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
    subscriber: Object,
})

const transactionModal = ref(false);
const modalComponent = ref('');
const { formatDateTime, formatAmount, formatType } = transactionFormat();

const openTransactionModal = (componentType) => {
    transactionModal.value = true;
    if (componentType === 'approve') {
        modalComponent.value = 'Approve subscription';
    } else if (componentType === 'reject') {
        modalComponent.value = 'Reject subscription';
    } else if (componentType === 'rejectRemarks') {
        modalComponent.value = 'Reject Remark';
    } else if (componentType === 'view') {
        // modalComponent.value = 'Renewal Details';
        modalComponent.value = 'Subscriber Details';

    } else if (componentType === 'termination') {
        modalComponent.value = 'Termination';
    } else if (componentType === 'terminateRemarks') {
        modalComponent.value = 'Terminate Remark';
    }
}

const closeModal = () => {
    transactionModal.value = false
    modalComponent.value = null;
}

const form = useForm({
    subscriber_id: props.subscriber.id,
    userId: props.subscriber.user_id,
    transactionId: props.subscriber.transaction_id,
    remarks: '',
});

const submitForm = () => {
    let submitRoute;
    if (modalComponent.value === 'Approve subscription') {
        submitRoute = route('subscriber.approveSubscribe');
    } else if (modalComponent.value === 'Reject Remark') {
        submitRoute = route('subscriber.rejectSubscribe');
    } else if (modalComponent.value === 'Terminate Remark') {
        submitRoute = route('subscriber.terminateSubscribe');
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
    <Tooltip v-if="subscriber.status !== 'Subscribing'" content="Approve" placement="bottom">
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
    <Tooltip v-if="subscriber.status !== 'Subscribing'" content="Reject" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="danger"
            @click="openTransactionModal('reject')"
        >
            <XIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">reject</span>
        </Button>
    </Tooltip>
    <Tooltip content="View" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openTransactionModal('view')"
        >
            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">Reset</span>
        </Button>
    </Tooltip>

    <Tooltip v-if="subscriber.status === 'Subscribing'" content="Termination" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="danger"
            @click="openTransactionModal('termination')"
        >
            <BanIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">Reset</span>
        </Button>
    </Tooltip>

    <Modal :show="transactionModal" :title="modalComponent" @close="closeModal" max-width="lg">
        <div v-if="modalComponent === 'Approve subscription'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve Subscriber</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve this subscription?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button
                    type="button"
                    variant="secondary"
                    class="px-6 justify-center"
                    @click="closeModal"
                >
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

        <div v-if="modalComponent === 'Reject subscription'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Reject Subscriber</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to reject this subscription?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button
                    type="button"
                    variant="secondary"
                    class="px-6 justify-center"
                    @click="closeModal"
                >
                    Cancel
                </Button>
                <Button
                    class="px-6 justify-center"
                    @click="openTransactionModal('rejectRemarks')"
                    :disabled="form.processing"
                >
                    Confirm
                </Button>
            </div>
        </div>

        <div v-if="modalComponent === 'Termination'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Terminate Subscriber</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to terminate this subscription?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button
                    class="px-6 justify-center"
                    @click="openTransactionModal('terminateRemarks')"
                    :disabled="form.processing"
                >
                    Confirm
                </Button>
            </div>
        </div>

        <div v-if="modalComponent === 'Reject Remark'">
            <form>
                <div class="flex gap-2 mt-3 mb-8">
                    <Label class="text-sm text-black dark:text-white w-1/4 pt-0.5" for="remark" value="Remark" />
                    <div class="flex flex-col w-full">
                        <Input
                            id="remark"
                            type="text"
                            placeholder="Enter remark (visible to member)"
                            class="block w-full"
                            :class="form.errors.remarks ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                            v-model="form.remarks"
                        />
                        <InputError :message="form.errors.remarks" class="mt-2" />
                    </div>
                </div>
                <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                    <Button
                        type="button"
                        variant="secondary"
                        class="px-6 justify-center"
                        @click="closeModal"
                    >
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
            </form>
        </div>

        <div v-if="modalComponent === 'Terminate Remark'">
            <div class="flex gap-2 mt-3 mb-8">
                <Label class="text-sm text-black dark:text-white w-1/4 pt-0.5" for="remark" value="Remark" />
                <div class="flex flex-col w-full">
                    <Input
                        id="remark"
                        type="text"
                        placeholder="Enter remark (visible to member)"
                        class="block w-full"
                        :class="form.errors.remarks ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.remarks"
                    />
                    <InputError :message="form.errors.remarks" class="mt-2" />
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4 border-t dark:border-gray-700">
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

        <div v-if="modalComponent === 'Subscriber Details'">
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(subscriber.created_at)}}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">User</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.first_leader') }}</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.first_leader }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.meta_login }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Master Name</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.master.trading_user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Master Account</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.master.meta_login }}</span>
            </div>
            <!-- <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">subscriber Fee</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ subscriber.subscriber.subscription_fee ? subscriber.subscriber.subscription_fee : '0.00' }}</span>
            </div> -->
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Copy Trade Balance</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ formatAmount(subscriber.initial_meta_balance ? subscriber.initial_meta_balance : 0) }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Settlement Period</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.roi_period }} Days</span>
            </div>
            <div v-if="subscriber.approval_date != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Approval Date</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.approval_date }}</span>
            </div>
            <div v-if="subscriber.expired_date != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Expired Date</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ subscriber.expired_date ? subscriber.expired_date : '-'}}</span>
            </div>
        </div>
    </Modal>
</template>
