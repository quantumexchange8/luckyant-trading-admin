<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
import {alertTriangle} from "@/Components/Icons/outline.jsx";
import {computed, onUnmounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Badge from "@/Components/Badge.vue";
import Modal from "@/Components/Modal.vue";
import debounce from "lodash/debounce.js";
import Action from "@/Pages/Member/TradingListing/Partials/Action.vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    exportStatus: Boolean,
    leverageSel: Array,
})

const { formatDateTime, formatAmount } = transactionFormat();
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const tradingListings = ref({data: []});
const tradingLoading = ref(props.isLoading);
const refreshDeposit = ref(props.refresh);
const currentPage = ref(1);

watch(
    [() => props.search, () => props.date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '') => {
    tradingLoading.value = true
    try {
        let url = `/member/getTradingAccount?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        tradingListings.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        tradingLoading.value = false
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
        let url = `/member/getTradingAccount?exportStatus=yes`;

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
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="tradingLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[900px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" class="p-3">
                        Date 
                    </th>
                    <th scope="col" class="p-3">
                        Trading Account  
                    </th>
                    <th scope="col" class="p-3">
                        Balance 
                    </th>
                    <th scope="col" class="p-3">
                        Margin Leverage 
                    </th>
                    <th scope="col" class="p-3">
                        Equity 
                    </th>
                    <th scope="col" class="p-3">
                        User 
                    </th>
                    <th scope="col" class="p-3">
                        Action 
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="tradingListings.data.length === 0">
                    <th colspan="7" class="py-4 text-lg text-center">
                        No Trading Account
                    </th>
                </tr>
                <tr
                    v-for="tradingListing in tradingListings.data"
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800"
                >
                    <td class="p-3">
                        {{ tradingListing.created_at }}
                    </td>
                    <td class="p-3">
                        <div class="flex flex-col ">
                            <span>Account: {{ tradingListing.meta_login }}</span>
                            <span>User Name: {{ tradingListing.trading_user.name }}</span>
                        </div>
                        
                    </td>
                    <td class="p-3">
                        $ {{ formatAmount(tradingListing.balance) }}
                    </td>
                    <td class="p-3">
                        1:{{ tradingListing.margin_leverage }}
                    </td>
                    <td class="p-3">
                       $ {{ formatAmount(tradingListing.equity) }}
                    </td>
                    <td class="p-3">
                    <div class="inline-flex gap-2">
                        <div>
                            <img :src="tradingListing.user.profile_photo_url ? tradingListing.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        </div>
                        <div class="flex flex-col">
                            <span>{{ tradingListing.user.name }}</span>
                            <span>{{ tradingListing.user.email }}</span>
                        </div>
                        
                    </div>
                        
                    </td>
                    <td class="p-3">
                        <Action :tradingListing="tradingListing" :leverageSel="leverageSel"/>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!tradingLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="tradingListings"
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
    
</template>