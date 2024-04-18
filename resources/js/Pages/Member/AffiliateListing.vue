<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {CloudDownloadIcon} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
import {ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Loading from "@/Components/Loading.vue";
import Badge from "@/Components/Badge.vue";
import Combobox from "@/Components/Combobox.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const isLoading = ref(false);
const date = ref('');
const search = ref('');
const firstLeader = ref(null);
const leaderDetail = ref(null);
const refresh = ref(false);
const affiliateSummaries = ref({data: []})
const currentPage = ref(1)
const { formatDateTime, formatAmount } = transactionFormat();

function loadUsers(query, setOptions) {
    fetch('/member/getAllLeaders?query=' + query)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(user => {
                    return {
                        value: user.id,
                        label: user.name,
                        img: user.profile_photo
                    }
                })
            )
        });
}

const getResults = async (page = 1, firstLeader = null, search = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/member/getAffiliateSummaries?page=${page}`;

        if (firstLeader) {
            url += `&firstLeader=${firstLeader.value}`;
        }

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        affiliateSummaries.value = response.data.children;
        leaderDetail.value = response.data.first_leader;

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

watch(
    [firstLeader, search, date],
    debounce(([firstLeaderValue, searchValue, dateValue]) => {
        if (firstLeaderValue) {
            getResults(1, firstLeaderValue, searchValue, dateValue);
        }
    }, 300)
);

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;
        getResults(currentPage.value, firstLeader.value, search.value, date.value);
    }
};

// const refreshHistory = () => {
//     const typeStrings = type.value ? type.value.map(item => item.value) : null;
//
//     getResults(1, typeStrings, date.value, tradeType.value);
//
//     toast.add({
//         message: wTrans('public.successfully_refreshed'),
//     });
// }

const exportSummary = () => {
    if(leaderDetail.value) {

        let url = `/member/getAffiliateSummaries?exportStatus=yes`;

        if (firstLeader) {
            url += `&firstLeader=${firstLeader.value.value}`;
        }

        if (search.value) {
            url += `&search=${search.value}`;
        }

        if (date.value) {
            url += `&date=${date.value}`;
        }

        window.location.href = url;
    }
}

const kycVariant = (kycApprovalStatus) => {
    if (kycApprovalStatus === 'Pending') return 'processing';
    if (kycApprovalStatus === 'Verified') return 'success';
    if (kycApprovalStatus === 'Unverified') return 'warning';
}

const paginationClass = [
    'bg-transparent border-0 text-gray-600 dark:text-gray-400 dark:enabled:hover:text-white'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-primary-500 dark:text-primary-300'
];
</script>

<template>
    <AuthenticatedLayout title="Affiliate Listing">
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Affiliate Listing
                    </h2>
                    <p class="text-base font-normal dark:text-gray-400">
                        Track all transaction history carried out by different groups.
                    </p>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-start gap-8">
            <div class="flex justify-between items-start self-stretch">
                <div class="flex flex-col md:flex-row items-center gap-4 w-2/3">
                    <div class="w-full">
                        <Combobox
                            :load-options="loadUsers"
                            v-model="firstLeader"
                            placeholder="Leader"
                            image
                        />
                    </div>
                    <div class="w-full">
                        <InputIconWrapper class="w-full">
                            <template #icon>
                                <SearchIcon aria-hidden="true" class="w-5 h-5" />
                            </template>
                            <Input
                                withIcon
                                id="search"
                                type="text"
                                class="block w-full"
                                placeholder="Search"
                                v-model="search"
                                :disabled="leaderDetail === null"
                            />
                        </InputIconWrapper>
                    </div>
                    <div class="w-full">
                        <vue-tailwind-datepicker
                            placeholder="Select dates"
                            :formatter="formatter"
                            separator=" - "
                            v-model="date"
                            input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                            :disabled="leaderDetail === null"
                        />
                    </div>
                </div>
                <div class="flex justify-end items-center gap-5">
                    <Button
                        type="button"
                        variant="secondary"
                    >
                        <span class="text-lg">Clear</span>
                    </Button>
                    <Button
                        type="button"
                        variant="gray"
                        class="flex gap-1 justify-center"
                        v-slot="{ iconSizeClasses }"
                        @click="exportSummary"
                        :disabled="leaderDetail === null"
                    >
                        <CloudDownloadIcon class="w-5 h-5" />
                        Export
                    </Button>
                </div>
            </div>

            <div v-if="leaderDetail" class="flex flex-col sm:flex-row gap-5 w-full">
                <div class="p-5 w-full bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="flex flex-col gap-5">
                        <Badge
                            :variant="kycVariant(leaderDetail.kyc_approval)"
                        >
                            {{ leaderDetail.kyc_approval }}
                        </Badge>
                        <div class="flex justify-between">
                            <div class="flex items-center gap-2">
                                <img :src="leaderDetail.profile_photo_url ? leaderDetail.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-12 h-12 rounded-full" alt="">
                                <div class="flex flex-col">
                                    <div>
                                        {{ leaderDetail.name }}
                                    </div>
                                    <div>
                                        {{ leaderDetail.email }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <Badge variant="primary" width="auto">{{ leaderDetail.rank.name }}</Badge>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-5 w-full bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 w-full">
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.profit_in') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.profit ? leaderDetail.profit : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.bonus_wallet_in') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.bonus_in ? leaderDetail.bonus_in : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.bonus_wallet_out') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.bonus_out ? leaderDetail.bonus_out : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.e_wallet_in') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.e_wallet_in ? leaderDetail.e_wallet_in : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.e_wallet_out') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.e_wallet_out ? leaderDetail.e_wallet_out : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.total_funding') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.total_funding ? leaderDetail.total_funding : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.total_withdrawal') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.total_withdrawal ? leaderDetail.total_withdrawal : 0) }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.total_demo_fund') }}
                            </div>
                            <div class="flex justify-center font-semibold">
                                $ {{ formatAmount(leaderDetail.total_demo_fund ? leaderDetail.total_demo_fund : 0) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-gray-600 dark:text-gray-400">
                Search for a leader
            </div>

            <div class="p-5 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900 w-full">
                <div class="relative overflow-x-auto">
                    <div v-if="isLoading" class="w-full flex justify-center my-8">
                        <Loading />
                    </div>
                    <table v-else class="w-[650px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                        <tr>
                            <th scope="col" class="p-3">
                                {{ $t('public.affiliate') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.profit_in') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.bonus_wallet_in') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.bonus_wallet_out') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.e_wallet_in') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.e_wallet_out') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.total_funding') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.total_withdrawal') }}
                            </th>
                            <th scope="col" class="p-3 text-center">
                                {{ $t('public.total_demo_fund') }}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="affiliateSummaries.data.length === 0">
                            <th colspan="10" class="py-4 text-lg text-center">
                                No data
                            </th>
                        </tr>
                        <tr
                            v-for="summary in affiliateSummaries.data"
                            class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:bg-primary-50 dark:hover:bg-gray-600"
                        >
                            <td class="p-3">
                                <div class="inline-flex gap-2">
                                    <div>
                                        <img :src="summary.profile_photo_url ? summary.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                                    </div>
                                    <div class="flex flex-col">
                                        <span>{{ summary.name }}</span>
                                        <span>{{ summary.email }}</span>
                                    </div>

                                </div>
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.profit ? summary.profit : 0) }}
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.bonus_in ? summary.bonus_in : 0) }}
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.bonus_out ? summary.bonus_out : 0) }}
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.e_wallet_in ? summary.e_wallet_in : 0) }}
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.e_wallet_out ? summary.e_wallet_out : 0) }}
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.total_funding ? summary.total_funding : 0) }}
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.total_withdrawal ? summary.total_withdrawal : 0) }}
                            </td>
                            <td class="p-3 font-semibold text-center">
                                $ {{ formatAmount(summary.total_demo_fund ? summary.total_demo_fund : 0) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-center mt-4" v-if="!isLoading">
                    <TailwindPagination
                        :item-classes=paginationClass
                        :active-classes=paginationActiveClass
                        :data="affiliateSummaries"
                        :limit=2
                        @pagination-change-page="handlePageChange"
                    >
                        <template #prev-nav>
                            <span class="flex gap-2"><ArrowLeftIcon class="w-5 h-5" /> <span class="hidden sm:flex">{{$t('public.previous')}}</span></span>
                        </template>
                        <template #next-nav>
                            <span class="flex gap-2"><span class="hidden sm:flex">{{$t('public.next')}}</span> <ArrowRightIcon class="w-5 h-5" /></span>
                        </template>
                    </TailwindPagination>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
