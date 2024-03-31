<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import { CloudDownloadIcon, SearchIcon } from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {onMounted, ref} from "vue";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import MemberTable from "@/Pages/Member/MemberTable.vue";
import toast from "@/Composables/toast.js";
import Action from "@/Pages/Member/Partials/Action.vue";
import TanstackTable from "@/Components/TanstackTable.vue";

const search = ref('');
const date = ref('');
const type = ref('');
const rank = ref();
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
    countries: Array,
    nationalities: Array,
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

const exportMember = () => {
    exportStatus.value = true;
}
</script>

<template>
    <AuthenticatedLayout title="Member Listing">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight">
                        Member Listing
                    </h2>
                </div>
                <div class="flex flex-row gap-3">
                    <div>
                        <Action
                            type="add_member"
                            :rankLists="rankLists"
                            :countries="countries"
                        />
                    </div>
                </div>
            </div>


        </template>

        <div class="pt-3 md:flex md:justify-end items-center">
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

        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900 my-8">
            <div class="w-full">
                <TabGroup :selectedIndex="selectedTab" @change="changeTab">
                    <TabList class="flex py-1 w-full flex-col gap-3 sm:flex-row sm:justify-between">
                        <div class="w-full">
                            <Tab
                                v-for="kycStatus in kycStatuses"
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    @click="updateKycStatus(kycStatus.value)"
                                    class="w-full sm:w-36"
                                    :class="[
                                    'py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                       selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                                >
                                    {{ kycStatus.name }} <span v-if="kycStatus.name !== 'All'">({{ kycStatus.count }})</span>
                                </button>
                            </Tab>
                        </div>
                        <div>
                            <Button
                                type="button"
                                variant="gray"
                                class="w-full flex gap-1 justify-center"
                                size="sm"
                                v-slot="{ iconSizeClasses }"
                                @click="exportMember"
                            >
                                <CloudDownloadIcon class="w-5 h-5" />
                                Export
                            </Button>
                        </div>
                    </TabList>

                    <TabPanels>
<!--                        <TabPanel-->
<!--                            v-for="kycStatus in kycStatuses"-->
<!--                        >-->
<!--                            <MemberTable-->
<!--                                :refresh="refresh"-->
<!--                                :isLoading="isLoading"-->
<!--                                :search="search"-->
<!--                                :date="date"-->
<!--                                :rank="rank"-->
<!--                                :kycStatus=kycStatus.value-->
<!--                                :exportStatus="exportStatus"-->
<!--                                :countries="countries"-->
<!--                                :nationalities="nationalities"-->
<!--                                @update:loading="isLoading = $event"-->
<!--                                @update:refresh="refresh = $event"-->
<!--                                @update:export="exportStatus = $event"-->
<!--                            />-->
<!--                        </TabPanel>-->
                        <TabPanel>
                            <TanstackTable />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
