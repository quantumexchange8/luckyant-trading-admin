<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {computed, ref, watch, watchEffect} from "vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import Action from "@/Pages/Member/Partials/Action.vue";
import KycAction from "@/Pages/Member/Partials/KycAction.vue";
import Badge from "@/Components/Badge.vue";
import Button from "@/Components/Button.vue";
import Tooltip from "@/Components/Tooltip.vue";
import Modal from "@/Components/Modal.vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import {CheckIcon, ChevronDownIcon, SortAscendingIcon } from '@heroicons/vue/solid'

const props = defineProps({
    search: String,
    date: String,
    rank: Number,
    refresh: Boolean,
    isLoading: Boolean,
    kycStatus: String,
    exportStatus: Boolean,
    countries: Array,
    nationalities: Array,
    leader: Object,
})
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const members = ref({data: []});
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const isLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const { formatDateTime, formatAmount } = transactionFormat();
const memberMT5Modal = ref(false);
const mt5Details = ref(null);

const sortDescending = ref('desc');
const types = ref('')

const toggleSort = (sortType) => {
    sortDescending.value = sortDescending.value === 'desc' ? 'asc' : 'desc';
    types.value = sortType;
}

watch(
    [() => props.search, () => props.rank, () => props.date, () => props.kycStatus, () => types.value, () => sortDescending.value, () => props.leader],
    debounce(([searchValue, rankValue, dateValue, typeValue, sortValue, leaderValue]) => {
        getResults(1, searchValue, rankValue, dateValue, typeValue, sortValue, leaderValue);
    }, 300)
);

const getResults = async (page = 1, search = props.search , rank = props.rank, date = props.date, type = props.kycStatus, sortType = types.value, sort = sortDescending.value, leader = props.leader) => {
    isLoading.value = true
    try {
        let url = `/member/getMemberDetails?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (rank) {
            url += `&rank=${rank}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (sortType) {
            url += `&sortType=${sortType}`;
            url += `&sort=${sort}`;
        }

        if (leader) {
            url += `&leader=${leader.value}`;
        }

        const response = await axios.get(url);
        members.value = response.data;
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

        getResults(currentPage.value, props.search, props.rank, props.date, props.kycStatus, types.value, sortDescending.value, props.leader);
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
    if(newVal) {

        let url = `/member/getMemberDetails?exportStatus=yes`;

            if (props.date) {
                url += `&date=${props.date}`;
            }

            if (props.search) {
                url += `&search=${props.search}`;
            }

            if (props.rank) {
                url += `&rank=${props.rank}`;
            }

            if (props.leader) {
            url += `&leader=${props.leader.value}`;
        }

            window.location.href = url;
            emit('update:export', false);
    }
})

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-primary-500 dark:text-white'
];

const kycVariant = (kycApprovalStatus) => {
    if (kycApprovalStatus === 'Pending') return 'processing';
    if (kycApprovalStatus === 'Verified') return 'success';
    if (kycApprovalStatus === 'Unverified') return 'warning';
}

const openMT5Modal = (member) => {
    memberMT5Modal.value = true;
    mt5Details.value = member;
}


const closeModal = () => {
    memberMT5Modal.value = false
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
                    <th scope="col" colspan="4" class="px-3 py-2.5">
                        <div class="flex gap-2">
                            <div>
                                <span>Name</span>
                            </div>
                            <div
                                class="transition-transform transform"
                                :class="{ 'rotate-180': sortDescending === 'asc' && types === 'name' }"
                                @click="toggleSort('name')"
                            >
                                <SortAscendingIcon class="w-5 h-5 hover:cursor-pointer" />
                            </div>
                        </div>
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-56">
                        <div class="flex gap-2 justify-center">
                            <div>
                                <span>Joining Date</span>
                            </div>
                            <div
                                class="transition-transform transform"
                                :class="{ 'rotate-180': sortDescending === 'asc' && types === 'created_at' }"
                                @click="toggleSort('created_at')"
                            >
                                <SortAscendingIcon class="w-5 h-5 hover:cursor-pointer" />
                            </div>
                        </div>
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-40">
                        MT5 Account
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-40">
                        Top Leader
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 w-40">
                        First Leader
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-56">
                        Wallet Balance
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-24">
                        <div class="flex gap-2 justify-center">
                            <div>
                                <span>Country</span>
                            </div>
                            <div
                                class="transition-transform transform"
                                :class="{ 'rotate-180': sortDescending === 'asc' && types === 'country' }"
                                @click="toggleSort('country')"
                            >
                                <SortAscendingIcon class="w-5 h-5 hover:cursor-pointer" />
                            </div>
                        </div>
                    </th>
                    <th scope="col" colspan="1" class="px-3 py-2.5 text-center w-24">
                        Rank
                    </th>
                    <th v-if="kycStatus !== 'Pending'" scope="col" colspan="1"  class="px-3 py-2.5 text-center w-24">
                        Status
                    </th>
                    <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-36">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="member in members.data"
                    class="bg-white dark:bg-transparent text-xs font-normal text-gray-900 dark:text-white border-b dark:border-gray-800"
                >
                    <td class="pl-3 py-2.5" colspan="4">
                        <div class="inline-flex items-center gap-2 mr-3">
                            <img :src="member.profile_photo_url ? member.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-8 h-8 rounded-full" alt="">
                            <div class="flex flex-col">
                                <div>
                                    {{ member.name }}
                                </div>
                                <div class="dark:text-gray-400">
                                    {{ member.email }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        {{ formatDateTime(member.created_at, false) }}
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        <div v-if="member.trading_accounts.length > 0">
                            <Tooltip content="View MT5 account" placement="bottom">
                                <Button
                                    type="button"
                                    variant="gray"
                                    size="sm"
                                    @click="openMT5Modal(member)"
                                    class="flex justify-center"
                                >
                                    <span class="text-xs">View</span>
                                </Button>
                            </Tooltip>
                        </div>
                        <div v-else>
                            -
                        </div>
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        {{ member.top_leader ? member.top_leader.name : 'LuckyAnt Trading' }} <!--top leader-->
                    </td>
                    <td class="px-3 py-2.5" colspan="2">
                        <div class="flex flex-col">
                            <span>{{ member.first_leader ? member.first_leader.name : 'LuckyAnt Trading' }} <!--first leader--></span>
                            <span>{{ member.first_leader ? member.first_leader.email : 'LuckyAntTrading@LuckyAntTrading.com' }}</span>
                        </div>
                        
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        $ {{ formatAmount(member.walletBalance) }}
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        {{ member.country.name }}
                    </td>
                    <td class="px-3 py-2.5 text-center uppercase" colspan="1">
                        {{ member.rank.name }}
                    </td>
                    <td v-if="kycStatus !== 'Pending'" class="px-3 py-2.5 text-center" colspan="1">
                        <Badge
                            :variant="kycVariant(member.kyc_approval)"
                        >
                            {{ member.kyc_approval }}
                        </Badge>
                    </td>
                    <td class="px-3 py-2.5 text-center" colspan="2">
                        <Action
                            v-if="kycStatus !== 'Pending' && kycStatus !== 'Unverified'"
                            :members="member"
                            type="member"
                        />
                        <KycAction
                            v-else
                            :member="member"
                            :countries="countries"
                            :nationalities="nationalities"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!isLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="members"
                :limit=2
                @pagination-change-page="handlePageChange"
            />
        </div>
        <div v-if="members.data.length === 0 && !isLoading" class="flex flex-col justify-center items-center gap-2">
            <div class="text-xl dark:text-gray-400">
                No Member
            </div>
        </div>
    </div>

    <Modal :show="memberMT5Modal" title="MT5 Account Listing" @close="closeModal" max-width="xl">
    <div v-if="mt5Details.trading_accounts == ''" class="py-4 text-lg text-center">
        No MT5 account
    </div>
    <div v-for="mt5Detail in mt5Details.trading_accounts" class="mb-2">
        <Disclosure v-slot="{ open }">
            <DisclosureButton
                class="flex w-full justify-between items-center rounded-lg bg-primary-500 px-4 py-2 text-left text-sm font-medium text-white hover:bg-primary-600 focus:outline-none focus-visible:ring focus-visible:ring-purple-500/75"
            >
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Trading Account No: </span>
                <span class="col-span-2 text-white dark:text-white py-2">{{ mt5Detail.meta_login }}</span>
            </div>
            <ChevronDownIcon
                :class="open ? 'rotate-180 transform' : ''"
                class="h-5 w-5 text-white"
            />
            </DisclosureButton>
            <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Name</span>
                    <span class="col-span-2 text-black dark:text-white py-2">{{ mt5Details.name }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Equity</span>
                    <span class="col-span-2 text-black dark:text-white py-2">$ {{ mt5Detail.equity }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Margin Leverage</span>
                    <span class="col-span-2 text-black dark:text-white py-2">{{ mt5Detail.margin_leverage }}</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-2">
                    <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Balance</span>
                    <span class="col-span-2 text-black dark:text-white py-2">$ {{ mt5Detail.balance }}</span>
                </div>
            </DisclosurePanel>
        </Disclosure>
    </div>
    </Modal>
</template>
