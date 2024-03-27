<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {computed, ref, watch, watchEffect} from "vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import Action from "@/Pages/Master/MasterListing/Partials/Action.vue"
import Badge from "@/Components/Badge.vue";

const props = defineProps({
    search: String,
    refresh: Boolean,
    isLoading: Boolean,
})

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const masters = ref({data: []});
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const isLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh']);
const { formatDateTime, formatAmount } = transactionFormat();

watch(
    [() => props.search, () => props.rank, () => props.date],
    debounce(([searchValue, rankValue, dateValue]) => {
        getResults(1, searchValue, rankValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = props.search , rank = props.rank, date = props.date, type = props.kycStatus) => {
    isLoading.value = true
    try {
        let url = `/master/getAllMaster?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        const response = await axios.get(url);
        masters.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
        emit('update:loading', false);
    }
}

getResults()

const transactionVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="isLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" class="p-3">
                        Name
                    </th>
                    <th scope="col" class="p-3">
                        Trading Account
                    </th>
                    <th scope="col" class="p-3">
                        Min Join Equity
                    </th>
                    <th scope="col" class="p-3">
                        Sharing Profit
                    </th>
                    <th scope="col" class="p-3">
                        Management Fee
                    </th>
                    <th scope="col" class="p-3">
                        Status
                    </th>
                    <th scope="col" class="p-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="masters.data.length === 0">
                    <th colspan="7" class="py-4 text-lg text-center">
                        No History
                    </th>
                </tr>
                <tr 
                    v-for="master in masters.data"
                    class="bg-white hover:bg-gray-100 dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 dark:hover:bg-gray-800"

                >
                <td class="p-3">
                    <div class="inline-flex items-center gap-2">
                        <!-- <img :src="member.profile_photo_url ? member.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt=""> -->
                        <img :src="'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                        <div class="flex flex-col">
                            <div>
                                {{ master.user.name }} 
                            </div>
                            <div class="dark:text-gray-400">
                                {{ master.user.email }} 
                            </div>
                        </div>
                    </div>
                </td>
                    <td class="p-3">
                        {{ master.trading_account.meta_login }}
                    </td>
                    <td class="p-3">
                        $ {{ master.min_join_equity ? master.min_join_equity : '0.00' }}
                    </td>
                    <td class="p-3">
                        {{ master.sharing_profit }}
                    </td>
                    <td class="p-3">
                        $ {{ master.management_fee ?  master.management_fee : '0.00'}}
                    </td>
                    <td class="p-3">
                        <Badge
                            :variant="transactionVariant(master.status)"
                            class="ml-0"
                        >
                            {{ master.status }}
                        </Badge>
                    </td>
                    <td class="p-3">
                        <Action
                        :masters="master"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>