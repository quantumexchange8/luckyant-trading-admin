<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
import {alertTriangle} from "@/Components/Icons/outline.jsx";
import {computed, onUnmounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Action from "@/Pages/Subscription/Partials/Action.vue";
import debounce from "lodash/debounce.js";
import Badge from "@/Components/Badge.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    filter: String,
    date: String,
    exportStatus: Boolean,
    leader: Object,
})

const subscribers = ref({data: []});
const depositLoading = ref(props.isLoading);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const { formatDateTime, formatAmount } = transactionFormat();
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);

watch(
    [() => props.search, () => props.date, () => props.filter, () => props.leader],
    debounce(([searchValue, dateValue, filterValue, leaderValue]) => {
        getResults(1, searchValue, dateValue, filterValue, leaderValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '', filter = '', leader = '') => {
    depositLoading.value = true
    try {
        let url = `/subscription/getHistorySubscriber?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (filter) {
            url += `&filter=${filter}`;
        }

        if (leader) {
            url += `&leader=${leader.value}`;
        }

        const response = await axios.get(url);
        subscribers.value = response.data;

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

        getResults(currentPage.value, props.search, props.date, props.leader);
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
        let url = `/subscription/getHistorySubscriber?exportStatus=yes`;

        if (props.date) {
            url += `&date=${props.date}`;
        }

        if (props.search) {
            url += `&search=${props.search}`;
        }

        if (props.filter) {
            url += `&filter=${props.filter}`;
        }

        if (props.leader) {
            url += `&leader=${props.leader.value}`;
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


const transactionVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
    if (transactionStatus === 'Expired') return 'warning';
    if (transactionStatus === 'Terminated') return 'danger';
}

const subscriptionHistoryModal = ref(false);
const subscriptionDetail = ref();

const openSubscriptionHistoryModal = (subscriber) => {
    subscriptionHistoryModal.value = true;
    subscriptionDetail.value = subscriber;
}

const closeModal = () => {
    subscriptionHistoryModal.value = false;
}
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="depositLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[900px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" class="p-3">
                        Date
                    </th>
                    <th scope="col" class="p-3">
                        User
                    </th>
                    <th scope="col" class="p-3">
                        Trading Account
                    </th>
                    <th scope="col" class="p-3">
                        First Leader
                    </th>
                    <th scope="col" class="p-3">
                        Master
                    </th>
                    <th scope="col" class="p-3">
                        Master Trading Account
                    </th>
                    <th scope="col" class="p-3">
                        Copy Trade Balance
                    </th>
                    <th scope="col" class="p-3">
                        Approval Date
                    </th>
                    <th scope="col" class="p-3">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="subscribers.data.length === 0">
                    <th colspan="7" class="py-4 text-lg text-center">
                        No Pending
                    </th>
                </tr>
                <tr
                    v-for="subscriber in subscribers.data"
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer"
                    @click="openSubscriptionHistoryModal(subscriber)"
                >
                    <td class="p-3">
                        {{ formatDateTime(subscriber.created_at) }}
                    </td>
                    <td class="p-3">
                        {{ subscriber.user.name }}
                    </td>
                    <td class="p-3">
                        {{ subscriber.meta_login }}
                    </td>
                    <td class="p-3">
                        {{ subscriber.first_leader ? subscriber.first_leader.name : 'LuckyAnt Trading' }}
                    </td>
                    <td class="p-3">
                        <!-- {{ subscriber.master ? subscriber.master.trading_user.name : '-' }} -->
                        {{ subscriber.master.trading_user ? subscriber.master.trading_user.name : '-' }}
                    </td>
                    <td class="p-3">
                        {{ subscriber.master ? subscriber.master.meta_login : '-' }}
                    </td>
                    <td class="p-3">
                       $ {{ subscriber.meta_balance }}
                    </td>
                    <td class="p-3">
                        {{ subscriber.approval_date ? subscriber.approval_date : '-' }}
                    </td>
                    <td class="p-3">
                        <Badge
                            :variant="transactionVariant(subscriber.status)"
                        >
                            {{ subscriber.status }}
                        </Badge>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!depositLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="subscribers"
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

    <Modal :show="subscriptionHistoryModal" title="Subscription Details" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(subscriptionDetail.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">User</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.user.name }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Trading Account</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.meta_login }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Master</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.master.user.name }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Master Trading Account</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.master.meta_login }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Subscription ID</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.subscription_number }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Subscription Period</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.subscription_period }} Days</span> 
        </div>
        <!-- <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Subscription Fee</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ subscriptionDetail.subscription_fee ? subscriptionDetail.subscription_fee : '0.00' }}</span>
        </div> -->
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Copy Trade Balance</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ subscriptionDetail.meta_balance }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Approval Date</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.approval_date ? subscriptionDetail.approval_date : '-' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Expired Date</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.expired_date ? subscriptionDetail.expired_date : '-' }}</span>
        </div>
        <div v-if="subscriptionDetail.status == 'Terminated'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Termination Date</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.termination_date }}</span>
        </div>
        <div v-if="subscriptionDetail.status == 'Terminated' || subscriptionDetail.status == 'Rejected'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Remarks</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionDetail.remarks }}</span>
        </div>
    </Modal>
</template>