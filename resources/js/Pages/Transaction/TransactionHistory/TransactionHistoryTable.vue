<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {computed, ref, watch, watchEffect} from "vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import Button from "@/Components/Button.vue";
import Tooltip from "@/Components/Tooltip.vue";
import Modal from "@/Components/Modal.vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import {CheckIcon, ChevronDownIcon, SortAscendingIcon } from '@heroicons/vue/solid'

const props = defineProps({
    search: String,
    date: String,
    category: String,
    fund_type: String,
    methods: String,
    status: String,
    refresh: Boolean,
    isLoading: Boolean,
    transactionType: String,
    exportStatus: Boolean,
})
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const transactions = ref({data: []});
const currentPage = ref(1);
const refreshTransaction = ref(props.refresh);
const transactionLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export', 'update:totalAmount', 'update:successAmount', 'update:rejectedAmount']);
const { formatDateTime, formatAmount, formatType } = transactionFormat();
const transactionModal = ref(false);
const transactionDetail = ref(null);

const sortDescending = ref('desc');
const types = ref('')

const toggleSort = (sortType) => {
    sortDescending.value = sortDescending.value === 'desc' ? 'asc' : 'desc';
    types.value = sortType;
}

watch(
    [() => props.search, () => props.category, () => props.fund_type, () => props.methods, () => props.status, () => props.date, () => props.transactionType, () => types.value, () => sortDescending.value],
    debounce(([searchValue, categoryValue, fundTypeValue, methodsValue, statusValue, dateValue, typeValue, sortValue]) => {
        getResults(1, searchValue, categoryValue, fundTypeValue, methodsValue, statusValue, dateValue, typeValue, sortValue);
    }, 300)
);

const getResults = async (page = 1, search = props.search, category = props.category, fund_type = props.fund_type, methods = props.methods, status = props.status, date = props.date, type = props.transactionType, sortType = types.value, sort = sortDescending.value) => {
    transactionLoading.value = true
    try {
        let url = `/transaction/getTransactionHistory?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (category) {
            url += `&category=${category}`;
        }

        if (fund_type) {
            url += `&fund_type=${fund_type}`;
        }

        if (methods) {
            url += `&methods=${methods}`;
        }

        if (status) {
            url += `&status=${status}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (sortType) {
            url += `&sortType=${sortType}`;
            url += `&sort=${sort}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data.transactions;
        emit('update:totalAmount', response.data.totalAmount);
        emit('update:successAmount', response.data.successAmount);
        emit('update:rejectedAmount', response.data.rejectedAmount);
    } catch (error) {
        console.error(error);
    } finally {
        transactionLoading.value = false
        emit('update:loading', false);
    }
}

getResults()

const handlePageChange = (newPage) => {
    if (newPage >= 1) {

        currentPage.value = newPage;

        getResults(currentPage.value, props.search, props.category, props.fund_type, props.methods, props.status, props.date, props.kycStatus, types.value, sortDescending.value);
    }
};

watch(() => props.refresh, (newVal) => {
    refreshTransaction.value = newVal;
    if (newVal) {
        // Call the getResults function when refresh is true
        getResults();
        emit('update:refresh', false);
    }
});

watch(() => props.exportStatus, (newVal) => {
    refreshTransaction.value = newVal;
    if(newVal) {

        let url = `/transaction/getTransactionHistory?exportStatus=yes`;

        if (props.date) {
            url += `&date=${props.date}`;
        }

        if (props.search) {
            url += `&search=${props.search}`;
        }

        if (props.transactionType) {
            url += `&type=${props.transactionType}`;
        }

        if (props.category) {
            url += `&category=${props.category}`;
        }

        if (props.fund_type) {
            url += `&fund_type=${props.fund_type}`;
        }

        if (props.methods) {
            url += `&methods=${props.methods}`;
        }

        if (props.status) {
            url += `&status=${props.status}`;
        }

        window.location.href = url;
        emit('update:export', false);
    }
})

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-primary-500 dark:text-white'
];

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Processing') return 'processing';
    if (transactionStatus === 'Success') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
}

const openTransactionModal = (transaction) => {
    transactionModal.value = true;
    transactionDetail.value = transaction;
}


const closeModal = () => {
    transactionModal.value = false
}
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="transactionLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr>
                <th scope="col" class="p-3">
                    {{ $t('public.date') }}
                </th>
                <th scope="col" class="p-3 min-w-40">
                    {{ $t('public.name') }}
                </th>
                <th scope="col" class="p-3 min-w-40">
                    {{ $t('public.first_leader') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.from') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.to') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.payment_methods') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.fund_type') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.transaction_no') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.amount') }}
                </th>
                <th v-if="transactionType === 'Withdrawal'" scope="col" class="p-3">
                    {{ $t('public.payment_charges') }}
                </th>
                <th v-if="transactionType === 'Withdrawal'" scope="col" class="p-3">
                    {{ formatType(transactionType) }} {{ $t('public.amount') }}
                </th>
                <th v-if="transactionType === 'Withdrawal'" scope="col" class="p-3">
                    {{ $t('public.profit') }}
                </th>
                <th v-if="transactionType === 'Withdrawal'" scope="col" class="p-3">
                    {{ $t('public.bonus') }}
                </th>
                <th scope="col" class="p-3 text-center">
                    {{ $t('public.status') }}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="transactions.data.length === 0">
                <th colspan="7" class="py-4 text-lg text-center">
                    {{ $t('public.no_history') }}
                </th>
            </tr>
            <tr
                v-for="transaction in transactions.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-600"
                @click="openTransactionModal(transaction)"
            >
                <td class="p-3">
                    <div class="inline-flex items-center gap-2">
                        {{ formatDateTime(transaction.created_at) }}
                    </div>
                </td>
                <td class="p-3">
                    <div class="flex items-center gap-2">
                        <img :src="transaction.user.profile_photo_url ? transaction.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        <div class="flex flex-col gap-1">
                            <div>
                                {{ transaction.user.name }}
                            </div>
                            <div>
                                {{ transaction.user.email }}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="p-3">
                    <div class="flex flex-col gap-1">
                        <div>
                            {{ transaction.user.first_leader ? transaction.user.first_leader.name : '-' }}
                        </div>
                        <div>
                            {{ transaction.user.first_leader ? transaction.user.first_leader.email : '-' }}
                        </div>
                    </div>
                </td>
                <td class="p-3">
                    <div class="flex flex-col gap-1">
                        <div>
                            {{ transaction.from_wallet ? $t('public.' + transaction.from_wallet.type) : (transaction.from_meta_login ? $t('public.account_no') : '-') }}
                        </div>
                        <div class="font-semibold">
                            {{ transaction.from_meta_login ?? '-' }}
                        </div>
                    </div>
                </td>
                <td v-if="transaction.transaction_type === 'Transfer'" class="p-3 flex flex-col gap-1">
                    <div>
                        {{ transaction.to_wallet ? transaction.to_wallet.user.name : '-' }}
                    </div>
                    <div>
                        {{ transaction.to_wallet ? transaction.to_wallet.user.email : '-' }}
                    </div>
                </td>
                <td v-else class="p-3">
                    <div v-if="transaction.category === 'wallet' && transaction.transaction_type === 'Withdrawal'" class="flex flex-col gap-1">
                        <div>
                            {{ transaction.payment_account ? transaction.payment_account.payment_account_name : '-' }} <span v-if="transaction.payment_account && transaction.payment_account.payment_platform === 'Bank'">({{ transaction.payment_account ? transaction.payment_account.payment_platform_name : '' }})</span>
                        </div>
                        <div>
                            {{ transaction.payment_account ? transaction.payment_account.account_no : '-' }}
                        </div>
                    </div>
                    <div v-else class="flex flex-col gap-1">
                        <div>
                            {{ transaction.to_wallet ? $t('public.' + transaction.to_wallet.type) : (transaction.to_meta_login ? $t('public.account_no') : '-') }}
                        </div>
                        <div class="font-semibold">
                            {{ transaction.to_meta_login ?? '-' }}
                        </div>
                    </div>
                </td>
                <td class="p-3">
                    {{ transaction.payment_method ?? '-' }}
                </td>
                <td class="p-3">
                    {{ formatType(transaction.fund_type ? transaction.fund_type : '-' ) }}
                </td>
                <td class="p-3">
                    {{ transaction.transaction_number }}
                </td>
                <td class="p-3">
                    $ {{ transaction.amount }}
                </td>
                <td v-if="transaction.transaction_type === 'Withdrawal'" class="p-3">
                    $ {{ transaction.transaction_charges }}
                </td>
                <td v-if="transaction.transaction_type === 'Withdrawal'" class="p-3 text-red-500">
                    $ {{ transaction.transaction_amount }}
                </td>
                <td v-if="transaction.transaction_type === 'Withdrawal'" class="p-3">
                    $ {{ formatAmount(transaction.profit_amount ? transaction.profit_amount : 0) }}
                </td>
                <td v-if="transaction.transaction_type === 'Withdrawal'" class="p-3">
                    $ {{ formatAmount(transaction.bonus_amount ? transaction.bonus_amount : 0) }}
                </td>
                <td class="p-3">
                    <div class="flex items-center justify-center">
                        <Badge :variant="statusVariant(transaction.status)">{{ transaction.status }}</Badge>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!transactionLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="transactions"
                :limit=2
                @pagination-change-page="handlePageChange"
            />
        </div>
    </div>

    <Modal :show="transactionModal" :title="$t('public.transaction_details')" @close="closeModal" max-width="xl">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_type') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.transaction_type }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.from') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.from_wallet ? $t('public.' + transactionDetail.from_wallet.type) : (transactionDetail.from_meta_login ? $t('public.account_no') + ' - ' + transactionDetail.from_meta_login : '-') }}</span>
        </div>
        <div class="grid grid-cols-3 items-start gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to') }}</span>
            <div v-if="transactionDetail.transaction_type === 'Transfer'">
                <div>
                    {{ transactionDetail.to_wallet ? transactionDetail.to_wallet.user.name : '-' }} ({{ $t('public.' + transactionDetail.to_wallet.type) }})
                </div>
                <div>
                    {{ transactionDetail.to_wallet ? transactionDetail.to_wallet.user.email : '-' }}
                </div>
            </div>
            <div v-else>
                <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.to_wallet ? $t('public.' + transactionDetail.to_wallet.type) : (transactionDetail.to_meta_login ? $t('public.account_no') + ' - ' + transactionDetail.to_meta_login.meta_login : '-') }}</span>
            </div>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.payment_methods') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.payment_method ?? '-' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.fund_type') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatType(transactionDetail.fund_type ? transactionDetail.fund_type : '-' ) }}</span>
        </div>
        <div v-if="transactionDetail.transaction_type === 'Deposit'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to_account') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.to_account_no ?? '-' }}</span>
        </div>
        <div v-if="transactionDetail.transaction_type === 'Withdrawal'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to_account') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.payment_account ? transactionDetail.payment_account.payment_platform_name + ' - ' + transactionDetail.payment_account.account_no : '-' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_no') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.transaction_number }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.amount') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetail.amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.payment_charges') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetail.transaction_charges }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_amount') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetail.transaction_amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.date_and_time') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(transactionDetail.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.status') }}</span>
            <Badge :variant="statusVariant(transactionDetail.status)" width="auto">{{ transactionDetail.status }}</Badge>
        </div>
        <div v-if="transactionDetail.status === 'Rejected'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.remarks') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.remarks }}</span>
        </div>
    </Modal>
</template>
