<script setup>
import debounce from "lodash/debounce.js";
import {ref, watch} from "vue";
import {ArrowLeftIcon, ArrowRightIcon, SortAscendingIcon} from "@heroicons/vue/outline";
import {InternalWalletIcon} from "@/Components/Icons/outline.jsx";
import Loading from "@/Components/Loading.vue";
import {transactionFormat} from "@/Composables/index.js";
import {TailwindPagination} from "laravel-vue-pagination";
import Modal from "@/Components/Modal.vue";
import Badge from "@/Components/Badge.vue";

const props = defineProps({
    search: String,
    date: String,
    filter: String,
    transactionType: String,
    refresh: Boolean,
    isLoading: Boolean,
    exportStatus: Boolean,
})

const { formatAmount, formatType } = transactionFormat();
const deposits = ref({data: []});
const totalAmount = ref(0);
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const depositLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const { formatDateTime } = transactionFormat();
const depositHistoryModal = ref(false);
const depositDetail = ref();
const sortDescending = ref('desc');
const types = ref('')

const toggleSort = (type) => {
  sortDescending.value = sortDescending.value === 'desc' ? 'asc' : 'desc';
  types.value = type;
}

watch(
    [() => props.search, () => props.date, () => props.filter, () => props.transactionType, () => types.value, () => sortDescending.value],
    debounce(([searchValue, dateValue, filterValue, transactionTypeValue, typeValue, sortValue]) => {
        getResults(1, searchValue, dateValue, filterValue, transactionTypeValue, typeValue, sortValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '', filter = '', transactionType = '', type = types.value, sort = sortDescending.value) => {
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

        if (transactionType) {
            url += `&transactionType=${transactionType}`;
        }

        if (type) {
            url += `&type=${type}`;
            url += `&sort=${sort}`;
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

        getResults(currentPage.value, props.search, props.date, props.filter, props.transactionType, types.value, sortDescending.value);
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

        if (props.transactionType) {
            url += `&transactionType=${props.transactionType}`;
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

const transactionVariant = (transactionStatus) => {
    if (transactionStatus === 'Success') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
}


</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="depositLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[800px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr>
                <th scope="col" class="py-2">
                    <div class="flex gap-2">
                        <div>
                            <span>Date</span>
                        </div>
                        <div 
                            class="transition-transform transform"
                            :class="{ 'rotate-180': sortDescending === 'asc' }"
                            @click="toggleSort('created_at')"
                        >
                            <SortAscendingIcon class="w-5 h-5 hover:cursor-pointer" />
                        </div>
                    </div>
                </th>
                <th scope="col" class="pl-5 py-2">
                    <div class="flex gap-2">
                        <div>
                            <span>Name</span>
                        </div>
                        <!-- <div 
                            class="transition-transform transform"
                            :class="{ 'rotate-180': sortDescending === 'asc' }"
                            @click="toggleSort('name')"
                        > -->
                            <!-- <SortAscendingIcon class="w-5 h-5 hover:cursor-pointer" /> -->
                        <!-- </div> -->
                    </div>
                </th>
                <th scope="col" class="py-2">
                    Type
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
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600 hover:cursor-pointer dark:hover:bg-gray-800"
                @click="openDepositHistoryModal(deposit)"
            >
                <td class="py-2">
                    {{ formatDateTime(deposit.created_at) }}
                </td>
                <td class="pl-5 py-2">
                    <div class="inline-flex items-center gap-2">
                        <img :src="deposit.user.profile_photo_url ? deposit.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        {{ deposit.user.name }}
                    </div>
                </td>
                <td class="py-2">
                    <div class="inline-flex items-center gap-2">
                        <!-- <div class="bg-gradient-to-t from-pink-300 to-pink-600 dark:shadow-pink-500 rounded-full w-4 h-4 shrink-0 grow-0">
                            <InternalWalletIcon class="mt-0.5 ml-0.5"/>
                        </div> -->
                        {{ formatType(deposit.category) }}
                    </div>
                </td>
                <td class="py-2">
                    {{ deposit.transaction_number }}
                </td>
                <td class="py-2">
                    $ {{ deposit.amount }}
                </td>
                <td class="py-2 text-center">
                    <Badge
                        :variant="transactionVariant(deposit.status)"
                    >
                        {{ deposit.status }}
                    </Badge>
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
        <div v-if="depositDetail.category == 'trading_account'">
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
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.transaction_type }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction ID</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.transaction_number }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date & Time</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(depositDetail.created_at) }}</span>
            </div>
            <div v-if="depositDetail.from_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">From Wallet</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.from_wallet.wallet_address }}</span>
            </div>
            <div v-if="depositDetail.to_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To Wallet</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.to_wallet.wallet_address }}</span>
            </div>
            <div v-if="depositDetail.from_meta_login != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">From Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.from_meta_login }}</span>
            </div>
            <div v-if="depositDetail.to_meta_login != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.to_meta_login }}</span>
            </div>
            <!-- <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">ticket</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.ticket }}</span>
            </div> -->
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Amount</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ depositDetail.amount }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Status</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.status }}</span>
            </div>
            <div v-if="depositDetail.status == 'Rejected'" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Remarks</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.remarks }}</span>
            </div>
        </div>
        <div v-if="depositDetail.category == 'wallet'">
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
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.transaction_type }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction ID</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.transaction_number }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date & Time</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(depositDetail.created_at) }}</span>
            </div>
            <div v-if="depositDetail.to_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To Wallet</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.to_wallet.wallet_address }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Payment Platform</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.payment_method }}</span>
            </div>
            <div v-if="depositDetail.setting_payment != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Payment Account</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.setting_payment.payment_account_name }}</span>
            </div>
            <div v-if="depositDetail.setting_payment != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Payment Account Number</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.setting_payment.account_no }}</span>
            </div>
            <div v-if="depositDetail.payment_method == 'Payment Merchant'" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Txn Hash</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ depositDetail.txn_hash }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Amount</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ depositDetail.amount }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Status</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.status }}</span>
            </div>
            <div v-if="depositDetail.status == 'Rejected'" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Remarks</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ depositDetail.remarks }}</span>
            </div>
        </div>
    </Modal>
</template>
