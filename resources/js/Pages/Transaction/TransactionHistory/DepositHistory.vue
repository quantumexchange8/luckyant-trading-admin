<script setup>
import debounce from "lodash/debounce.js";
import {ref, watch} from "vue";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
import {InternalWalletIcon} from "@/Components/Icons/outline.jsx";
import Loading from "@/Components/Loading.vue";
import {transactionFormat} from "@/Composables/index.js";
import {TailwindPagination} from "laravel-vue-pagination";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    search: String,
    date: String,
    filter: String,
    refresh: Boolean,
    isLoading: Boolean,
    exportStatus: Boolean,
})

const { formatAmount } = transactionFormat();
const deposits = ref({data: []});
const totalAmount = ref(0);
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const depositLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const { formatDateTime } = transactionFormat();
const depositHistoryModal = ref(false);
const depositDetail = ref();

watch(
    [() => props.search, () => props.date, () => props.filter],
    debounce(([searchValue, dateValue, filterValue]) => {
        getResults(1, searchValue, dateValue, filterValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '', filter = '') => {
    depositLoading.value = true
    try {
        let url = `/transaction/getTransactionHistory/Deposit?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (filter) {
            url += `&filter=${filter}`;
        }
        
        const response = await axios.get(url);
        deposits.value = response.data.Deposit;
        totalAmount.value = response.data.totalAmount;
    } catch (error) {
        console.error(error.response.data);
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
        let url = `/transaction/getTransactionHistory/Deposit?exportStatus=yes`;

        if (props.date) {
            url += `&date=${props.date}`;
        }

        if (props.search) {
            url += `&search=${props.search}`;
        }

        if (props.filter) {
            url += `&filter=${props.filter}`;
        }

        window.location.href = url;
        emit('update:export', false);
    }
});

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400 dark:enabled:hover:text-white'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-[#FF9E23] dark:text-white'
];

const openDepositHistoryModal = (deposit) => {
    depositHistoryModal.value = true
    depositDetail.value = deposit
}

const closeModal = () => {
    depositHistoryModal.value = false
}
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="depositLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[800px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-700 uppercase bg-gray-50 dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-600">
            <tr>
                <th scope="col" class="pl-5 py-2">
                    Name
                </th>
                <th scope="col" class="py-2">
                    Asset
                </th>
                <th scope="col" class="py-2">
                    Date
                </th>
                <th scope="col" class="py-2">
                    Transaction ID
                </th>
                <th scope="col" class="py-2">
                    Amount
                </th>
                <th scope="col" class="py-2 text-center">
                    Status
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="deposits.data.length === 0">
                <th colspan="5" class="py-4 text-lg text-center">
                    No History
                </th>
            </tr>
            <tr
                v-for="deposit in deposits.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600 hover:cursor-pointer dark:hover:bg-gray-600"
                @click="openDepositHistoryModal(deposit)"
            >
                <td class="pl-5 py-2">
                    <div class="inline-flex items-center gap-2">
                        <img :src="deposit.user.profile_photo_url ? deposit.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        {{ deposit.user.name }}
                    </div>
                </td>
                <td class="py-2">
                    <div class="inline-flex items-center gap-2">
                        <div class="bg-gradient-to-t from-pink-300 to-pink-600 dark:shadow-pink-500 rounded-full w-4 h-4 shrink-0 grow-0">
                            <InternalWalletIcon class="mt-0.5 ml-0.5"/>
                        </div>
                        {{ deposit.wallet.name }}
                    </div>
                </td>
                <td class="py-2">
                    {{ formatDateTime(deposit.created_at) }}
                </td>
                <td class="py-2">
                    {{ deposit.transaction_id }}
                </td>
                <td class="py-2">
                    $ {{ deposit.amount }}
                </td>
                <td class="py-2 text-center">
                    <span v-if="deposit.status === 'Success'" class="flex w-2 h-2 bg-green-500 dark:bg-success-500 mx-auto rounded-full"></span>
                    <span v-else-if="deposit.status === 'Pending'" class="flex w-2 h-2 bg-red-500 dark:bg-warning-500 mx-auto rounded-full"></span>
                    <span v-else-if="deposit.status === 'Processing'" class="flex w-2 h-2 bg-red-500 dark:bg-[#007AFF] mx-auto rounded-full"></span>
                    <span v-else-if="deposit.status === 'Rejected'" class="flex w-2 h-2 bg-red-500 dark:bg-error-500 mx-auto rounded-full"></span>
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
        <div class="text-xl font-semibold">
            Total Amount: ${{ formatAmount(totalAmount) }}
        </div>
    </div>

    <Modal :show="depositHistoryModal" title="Transaction Details" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Name</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.user.name }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Email</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.user.email }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Type</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.type }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction ID</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.transaction_id }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date & Time</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(depositDetail.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To</span>
            <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.to_wallet_address }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Amount</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ depositDetail.amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Status</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.status }}</span>
        </div>
    </Modal>
</template>
