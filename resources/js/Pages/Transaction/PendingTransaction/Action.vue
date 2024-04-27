<script setup>
import {ref} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {MemberDetailIcon, alertTriangle} from "@/Components/Icons/outline.jsx";
import {CheckIcon, XIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";

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
    } else if (componentType === 'rejectRemarks') {
        modalComponent.value = 'Reject Remark';
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
    remarks: '',
});

const submitForm = () => {
    let submitRoute;
    if (modalComponent.value === 'Approve Transaction') {
        submitRoute = route('transaction.approveTransaction');
    } else if (modalComponent.value === 'Reject Remark') {
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
                <span class="sr-only">approve</span>
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
                <span class="sr-only">reject</span>
            </Button>
        </Tooltip>
        <Tooltip content="View" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="gray"
                @click="openTransactionModal(transaction.id, 'view')"
            >
                <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">view</span>
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
                <Button class="px-6 justify-center" @click="openTransactionModal(transaction.id, 'rejectRemarks')">Confirm</Button>
            </div>
        </div>

        <!-- Reject Remarks -->
        <div v-if="modalComponent === 'Reject Remark'">
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


        <!-- View -->
        <div v-if="modalComponent === 'Transaction Details'" class="pb-3">
            <div v-if="transaction.transaction_type == 'Deposit'">
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
                    <span class="col-span-2 text-black dark:text-white py-2">{{ transaction.transaction_number }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Date & time</span>
                    <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(transaction.created_at) }}</span>
                </div>
                <div v-if="transaction.transaction_type == 'Deposit'" class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">To Wallet Address</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.to_wallet.wallet_address }}</span>
                </div>
                <div v-if="transaction.transaction_type == 'Withdrawal'" class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">From Wallet Address</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.from_wallet.wallet_address }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Payment Platform</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.payment_method }}</span>
                </div>
                <div v-if="transaction.payment_account != null" class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Account Name</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.payment_account.payment_account_name }}</span>
                </div>
                <div v-if="transaction.payment_account != null" class="grid grid-cols-3 items-center gap-2">
                    <span v-if="transaction.payment_method == 'Crypto'" class="text-sm font-semibold dark:text-gray-400">USDT Address</span>
                    <span v-if="transaction.payment_method == 'Bank'" class="text-sm font-semibold dark:text-gray-400">Bank Account</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.to_wallet_address }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Amount</span>
                    <span class="col-span-2 text-black dark:text-white py-2">$ {{ transaction.amount }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2 pb-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Transaction Status</span>
                    <span class="col-span-2 text-black dark:text-white py-2">{{ formatType(transaction.status) }}</span>
                </div>

                <div v-if="transaction.payment_method == 'Crypto' && transaction.transaction_type == 'Deposit'" class="grid grid-cols-3 items-center gap-2 border-b pb-3">
                    <span class="text-xl font-semibold dark:text-gray-400">Slip</span>
                </div>

                <div v-if="transaction.payment_method == 'Crypto'" class="flex justify-center items-center gap-2 pb-2">
                    <img v-if="transaction.transaction_type == 'Deposit'"
                    :src="transaction.receipt_url ? transaction.receipt_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                    alt=""
                    class="pt-5"
                    />

                </div>
            </div>
            <div v-if="transaction.transaction_type == 'Withdrawal'">
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
                    <span class="col-span-2 text-black dark:text-white py-2">{{ transaction.transaction_number }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Date & time</span>
                    <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(transaction.created_at) }}</span>
                </div>
                <div v-if="transaction.transaction_type == 'Deposit'" class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">To Wallet Address</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.to_wallet.wallet_address }}</span>
                </div>
                <div v-if="transaction.transaction_type == 'Withdrawal'" class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">From Wallet Address</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.from_wallet.wallet_address }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Payment Platform</span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.payment_method }}</span>
                </div>
                <div v-if="transaction.payment_account != null" class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">To Account </span>
                    <span class="col-span-2 text-black dark:text-white py-2 break-all">{{ transaction.payment_account.payment_platform_name }} - {{ transaction.payment_account.account_no }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Amount</span>
                    <span class="col-span-2 text-black dark:text-white py-2">
                        $ {{ formatAmount(transaction.amount) }}
                    </span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Payment Charges</span>
                    <span class="col-span-2 text-black dark:text-white py-2">
                        $ {{ transaction.transaction_charges }}
                    </span>
                </div>
                <div v-if="transaction.payment_account.payment_platform == 'Bank'" class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Conversion Rate</span>
                    <span class="col-span-2 text-black dark:text-white py-2">{{ formatType(transaction.conversion_rate) }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Transaction Amount</span>
                    <span class="col-span-2 text-black dark:text-white py-2">
                        {{ transaction.payment_account.payment_platform == 'Bank' ? transaction.payment_account.currency : '$ ' }} {{ transaction.transaction_amount }}
                    </span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2 pb-2">
                    <span class="text-sm font-semibold dark:text-gray-400">Transaction Status</span>
                    <span class="col-span-2 text-black dark:text-white py-2">{{ formatType(transaction.status) }}</span>
                </div>
                <div v-if="transaction.payment_method == 'Crypto' && transaction.transaction_type == 'Deposit'" class="grid grid-cols-3 items-center gap-2 border-b pb-3">
                    <span class="text-xl font-semibold dark:text-gray-400">Slip</span>
                </div>

                <div v-if="transaction.payment_method == 'Crypto'" class="flex justify-center items-center gap-2 pb-2">
                    <img v-if="transaction.transaction_type == 'Deposit'"
                    :src="transaction.receipt_url ? transaction.receipt_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                    alt=""
                    class="pt-5"
                    />
                </div>
            </div>
        </div>
    </Modal>

</template>
