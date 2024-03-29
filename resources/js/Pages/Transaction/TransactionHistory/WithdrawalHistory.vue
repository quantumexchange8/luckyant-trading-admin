<script setup>
import debounce from "lodash/debounce.js";
import {ref, watch} from "vue";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
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

const withdrawals = ref({data: []});
const totalAmount = ref(0);
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const depositLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const { formatAmount, formatDateTime, formatType } = transactionFormat();
const withdrawalHistoryModal = ref(false);
const withdrawalDetail = ref();

watch(
    [() => props.search, () => props.date, () => props.filter, () => props.transactionType],
    debounce(([searchValue, dateValue, filterValue, transactionTypeValue]) => {
        getResults(1, searchValue, dateValue, filterValue, transactionTypeValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '', filter = '', transactionType = '') => {
    depositLoading.value = true
    try {
        let url = `/transaction/getTransactionHistory/Withdrawal?page=${page}`;

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

        const response = await axios.get(url);
        withdrawals.value = response.data.Withdrawal;
        totalAmount.value = response.data.totalAmount;
        
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

        getResults(currentPage.value, props.search, props.date, props.transactionType);
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
        let url = `/transaction/getTransactionHistory/Withdrawal?exportStatus=yes`;

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

const openWithdrawalHistoryModal = (withdrawal) => {
    withdrawalHistoryModal.value = true
    withdrawalDetail.value = withdrawal
}

const closeModal = () => {
    withdrawalHistoryModal.value = false
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
                    Date
                </th>
                <th scope="col" class="pl-5 py-2">
                    Name
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
            <tr v-if="withdrawals.data.length === 0">
                <th colspan="5" class="py-4 text-lg text-center">
                    No History
                </th>
            </tr>
            <tr
                v-for="withdrawal in withdrawals.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600 hover:cursor-pointer dark:hover:bg-gray-800"
                @click="openWithdrawalHistoryModal(withdrawal)"
            >
                <td class="py-2">
                    {{ formatDateTime(withdrawal.created_at) }}
                </td>
                <td class="pl-5 py-2">
                    <div class="inline-flex items-center gap-2">
                        <img :src="withdrawal.user.profile_photo_url ? withdrawal.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        {{ withdrawal.user.name }}
                    </div>
                </td>
                <td class="py-2">
                    <div class="inline-flex items-center gap-2">
                        <!-- <div class="bg-gradient-to-t from-pink-300 to-pink-600 dark:shadow-pink-500 rounded-full w-4 h-4 shrink-0 grow-0">
                            <InternalWalletIcon class="mt-0.5 ml-0.5"/>
                        </div> -->
                        {{ formatType(withdrawal.category) }}
                    </div>
                </td>
                <td class="py-2">
                    {{ withdrawal.transaction_number }}
                </td>
                <td class="py-2">
                    $ {{ withdrawal.amount }}
                </td>
                <td class="py-2 text-center">
                    <Badge
                        :variant="transactionVariant(withdrawal.status)"
                    >
                        {{ withdrawal.status }}
                    </Badge>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!depositLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="withdrawals"
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

    <Modal :show="withdrawalHistoryModal" title="Transaction Details" @close="closeModal">
        <div v-if="withdrawalDetail.category == 'trading_account'">
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Name</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Email</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.user.email }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Type</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.transaction_type }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction ID</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.transaction_number }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date & Time</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(withdrawalDetail.created_at) }}</span>
            </div>
            <div v-if="withdrawalDetail.from_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">From Wallet</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.from_wallet.wallet_address }}</span>
            </div>
            <div v-if="withdrawalDetail.to_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To Wallet</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.to_wallet.wallet_address }}</span>
            </div>
            <div v-if="withdrawalDetail.from_meta_login != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">From Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.from_meta_login }}</span>
            </div>
            <div v-if="withdrawalDetail.to_meta_login != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.to_meta_login }}</span>
            </div>
            <!-- <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">ticket</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.ticket }}</span>
            </div> -->
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Amount</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ withdrawalDetail.amount }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Status</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.status }}</span>
            </div>
        </div>

        <div v-if="withdrawalDetail.category == 'wallet'">
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Name</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Email</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.user.email }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Type</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.transaction_type }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction ID</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.transaction_number }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date & Time</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(withdrawalDetail.created_at) }}</span>
            </div>
            <div v-if="withdrawalDetail.from_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">From Wallet</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.from_wallet.wallet_address }}</span>
            </div>
            <div v-if="withdrawalDetail.to_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To Wallet</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.to_wallet.wallet_address }}</span>
            </div>
            <div v-if="withdrawalDetail.payment_account != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Payment Platform</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.payment_account.payment_platform }}</span>
            </div>
            <div v-if="withdrawalDetail.payment_account != null" class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Payment Account</span>
                <span class="col-span-2 text-black dark:text-white py-2 break-words">{{ withdrawalDetail.payment_account.payment_platform_name }} - {{ withdrawalDetail.payment_account.account_no }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Amount</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ withdrawalDetail.amount }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Payment Charges</span>
                <span class="col-span-2 text-black dark:text-white py-2">
                    $ {{ withdrawalDetail.transaction_charges }}
                </span>
            </div>
            <div v-if="withdrawalDetail.payment_account ? withdrawalDetail.payment_account.payment_platform == 'Bank' : ''" class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Conversion Rate</span>
                <span class="col-span-2 text-black dark:text-white py-2">
                    {{ withdrawalDetail.conversion_rate }}
                </span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="text-sm font-semibold dark:text-gray-400">Transaction Amount</span>
                <span class="col-span-2 text-black dark:text-white py-2">
                    {{ withdrawalDetail.payment_account.payment_platform == 'Bank' ? withdrawalDetail.payment_account.currency : '$ ' }} {{ withdrawalDetail.transaction_amount }}
                </span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Status</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ withdrawalDetail.status }}</span>
            </div>
        </div>
    </Modal>
</template>
