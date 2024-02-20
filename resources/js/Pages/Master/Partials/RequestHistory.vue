<script setup>
import {ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import Loading from "@/Components/Loading.vue";
import {transactionFormat} from "@/Composables/index.js";
import {TailwindPagination} from "laravel-vue-pagination";
import Modal from "@/Components/Modal.vue";
import Badge from "@/Components/Badge.vue";

const props = defineProps({
    search: String,
    date: String,
    filter: String,
    refresh: Boolean,
    isLoading: Boolean,
    exportStatus: Boolean,
})


const { formatAmount, formatDateTime } = transactionFormat();
const masterLoading = ref(props.isLoading);
const histories = ref({data: []});
const masterHistoryModal = ref(false);
const masterHistoryDetail = ref();
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const refreshHistory = ref(props.refresh);

watch(
    [() => props.search, () => props.date, () => props.filter],
    debounce(([searchValue, dateValue, filterValue]) => {
        getResults(1, searchValue, dateValue, filterValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '', filter = '') => {
    masterLoading.value = true
    try {
        let url = `/master/getMasterHistory?page=${page}`;

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
        histories.value = response.data;

    } catch (error) {
        console.error(error.response.data);
    } finally {
        masterLoading.value = false
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
    refreshHistory.value = newVal;
    if (newVal) {
        // Call the getResults function when refresh is true
        getResults();
        emit('update:refresh', false);
    }
});

const transactionVariant = (transactionStatus) => {
    if (transactionStatus === 'Success') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
}

const openMasterHistoryModal = (history) => {
    masterHistoryModal.value = true
    masterHistoryDetail.value = history
}

const closeModal = () => {
    masterHistoryModal.value = false
}
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="masterLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[800px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
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
                        Status
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="histories.data.length === 0">
                    <th colspan="7" class="py-4 text-lg text-center">
                        No History
                    </th>
                </tr>
                <tr 
                    v-for="history in histories.data"
                    class="bg-white hover:bg-gray-100 dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer dark:hover:bg-gray-800"
                    @click="openMasterHistoryModal(history)"
                >
                    <td class="py-3">
                        {{ history.user.name }}
                    </td>
                    <td class="py-3">
                        {{ formatDateTime(history.created_at) }}
                    </td>
                    <td class="py-3">
                        {{ history.trading_account.meta_login }}
                    </td>
                    <td class="py-3">
                        <Badge
                            :variant="transactionVariant(history.status)"
                        >
                            {{ history.status }}
                        </Badge>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal :show="masterHistoryModal" title="Master Request Details" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Name</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ masterHistoryDetail.user.name }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(masterHistoryDetail.created_at) }}</span>
        </div>
        
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Trading Account</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ masterHistoryDetail.trading_account.meta_login }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Status</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ masterHistoryDetail.status }}</span>
        </div>
        <div v-if="masterHistoryDetail.status == 'Rejected'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Remarks</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ masterHistoryDetail.remarks }}</span>
        </div>
    </Modal>
</template>