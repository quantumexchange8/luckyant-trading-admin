<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {computed, ref, watch, watchEffect} from "vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import Action from "@/Pages/Admin/Partials/Action.vue";

const props = defineProps({
    search: String,
    rank: String,
    refresh: Boolean,
    isLoading: Boolean,
    kycStatus: String,
    exportStatus: Boolean,
})

const admins = ref({data: []});
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const isLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh']);
const { formatDateTime, formatAmount } = transactionFormat();

watch(
    [() => props.search, () => props.rank],
    debounce(([searchValue, rankValue, dateValue]) => {
        getResults(1, searchValue, rankValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = props.search , rank = props.rank) => {
    isLoading.value = true
    try {
        let url = `/admin/getAdminDetails?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        // if (type) {
        //     url += `&type=${type}`;
        // }

        if (rank) {
            url += `&rank=${rank}`;
        }

        // if (date) {
        //     url += `&date=${date}`;
        // }
        
        // if (type) {
        //     url += `&type=${type}`;
        //     url += `&sort=${sort}`;
        // }

        const response = await axios.get(url);
        admins.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
        emit('update:loading', false);
    }
}

getResults()

const handlePageChange = (newPage) => {
    if (newPage >= 1) {

        currentPage.value = newPage;

        getResults(currentPage.value, props.search, props.rank, props.kycStatus);
    }
};

</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="isLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" colspan="4" class="px-3 py-2.5">
                        <span>Name</span>
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-40">
                        Join Date
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-56">
                       Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                v-for="admin in admins.data"
                class="bg-white dark:bg-transparent text-xs font-normal text-gray-900 dark:text-white border-b dark:border-gray-800"
                >
                    <td class="pl-3 py-2.5" colspan="4">
                        <div class="inline-flex items-center gap-2 mr-3">
                            <img :src="admin.profile_photo_url ? admin.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                            <div class="flex flex-col">
                                <div>
                                    {{ admin.name }}
                                </div>
                                <div class="dark:text-gray-400">
                                    {{ admin.email }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        {{ formatDateTime(admin.created_at, false) }}
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        <Action
                            v-if="kycStatus !== 'Pending'"
                            :admins="admin"
                            type="member"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>