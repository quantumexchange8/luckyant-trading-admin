<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
import {alertTriangle} from "@/Components/Icons/outline.jsx";
import {computed, onUnmounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Action from "@/Pages/Subscription/Partials/Action.vue";
import debounce from "lodash/debounce.js";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    // exportStatus: Boolean,
})

const subscribers = ref({data: []});
const depositLoading = ref(props.isLoading);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const { formatDateTime, formatAmount } = transactionFormat();
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);

watch(
    [() => props.search, () => props.date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '') => {
    depositLoading.value = true
    try {
        let url = `/subscription/getPendingSubscriber?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
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

        getResults(currentPage.value, props.search, props.date);
    }
};

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
                        Master
                    </th>
                    <th scope="col" class="p-3">
                        Master Trading Account
                    </th>
                    <th scope="col" class="p-3">
                        Subscription Fee
                    </th>
                    <th scope="col" class="p-3 text-center">
                        Action
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
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800"
                >
                    <td class="p-2.5">
                        {{ formatDateTime(subscriber.created_at) }}
                    </td>
                    <td class="p-2.5">
                        {{ subscriber.user.name }}
                    </td>
                    <td class="p-2.5">
                        {{ subscriber.meta_login }}
                    </td>
                    <td class="p-2.5">
                        {{ subscriber.master.user.name }}
                    </td>
                    <td class="p-2.5">
                        {{ subscriber.master.meta_login }}
                    </td>
                    <td class="p-2.5">
                        {{ subscriber.subscription.subscription_fee }}
                    </td>
                    <td class="p-2.5 text-center">
                        <Action :subscriber="subscriber"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>