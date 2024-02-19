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
import Action from "@/Pages/Master/Partials/Action.vue";
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

const requests = ref({data: []});
const currentPage = ref(1);
const requestLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const isAllSelected = ref(false);
const { formatDateTime, formatAmount } = transactionFormat();

const getResults = async (page = 1, search = '', date = '') => {
    requestLoading.value = true
    try {
        let url = `/master/getMaster/Pending?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        requests.value = response.data.Pending;

    } catch (error) {
        console.error(error);
    } finally {
        requestLoading.value = false
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

</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="requestLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[900px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <!-- <th scope="col" class="py-3 mx-1 flex items-center justify-center">
                        <Checkbox
                            v-model="isAllSelected"
                            @click="handleSelectAll"
                        />
                    </th> -->
                    <th scope="col" class="py-3">
                        Name
                    </th>
                    <th scope="col" class="py-3">
                        Date
                    </th>
                    <th scope="col" class="py-3">
                        Trading Account
                    </th>
                    <th scope="col" class="py-3 text-center">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="requests.data.length === 0">
                    <th colspan="7" class="py-4 text-lg text-center">
                        No Pending
                    </th>
                </tr>
                <tr
                    v-for="request in requests.data"
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800"
                >
                    <td class="py-3">
                        {{ request.user.name }}
                    </td>
                    <td class="py-3">
                        {{ formatDateTime(request.created_at) }}
                    </td>
                    <td class="py-3">
                        {{ request.trading_account.meta_login }}
                    </td>
                    <td class="py-3 flex justify-center">
                        <Action
                            :request="request"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
