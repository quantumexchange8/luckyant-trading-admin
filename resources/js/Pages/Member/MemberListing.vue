<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import { RefreshIcon, SearchIcon } from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {onMounted, ref} from "vue";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import MemberTable from "@/Pages/Member/MemberTable.vue";

const search = ref('');
const date = ref('');
const type = ref('');
const rank = ref('');
const isLoading = ref(false);
const refresh = ref(false);
const exportStatus = ref(false)
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const props = defineProps({
    rankLists: Array,
    kycCounts: Object,
})

const updateKycStatus = (kyc_status) => {
    type.value = kyc_status
};

const selectedTab = ref(0);
function changeTab(index) {
    selectedTab.value = index;
}

const kycStatuses = [
    { value: '', name: 'All', count: 0 },
    { value: 'Pending', name: 'Pending', count: 0 },
    { value: 'Verified', name: 'Verified', count: 0 },
    { value: 'Unverified', name: 'Unverified', count: 0 },
];

onMounted(() => {
    updateKycCounts();
});

const updateKycCounts = () => {
    for (const status of kycStatuses) {
        status.count = props.kycCounts[status.value] || 0;
    }
}
</script>

<template>
    <AuthenticatedLayout title="Member Listing">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Member Listing
                </h2>
            </div>
        </template>

        <div class="p-6 bg-white rounded-md shadow-md dark:bg-dark-eval-1">
            <div class="flex items-center gap-5">
                <div class="w-full lg:w-[280px]">
                    <InputIconWrapper class="md:col-span-2">
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
                        />
                    </InputIconWrapper>
                </div>
                <div class="w-full md:w-[240px]">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                    />
                </div>
                <div class="w-full md:w-[240px]">
                    <BaseListbox
                        id="rankID"
                        class="w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600"
                        v-model="rank"
                        :options="rankLists"
                        placeholder="Filter rank"
                    />
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-md shadow-md dark:bg-dark-eval-1 my-8">
            <div class="w-full pt-5">
                <TabGroup :selectedIndex="selectedTab" @change="changeTab">
                    <TabList class="max-w-md flex py-1">
                        <Tab
                            v-for="kycStatus in kycStatuses"
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                @click="updateKycStatus(kycStatus.value)"
                                :class="[
                                    'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                    selected ? 'dark:text-white border-b-2' : 'border-b border-gray-400',
                                ]"
                            >
                                {{ kycStatus.name }} <span v-if="kycStatus.name !== 'All'">({{ kycStatus.count }})</span>
                            </button>
                        </Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel
                            v-for="kycStatus in kycStatuses"
                        >
                            <MemberTable
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :rank="rank"
                                :kycStatus=kycStatus.value
                                :exportStatus="exportStatus"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                                @update:export="$emit('update:export', $event)"
                            />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
