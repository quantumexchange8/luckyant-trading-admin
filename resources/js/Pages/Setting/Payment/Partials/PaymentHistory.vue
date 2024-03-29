<script setup>
import {transactionFormat} from "@/Composables/index.js";
import {ref, watch} from "vue";
import Badge from "@/Components/Badge.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import debounce from "lodash/debounce.js";
import Loading from "@/Components/Loading.vue";
import {ArrowLeftIcon, ArrowRightIcon, ClipboardListIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Tooltip from "@/Components/Tooltip.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    paymentHistories: Array,
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    filter: String,

})

const { formatDateTime } = transactionFormat();
const histories = ref({data: []});
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const historyLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh']);

const transactionVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}

watch(
    [() => props.search, () => props.date, () => props.filter ],
    debounce(([searchValue, dateValue, filterValue]) => {
        getResults(1, searchValue, dateValue, filterValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '', filter = '') => {
    historyLoading.value = true
    try {
        let url = `/setting/getPaymentActivity?page=${page}`;
        
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
        historyLoading.value = false

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

function refreshTable() {
    search.value = '';
    date.value = '';
    filter.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}
const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400 dark:enabled:hover:text-white'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-[#FF9E23] dark:text-white'
];

const paymentDetailModal = ref(false);
const paymentDetail = ref();

const openEditModal = (history) => {
    paymentDetailModal.value = true;
    paymentDetail.value = history;
}

const closeModal = () => {
    paymentDetailModal.value = false
}
</script>

<template>


    <div class="flex justify-between items-center self-stretch border-gray-300 dark:border-gray-500 pb-2">
        <div class="flex items-center gap-4">
            <!-- <div class="w-full">
                <InputIconWrapper class="w-full md:w-[280px]">
                    <template #icon>
                        <SearchIcon aria-hidden="true" class="w-5 h-5" />
                    </template>
                    <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
                </InputIconWrapper>
            </div>
            <div class="w-full">
                <vue-tailwind-datepicker
                    placeholder="Select dates"
                    :formatter="formatter"
                    separator=" - "
                    v-model="date"
                    input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                    class="w-full md:w-[230px]"
                />
            </div> -->
            <!-- <div class="w-full">
                <BaseListbox
                    id="statusID"
                    class="rounded-lg text-base text-black w-full md:w-[155px] dark:text-white dark:bg-gray-600"
                    v-model="filter"
                    :options="statusList"
                    placeholder="Filter status"
                />
            </div> -->
            <!-- <div>
                <Button
                    type="button"
                    variant="secondary"
                    @click="refreshTable"
                    class="w-full md:w-auto flex items-center justify-center px-3 py-2 border border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                    Clear
                </Button>
            </div> -->
        </div>
    </div>
    <div>



        <div v-if="historyLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" colspan="2" class="p-3 text-center">
                        Date
                    </th>
                    <th scope="col" colspan="2" class="p-3 text-center w-56">
                        Description
                    </th>
                    <th scope="col" colspan="2" class="p-3 text-center w-56">
                        Updated By
                    </th>
                    <th scope="col" colspan="2" class="p-3 text-center w-56">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="histories.data.length === 0">
                    <th colspan="15" class="py-4 text-lg text-center">
                        No History
                    </th>
                </tr>
                <tr
                    v-for="history in histories.data"
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600 dark:hover:bg-gray-800"
                >
                    <td class="p-3 text-center" colspan="2">
                        {{ formatDateTime(history.created_at) }}
                    </td>
                    <td class="p-3 text-center" colspan="2">
                        {{ history.description }}
                        <!-- {{ history.properties.old.payment_account_name }} - {{ history.properties.attributes.payment_account_name }} -->
                    </td>
                    <td class="p-3 text-center" colspan="2">
                        {{ history.causer.name }}
                    </td>
                    <td class="p-3 text-center" colspan="2">
                        <Tooltip content="View Details" placement="bottom">
                            <Button
                                type="button"
                                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                                variant="gray"
                                pill
                                @click="openEditModal(history)"
                            >
                                <ClipboardListIcon aria-hidden="true" class="w-5 h-5 absolute" />
                                <span class="sr-only">View Details</span>
                            </Button>
                        </Tooltip>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!historyLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="histories"
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

    <Modal :show="paymentDetailModal" title="Payment Details" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(paymentDetail.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Description</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ paymentDetail.description }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span v-if="paymentDetail.event != 'deleted'" class="col-span-1 text-sm font-semibold dark:text-gray-400">Properties</span>
            
            <span v-if="paymentDetail.event == 'updated'" class="col-span-2 text-black dark:text-white py-2">
                <b>Old</b>
                <!-- {{ paymentDetail.properties.old }}
                {{ paymentDetail.properties.attributes }} -->
                <div v-for="old in paymentDetail.properties.old" class="pl-3">
                    <li>{{ old }}</li>
                </div>
                <b>New</b>
                <div v-for="attributes in paymentDetail.properties.attributes" class="pl-3">
                    <li>{{ attributes }}</li>
                </div>
            </span>

            <span v-if="paymentDetail.event == 'created'" class="col-span-2 text-black dark:text-white py-2">
                <div v-for="attributes in paymentDetail.properties.attributes" class="pl-3">
                    <li>{{ attributes }}</li>
                </div>
            </span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Updated By</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ paymentDetail.causer.name }}</span>
        </div>
    </Modal>
        
</template>