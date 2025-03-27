<script setup>
import NoData from "@/Components/NoData.vue";
import {ref, watch, watchEffect} from "vue";
import debounce from "lodash/debounce.js";
import DatePicker from "primevue/datepicker";
import {SearchLgIcon, XIcon} from "@/Components/Icons/outline.jsx";
import {IconCircleXFilled, IconScanEye, IconUsers} from "@tabler/icons-vue";
import InputText from "primevue/inputtext";
import Skeleton from "primevue/skeleton";
import Card from "primevue/card";
import Paginator from "primevue/paginator";
import ApplicationListAction from "@/Pages/Application/Listing/ApplicationListAction.vue";
import AttendanceListing from "@/Pages/Application/Listing/Partials/AttendanceListing.vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    applicationsCount: Number,
});

const isLoading = ref(false);
const applications = ref([]);
const currentPage = ref(1);
const rowsPerPage = ref(6);
const totalRecords = ref(0);
const search = ref('');
const status = ref();
const selectedDate = ref([]);

const getResults = async (page = 1, rowsPerPage = 6) => {
    isLoading.value = true;

    try {
        let url = `/application/getApplicationData?page=${page}&limit=${rowsPerPage}`;

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
        applications.value = response.data.applications;
        totalRecords.value = response.data.totalRecords;
        currentPage.value = response.data.currentPage;
    } catch (error) {
        console.error('Error getting applications:', error);
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

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults(currentPage.value, rowsPerPage.value);
    }
});
</script>

<template>
    <div class="flex flex-col items-center gap-5">
        <!-- Filter -->
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
            <div class="relative w-full md:w-auto">
                <DatePicker
                    v-model="selectedDate"
                    dateFormat="dd/mm/yy"
                    class="min-w-60 w-full md:w-auto"
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

        <!-- No Data -->
        <div
            v-if="applicationsCount === 0"
            class="flex justify-center items-center w-full"
        >
            <NoData />
        </div>

        <!-- Content -->
        <div v-else class="w-full">
            <div v-if="isLoading">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch">
                    <Card
                        v-for="(application, index) in applicationsCount > 6 ? 6 : applicationsCount"
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

            <div v-else-if="!applications.length">
                <NoData />
            </div>

            <div v-else>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch">
                    <Card
                        v-for="(application, index) in applications"
                        :key="index"
                    >
                        <template #content>
                            <div class="flex flex-col gap-5 items-center self-stretch">
                                <div class="flex flex-col gap-1 items-start self-stretch">
                                    <div class="flex gap-5 items-center self-stretch">
                                        <div class="w-full text-gray-950 dark:text-white font-bold text-wrap">
                                            {{ application.title }}
                                        </div>
                                        <ApplicationListAction
                                            :application="application"
                                        />
                                    </div>
                                </div>

                                <div class="text-sm dark:text-white line-clamp-4 w-full prose dark:prose-invert h-20" v-html="application.content"></div>

                                <div class="flex items-end justify-between self-stretch">
                                    <div class="flex flex-col items-center gap-1 self-stretch w-full">
                                        <AttendanceListing
                                            :application="application"
                                        />
                                        <div class="py-1 flex items-center gap-3 self-stretch">
                                            <div class="min-w-5 text-gray-500">
                                                <IconScanEye size="20" stroke-width="1.25" />
                                            </div>
                                            <div class="text-gray-950 dark:text-white text-sm font-medium truncate">
                                                <span v-if="application.leaders_names === 'everyone'">{{ $t('public.everyone') }}</span>
                                                <span v-else>{{ application.leaders_names || '-' }}</span>
                                            </div>
                                        </div>
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
