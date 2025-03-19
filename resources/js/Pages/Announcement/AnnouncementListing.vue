<script setup>
import NoData from "@/Components/NoData.vue";
import {ref, watch} from "vue";
import Card from "primevue/card";
import {IconCircleXFilled} from "@tabler/icons-vue";
import {SearchLgIcon, XIcon} from "@/Components/Icons/outline.jsx";
import InputText from "primevue/inputtext";
import DatePicker from "primevue/datepicker";
import dayjs from "dayjs";
import Paginator from "primevue/paginator";
import AnnouncementListingAction from "@/Pages/Announcement/AnnouncementListingAction.vue";
import Skeleton from "primevue/skeleton";
import debounce from "lodash/debounce.js";

defineProps({
    announcementsCount: Number,
});

const isLoading = ref(false);
const announcements = ref([]);
const currentPage = ref(1);
const rowsPerPage = ref(6);
const totalRecords = ref(0);
const search = ref('');
const status = ref();
const selectedDate = ref([]);

const getResults = async (page = 1, rowsPerPage = 6) => {
    isLoading.value = true;

    try {
        let url = `/announcement/getAnnouncement?page=${page}&limit=${rowsPerPage}`;

        if (search.value) {
            url += `&search=${search.value}`;
        }

        if (status.value) {
            url += `&status=${status.value}`;
        }

        if (
            selectedDate.value &&
            Array.isArray(selectedDate.value) &&
            typeof selectedDate.value[0] !== 'undefined' &&
            typeof selectedDate.value[1] !== 'undefined'
        ) {
            const startDateISO = new Date(selectedDate.value[0]).toISOString();
            const endDateISO = new Date(selectedDate.value[1]).toISOString();
            console.log(selectedDate.value);
            url += `&start_date=${startDateISO}&end_date=${endDateISO}`;
        }

        const response = await axios.get(url);
        announcements.value = response.data.announcements;
        totalRecords.value = response.data.totalRecords;
        currentPage.value = response.data.currentPage;
    } catch (error) {
        console.error('Error getting announcements:', error);
    } finally {
        isLoading.value = false;
    }
};

// Initial call to populate data
getResults(currentPage.value, rowsPerPage.value);

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
    getResults(currentPage.value, rowsPerPage.value);
};

const clearJoinDate = () => {
    selectedDate.value = [];
}

watch(search, debounce(() => {
    getResults(currentPage.value, rowsPerPage.value);
}, 300));

watch(selectedDate, (newDateRange) => {
    if (
        Array.isArray(newDateRange) &&
        newDateRange.length === 2 &&
        newDateRange[0] &&
        newDateRange[1]
    ) {
        getResults(currentPage.value, rowsPerPage.value);
    } else if (newDateRange.length === 0) {
        getResults(currentPage.value, rowsPerPage.value);
    }
})

const clearSearch = () => {
    search.value = '';
}
</script>

<template>
    <div class="flex flex-col items-center gap-5">
        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch">
            <div class="relative w-full md:w-60">
                <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                    <SearchLgIcon class="w-5 h-5" />
                </div>
                <InputText v-model="search" :placeholder="$t('public.search')" class="font-normal pl-12 w-full md:w-60" />
                <div
                    v-if="search"
                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                    @click="clearSearch"
                >
                    <IconCircleXFilled size="16" />
                </div>
            </div>
            <div class="relative">
                <DatePicker
                    v-model="selectedDate"
                    dateFormat="dd/mm/yy"
                    class="min-w-60"
                    selectionMode="range"
                    placeholder="Filter published date"
                />
                <div
                    v-if="selectedDate && selectedDate.length > 0"
                    class="absolute top-2/4 -mt-2 right-2 text-gray-400 hover:text-gray-500 dark:text-gray-400 select-none cursor-pointer bg-transparent"
                    @click="clearJoinDate"
                >
                    <XIcon class="w-4 h-4" />
                </div>
            </div>
        </div>

        <div
            v-if="announcementsCount === 0"
            class="flex justify-center items-center w-full"
        >
            <NoData />
        </div>

        <div v-else class="w-full">
            <div v-if="isLoading">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch">
                    <Card
                        v-for="(announcement, index) in announcementsCount > 6 ? 6 : announcementsCount"
                        :key="index"
                    >
                        <template #content>
                            <div class="flex flex-col gap-5 items-center self-stretch">
                                <div class="flex flex-col gap-1 items-start self-stretch">
                                    <div class="flex gap-5 items-center self-stretch">
                                        <div class="w-full text-gray-950 dark:text-white font-bold text-wrap">
                                            <Skeleton width="10rem" height="1.5rem" class="mb-3"></Skeleton>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        <Skeleton width="8rem" height="1rem" class="mb-0.5"></Skeleton>
                                    </div>
                                </div>

                                <div class="text-sm dark:text-white w-full">
                                    <Skeleton height="1rem" class="w-full"></Skeleton>
                                    <Skeleton height="1rem" class="w-full my-1"></Skeleton>
                                    <Skeleton height="1rem" class="w-full my-1"></Skeleton>
                                    <Skeleton height="1rem" class="w-full mb-1"></Skeleton>
                                </div>

                                <div class="flex items-center gap-2 self-stretch">
                                    <div class="text-sm text-gray-500">
                                        {{ $t('public.to') }}:
                                    </div>
                                    <div class="text-gray-950 dark:text-white text-sm font-medium truncate">
                                        <Skeleton width="8rem" class="my-1"></Skeleton>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div v-else-if="!announcements.length">
                <NoData />
            </div>

            <div v-else>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch">
                    <Card
                        v-for="(announcement, index) in announcements"
                        :key="index"
                    >
                        <template #content>
                            <div class="flex flex-col gap-5 items-center self-stretch">
                                <div class="flex flex-col gap-1 items-start self-stretch">
                                    <div class="flex gap-5 items-center self-stretch">
                                        <div class="w-full text-gray-950 dark:text-white font-bold text-wrap">
                                            {{ announcement.subject }}
                                        </div>
                                        <AnnouncementListingAction
                                            :announcement="announcement"
                                        />
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $t('public.published_at') }}: {{ dayjs(announcement.created_at).format('YYYY-MM-DD HH:mm:ss') }}
                                    </div>
                                </div>

                                <div class="text-sm dark:text-white line-clamp-4 w-full prose dark:prose-invert" v-html="announcement.details"></div>

                                <div class="flex items-center gap-2 self-stretch">
                                    <div class="text-sm text-gray-500">
                                        {{ $t('public.to') }}:
                                    </div>
                                    <div class="text-gray-950 dark:text-white text-sm font-medium truncate">
                                        <span v-if="announcement.leaders_names === 'everyone'">{{ $t('public.everyone') }}</span>
                                        <span v-else>{{ announcement.leaders_names || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <Paginator
                    :first="(currentPage - 1) * rowsPerPage"
                    :rows="rowsPerPage"
                    :totalRecords="totalRecords"
                    @page="onPageChange"
                />
            </div>
        </div>
    </div>
</template>
