<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
import {alertTriangle} from "@/Components/Icons/outline.jsx";
import {InternalWalletIcon} from "@/Components/Icons/outline.jsx";
import debounce from "lodash/debounce.js";
import {computed, onUnmounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Checkbox from "@/Components/Checkbox.vue";
import Action from "@/Pages/Transaction/PendingTransaction/Action.vue";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import toast from "@/Composables/toast.js";
import Alert from "@/Components/Alert.vue";
import {Inertia} from "@inertiajs/inertia";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    exportStatus: Boolean,
})
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const deposits = ref({data: []});
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const depositLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const { formatDateTime, formatAmount } = transactionFormat();
const totalAmount = ref(0);
const isChecked = ref([]);
const isAllSelected = ref(false);
const transactionModal = ref(false);
const modalComponent = ref(null);
const showAlert = ref(false);
const intent = ref(null);
const alertTitle = ref('');
const alertMessage = ref(null);

const openTransactionModal = (componentType) => {
    transactionModal.value = true;
    if (componentType === 'approve') {
        modalComponent.value = 'Approve Transaction';
    } else if (componentType === 'reject') {
        modalComponent.value = 'Reject Transaction';
    }
}

const closeModal = () => {
    transactionModal.value = false
    modalComponent.value = null;
}

watch(
    [() => props.search, () => props.date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

// Watch for changes in isChecked
const updateChecked = (id, amount) => {
    if (isNaN(amount)) {
        console.error(`Invalid amount: ${amount}`);
        return;  // Skip further processing for this deposit
    }

    const index = isChecked.value.indexOf(id);
    if (index !== -1) {
        isChecked.value.splice(index, 1);
        totalAmount.value = parseFloat(totalAmount.value) - parseFloat(amount);
    } else {
        isChecked.value.push(id);
        totalAmount.value = parseFloat(totalAmount.value) + parseFloat(amount);
    }
};

const handleSelectAll = () => {
    isAllSelected.value = !isAllSelected.value;
    if (isAllSelected.value) {
        const depositData = deposits.value.data;

        for (const deposit of depositData) {
            const id = deposit.id;
            // Check if the ID is already in isChecked
            if (!isChecked.value.includes(id)) {
                // If not, add it to isChecked
                isChecked.value.push(id);
                totalAmount.value += parseFloat(deposit.amount);
            }
        }
    } else {
        // Deselect all
        isChecked.value = [];
        totalAmount.value = 0; // Reset totalAmount
    }
};

const getResults = async (page = 1, search = '', date = '') => {
    depositLoading.value = true
    try {
        let url = `/transaction/getPendingTransaction/Deposit?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        deposits.value = response.data.Deposit;
    } catch (error) {
        console.error(error);
    } finally {
        depositLoading.value = false
        emit('update:loading', false);
    }
}

getResults()

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;

        getResults(currentPage.value, props.search, props.date);
    }
};

watch(() => props.refresh, (newVal) => {
    refreshDeposit.value = newVal;
    if (newVal) {
        // Call the getResults function when refresh is true
        getResults();
        emit('update:refresh', false);
    }
});

watch(() => props.exportStatus, (newVal) => {
    refreshDeposit.value = newVal;
    if (newVal) {
        let url = `/transaction/getPendingTransaction/Deposit?exportStatus=yes`;

        if (props.date) {
            url += `&date=${props.date}`;
        }

        if (props.search) {
            url += `&search=${props.search}`;
        }

        window.location.href = url;
        emit('update:export', false);
    }
});

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400 dark:enabled:hover:text-white'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-[#FF9E23] dark:text-white'
];

function isItemSelected(id, amount) {
    return isChecked.value.some(deposit =>
        deposit.id === id &&
        deposit.amount === amount
    );
}

const approveTransaction = async () => {
    try {
        await axios.post('/transaction/approveTransaction', {
            id: isChecked.value,
            type: 'approve_selected',
        });

        closeModal()
        showAlert.value = true
        intent.value = 'success'
        alertTitle.value = 'Deposit approved'
        alertMessage.value = 'This deposit request has been approved successfully.'

        totalAmount.value = 0;
        await getResults();
    } catch (error) {
        console.error('Error approving transaction:', error);
    }
};

const rejectTransaction = async () => {
    try {
        await axios.post('/transaction/rejectTransaction', {
            id: isChecked.value,
            type: 'reject_selected',
        });

        closeModal()
        showAlert.value = true
        intent.value = 'success'
        alertTitle.value = 'Deposit reject'
        alertMessage.value = 'This deposit request has been rejected successfully.'

        totalAmount.value = 0;
        await getResults();
    } catch (error) {
        console.error('Error approving transaction:', error);
    }
};

</script>

<template>
    <Alert
        :show="showAlert"
        :on-dismiss="() => showAlert = false"
        :title="alertTitle"
    >
      {{ alertMessage }}
    </Alert>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="depositLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[900px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-700 uppercase bg-gray-50 dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-600">
            <tr>
                <th scope="col" class="py-3 mx-1 flex items-center justify-center">
                    <Checkbox
                        v-model="isAllSelected"
                        @click="handleSelectAll"
                    />
                </th>
                <th scope="col" class="py-3">
                    Name
                </th>
                <th scope="col" class="py-3">
                    Asset
                </th>
                <th scope="col" class="py-3">
                    Date
                </th>
                <th scope="col" class="py-3">
                    Transaction ID
                </th>
                <th scope="col" class="py-3">
                    Amount
                </th>
                <th scope="col" class="py-3 text-center">
                    Action
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="deposits.data.length === 0">
                <th colspan="7" class="py-4 text-lg text-center">
                    No Pending
                </th>
            </tr>
            <tr
                v-for="deposit in deposits.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600"
            >
                <td class="py-3 mx-1 text-center">
                    <Checkbox
                        :checked="isAllSelected || isItemSelected(deposit.id, deposit.amount)"
                        :model-value="isChecked.includes(deposit.id)"
                        @update:model-value="updateChecked(deposit.id, deposit.amount)"
                    />
                </td>
                <td class="py-3">
                    <div class="inline-flex items-center gap-2">
                        <img :src="deposit.user.profile_photo_url ? deposit.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        {{ deposit.user.name }}
                    </div>
                </td>
                <td class="py-3">
                    <div class="inline-flex items-center gap-2">
                        <div class="bg-gradient-to-t from-pink-300 to-pink-600 dark:shadow-pink-500 rounded-full w-4 h-4 shrink-0 grow-0">
                            <InternalWalletIcon class="mt-0.5 ml-0.5"/>
                        </div>
                        {{ deposit.wallet.name }}
                    </div>
                </td>
                <td class="py-3">
                    {{ formatDateTime(deposit.created_at) }}
                </td>
                <td class="py-3">
                    {{ deposit.transaction_id }}
                </td>
                <td class="py-3">
                    ${{ deposit.amount }}
                </td>
                <td class="py-3 text-center">
                    <Action
                        :transaction="deposit"
                    />
                </td>
            </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!depositLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="deposits"
                :limit=2
                @pagination-change-page="handlePageChange"
            >
                <template #prev-nav>
                    <span class="flex gap-2"><ArrowLeftIcon class="w-5 h-5" /> Previous</span>
                </template>
                <template #next-nav>
                    <span class="flex gap-2">Next <ArrowRightIcon class="w-5 h-5" /></span>
                </template>
            </TailwindPagination>
        </div>
        <div class="flex justify-between items-center">
            <div class="text-xl font-semibold">
                Total Amount: ${{ formatAmount(totalAmount) }}
            </div>
            <div class="pt-3 px-2 grid grid-cols-2 gap-4">
                <Button
                    type="button"
                    variant="success"
                    class="px-6 justify-center"
                    :disabled="totalAmount === 0"
                    @click="openTransactionModal('approve')"
                >
                    Approve all
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    class="px-6 justify-center"
                    :disabled="totalAmount === 0"
                    @click="openTransactionModal('reject')"
                >
                    Reject all
                </Button>
            </div>
        </div>
    </div>

    <Modal :show="transactionModal" :title="modalComponent" @close="closeModal" max-width="lg">

        <div v-if="modalComponent === 'Approve Transaction'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve Deposit</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve a total deposit amount of ${{ formatAmount(totalAmount) }}?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="approveTransaction">Confirm</Button>
            </div>
        </div>

        <!-- Reject -->
        <div v-if="modalComponent === 'Reject Transaction'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Reject Deposit</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to reject a total deposit amount of ${{ formatAmount(totalAmount) }}?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="rejectTransaction">Confirm</Button>
            </div>
        </div>

    </Modal>
</template>
