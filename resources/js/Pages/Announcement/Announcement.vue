<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import {usePage} from "@inertiajs/vue3";
import AddAnnouncement from "@/Pages/Announcement/Partials/AddAnnouncement.vue";
import Action from "@/Pages/Announcement/Partials/Action.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {ArrowLeftIcon, ArrowRightIcon, SearchIcon} from "@heroicons/vue/outline";
import {MemberDetailIcon} from "@/Components/Icons/outline.jsx";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Loading from "@/Components/Loading.vue";
import {ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import debounce from "lodash/debounce.js";
import {TailwindPagination} from "laravel-vue-pagination";
import { Switch } from '@headlessui/vue'

const announcements = ref({data: []});
const isLoading = ref(false);
const search = ref('');
const date = ref('');
const announcementModal = ref(false);
const { formatDateTime } = transactionFormat();
const announcementDetail = ref();
const currentPage = ref(1);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

watch(
    [search, date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/announcement/getAnnouncement?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        announcements.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults()

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;
        const dateRange = date.value.split(' - ');
        getResults(currentPage.value,  search.value, dateRange);
    }
};

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-[#FF9E23] dark:text-white'
];

const openAnnouncementModal = (announcement) => {
    announcementModal.value = true
    announcementDetail.value = announcement
}

const closeModal = () => {
    announcementModal.value = false
}

const enabled = ref(false);

const toggleStatus = (announcement) => {
    announcement.status = announcement.status === 'Active' ? 'Inactive' : 'Active';
    updateStatus(announcement.id, announcement.status);
}

const updateStatus = async (announcementId, newStatus) => {
    try {
        const response = await axios.post('/announcement/updateStatus', {
            id: announcementId,
            status: newStatus
        });

    } catch (error) {
        console.error('Error updating status:', error);
    }
};
</script>

<template>
    <AuthenticatedLayout title="Announcement">
        <template #header>
            <div class="flex justify-between">
                <h2
                    class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
                >
                    Announcement
                </h2>
                <div class="flex justify-end">
                    <AddAnnouncement />
                </div>
            </div>
        </template>

        <div class="w-full p-5 rounded-xl shadow-md bg-white dark:bg-gray-900">
            <h4 class="font-semibold dark:text-white">
                History
            </h4>
            <form>
                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="w-full">
                        <InputIconWrapper class="md:col-span-2">
                            <template #icon>
                                <SearchIcon aria-hidden="true" class="w-5 h-5" />
                            </template>
                            <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
                        </InputIconWrapper>
                    </div>
                    <div class="md:w-2/3">
                        <vue-tailwind-datepicker
                            placeholder="Select dates"
                            :formatter="formatter"
                            separator=" - "
                            v-model="date"
                            input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"                        />
                    </div>
                </div>
            </form>
            <div class="relative overflow-x-auto sm:rounded-lg">
                <div v-if="isLoading" class="w-full flex justify-center my-8">
                    <Loading />
                </div>
                <table v-else class="w-[650px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
                    <thead class="text-xs font-medium text-gray-700 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                        <tr>
                            <th scope="col" class="p-3">
                                Date
                            </th>
                            <th scope="col" class="p-3">
                                Subject
                            </th>
                            <th scope="col" class="p-3">
                                Type
                            </th>
                            <th scope="col" class="p-3">
                                Status
                            </th>
                            <th scope="col" class="p-3 text-center">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="announcements.data.length === 0">
                            <th colspan="5" class="py-4 text-lg text-center">
                                No History
                            </th>
                        </tr>
                        <tr
                            v-for="announcement in announcements.data"
                            class="bg-white dark:bg-transparent text-xs font-normal text-gray-900 dark:text-white border-b dark:border-gray-800 dark:hover:bg-gray-800"
                        >
                            <td class="p-3">
                                {{ formatDateTime(announcement.created_at) }}
                            </td>
                            <td class="p-3">
                                {{ announcement.subject }}
                            </td>
                            <td class="p-3">
                                {{ announcement.type }}
                            </td>
                            <td class="p-3">
                                <Switch
                                    :modelValue="enabled.value && announcement.status === 'Active'"
                                    :class="announcement.status === 'Active' ? 'bg-success-500' : 'bg-gray-300'"
                                    class="relative inline-flex h-[24px] w-[52px] shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75"
                                    @click="toggleStatus(announcement)"
                                    >
                                    <span class="sr-only">Use setting</span>
                                    <span
                                        aria-hidden="true"
                                        :class="announcement.status === 'Active' ? 'translate-x-7' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-[20px] w-[20px] transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                    />
                                </Switch>
                            </td>
                            <td class="p-3 flex justify-center">
                                <Action
                                    :announcement="announcement"
                                />

                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex justify-center mt-4" v-if="!isLoading">
                    <TailwindPagination
                        :item-classes=paginationClass
                        :active-classes=paginationActiveClass
                        :data="announcements"
                        :limit=2
                        @pagination-change-page="handlePageChange"
                    />
                </div>
            </div>
        </div>

        <Modal :show="announcementModal" title="Details" @close="closeModal">
            <div class="text-xs dark:text-gray-400">{{ formatDateTime(announcementDetail.created_at) }}</div>
            <div class="my-5" v-if="announcementDetail.image">
                <img class="rounded-lg w-full" :src="announcementDetail.image" alt="announcement image" />
            </div>
            <div class="my-5 dark:text-white">{{ announcementDetail.subject }}</div>
            <div class="dark:text-gray-300 text-sm prose leading-5" v-html="announcementDetail.details"></div>
        </Modal>
    </AuthenticatedLayout>

</template>
