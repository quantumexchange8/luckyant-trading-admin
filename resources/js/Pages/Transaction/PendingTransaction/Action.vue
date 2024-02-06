<script setup>
import {ref} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {MemberDetailIcon, alertTriangle} from "@/Components/Icons/outline.jsx";
import {CheckIcon, XIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    transaction: Object
})

const transactionModal = ref(false);
const modalComponent = ref(null);
const { formatDateTime, formatAmount, formatType } = transactionFormat();

const openTransactionModal = (ibId, componentType) => {
    transactionModal.value = true;
    if (componentType === 'approve') {
        modalComponent.value = 'Approve Transaction';
    } else if (componentType === 'reject') {
        modalComponent.value = 'Reject Transaction';
    } else if (componentType === 'view') {
        modalComponent.value = 'Transaction Details';
    }
}

const closeModal = () => {
    transactionModal.value = false
    modalComponent.value = null;
}

const form = useForm({
    id: props.transaction.id,
    type: 'single',
});

const submitForm = () => {
    let submitRoute;
    if (modalComponent.value === 'Approve Transaction') {
        submitRoute = route('transaction.approveTransaction');
    } else if (modalComponent.value === 'Reject Transaction') {
        submitRoute = route('transaction.rejectTransaction');
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
        <Tooltip content="Approve" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="success"
                @click="openTransactionModal(transaction.id, 'approve')"
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
                @click="openTransactionModal(transaction.id, 'reject')"
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
                variant="action"
                @click="openTransactionModal(transaction.id, 'view')"
            >
                <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">Reset</span>
            </Button>
        </Tooltip>
    </div>

    <Modal :show="transactionModal" :title="modalComponent" @close="closeModal" max-width="lg">
        <!-- Approve -->
        <div v-if="modalComponent === 'Approve Transaction'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve Transaction</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve a total amount of ${{ formatAmount(transaction.amount )}}?
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
        <div v-if="modalComponent === 'Reject Transaction'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Reject Transaction</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to reject a total amount of ${{ formatAmount(transaction.amount )}}?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">Confirm</Button>
            </div>
        </div>

        <!-- View -->
        <div v-if="modalComponent === 'Transaction Details'">
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Name</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ transaction.user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Email</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ transaction.user.email }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">ID Number</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ transaction.transaction_id }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Date & time</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(transaction.created_at) }}</span>
            </div>
            <div v-if="transaction.type === 'Deposit'" class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Transaction Hash</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.txn_hash }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">To Wallet Address</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.to_wallet_address }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Amount</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ transaction.amount }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Transaction Status</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatType(transaction.status) }}</span>
            </div>
        </div>

    </Modal>

</template>
