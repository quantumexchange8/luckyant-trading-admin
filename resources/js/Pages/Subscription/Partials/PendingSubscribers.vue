<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
import {alertTriangle} from "@/Components/Icons/outline.jsx";
import {computed, onUnmounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Action from "@/Pages/Subscription/Partials/Action.vue";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    // exportStatus: Boolean,
})

const subscriptions = ref({data: []});
const depositLoading = ref(props.isLoading);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const { formatDateTime, formatAmount } = transactionFormat();
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const refreshDeposit = ref(props.refresh);

watch(
    [() => props.search, () => props.date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '') => {
    depositLoading.value = true
    try {
        let url = `/subscription/getPendingSubscriptions?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        subscriptions.value = response.data;

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

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400 dark:enabled:hover:text-white'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-[#FF9E23] dark:text-white'
];

const currentLocale = ref(usePage().props.locale);
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
                        Master Account
                    </th>
                    <th scope="col" class="p-3">
                        Copy Trade Balance
                    </th>
                    <th scope="col" class="p-3 text-center">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="subscriptions.data.length === 0">
                    <th colspan="7" class="py-4 text-lg text-center">
                        No Pending
                    </th>
                </tr>
                <tr
                    v-for="subscription in subscriptions.data"
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800"
                >
                    <td class="p-3">
                        {{ formatDateTime(subscription.created_at) }}
                    </td>
                    <td class="p-3">
                        <div class="flex items-center gap-2">
                            <img :src="subscription.user.profile_photo_url ? subscription.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                            <div class="flex flex-col">
                                <div>
                                    {{ subscription.user.name }}
                                </div>
                                <div class="dark:text-gray-400">
                                    {{ subscription.user.email }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="p-3">
                        {{ subscription.meta_login }}
                    </td>
                    <td class="p-3">
                        <div class="flex flex-col">
                            <div v-if="currentLocale === 'en'">
                                {{ subscription.master.trading_user.name }}
                            </div>
                            <div v-if="currentLocale === 'cn'">
                                {{ subscription.master.trading_user.company ? subscription.master.trading_user.company : subscription.master.trading_user.name }}
                            </div>
                            <div class="font-semibold">
                                {{ subscription.master.meta_login }}
                            </div>
                        </div>
                    </td>
                    <td class="p-3">
                        $ {{ formatAmount(subscription.meta_balance) }}
                    </td>
                    <td class="p-3 text-center">
                        <Action :subscription="subscription"/>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!depositLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="subscriptions"
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
