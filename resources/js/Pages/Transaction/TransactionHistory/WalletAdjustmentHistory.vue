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
    refresh: Boolean,
    isLoading: Boolean,
    exportStatus: Boolean,
})

const wallets = ref({data: []});
const currentPage = ref(1);
const refreshWallet = ref(props.refresh);
const walletLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const { formatDateTime, formatAmount } = transactionFormat();
const walletHistoryModal = ref(false);
const walletDetail = ref();

watch(
    [() => props.search, () => props.date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '') => {
    walletLoading.value = true
    try {
        let url = `/transaction/getBalanceHistory/WalletAdjustment?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }
        
        const response = await axios.get(url);
        wallets.value = response.data.WalletAdjustment;
    } catch (error) {
        console.error(error);
    } finally {
        walletLoading.value = false
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
    refreshWallet.value = newVal;
    if (newVal) {
        // Call the getResults function when refresh is true
        getResults();
        emit('update:refresh', false);
    }
});

watch(() => props.exportStatus, (newVal) => {
    refreshWallet.value = newVal;
    if (newVal) {
        let url = `/transaction/getBalanceHistory/WalletAdjustment?exportStatus=yes`;

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

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400 dark:enabled:hover:text-white'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-[#FF9E23] dark:text-white'
];

const openWalletHistoryModal = (wallet) => {
    walletHistoryModal.value = true
    walletDetail.value = wallet
}

const closeModal = () => {
    walletHistoryModal.value = false
}
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="walletLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[650px] table-fixed md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-700 uppercase bg-gray-50 dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-600">
            <tr>
                <th scope="col" class="pl-5 py-2">
                    Date
                </th>
                <th scope="col" class="py-2">
                    Name
                </th>
                <th scope="col" class="py-2">
                    After Adjustment
                </th>
                <th scope="col" class="py-2">
                    Remark
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="wallets.data.length === 0">
                <th colspan="4" class="py-4 text-lg text-center">
                    No History
                </th>
            </tr>
            <tr
                v-for="wallet in wallets.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600 hover:cursor-pointer dark:hover:bg-gray-600"
                @click="openWalletHistoryModal(wallet)"
            >
                <td class="pl-5 py-2">
                    {{ formatDateTime(wallet.created_at) }}
                </td>
                <td class="py-2">
                    <div class="inline-flex items-center gap-2">
                        <img :src="wallet.user.profile_photo_url ? wallet.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        {{ wallet.user.name }}
                    </div>
                </td>
                <td class="py-2">
                    $ {{ formatAmount(wallet.new_balance) }}
                </td>
                <td class="py-2">
                    {{ wallet.description }}
                </td>
            </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!walletLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="wallets"
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
    </div>

    <Modal :show="walletHistoryModal" title="Wallet Adjustment Details" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Name</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ walletDetail.user.name }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Email</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ walletDetail.user.email }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date & Time</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(walletDetail.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Before Adjustment</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ formatAmount(walletDetail.old_balance) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Adjust Amount</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ formatAmount(walletDetail.amount) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">After Adjustment</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ formatAmount(walletDetail.new_balance) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Remark</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ walletDetail.description }}</span>
        </div>
    </Modal>
</template>
