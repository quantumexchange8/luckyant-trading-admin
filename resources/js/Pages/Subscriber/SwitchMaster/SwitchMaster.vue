<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { ref } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import PendingSwitchMaster from "@/Pages/Subscriber/SwitchMaster/PendingSwitchMaster.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {CloudDownloadIcon, UsersXIcon, RefreshCw05Icon, UsersCheckIcon} from "@/Components/Icons/outline.jsx";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Combobox from "@/Components/Combobox.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {transactionFormat} from "@/Composables/index.js";
import SwitchMasterHistory from "@/Pages/Subscriber/SwitchMaster/SwitchMasterHistory.vue";
import MemberTable from "@/Pages/Member/MemberTable.vue";

const switchTypes = ref([
    {value: 'Pending', label: 'Pending'},
    {value: '', label: 'History'}
])

const search = ref('');
const leader = ref();
const date = ref('');
const subscriberStatus = ref('');
const status = ref('Pending');
const totalRequest = ref(null);
const totalApprovedRequest = ref(null);
const totalRejectedRequest = ref(null);
const { formatDateTime, formatAmount } = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const handleType = (value) => {
    status.value = value
}

const statusOptions = [
    {value: '', label: 'All' },
    {value: 'Success', label: 'Success' },
    {value: 'Rejected', label: 'Rejected' },
]

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

const clearFilter = () => {
    search.value = '';
    date.value = '';
    leader.value = null;
    subscriberStatus.value = '';
}
</script>

<template>
    <AuthenticatedLayout title="Switch Master">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold leading-tight">
                        Switch Master
                    </h2>
                    <!-- <p class="text-base font-normal dark:text-gray-400">
                        Manage all pending subscribers.
                    </p> -->
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Requests
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalRequest !== null">
                            {{ totalRequest }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                    <RefreshCw05Icon class="text-primary-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Approved Requests
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalApprovedRequest !== null">
                            {{ totalApprovedRequest }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <UsersCheckIcon class="text-success-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        Total Rejected Requests
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalRejectedRequest !== null">
                            {{ totalRejectedRequest }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-error-200">
                    <UsersXIcon class="text-error-500 w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
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
                        />
                    </InputIconWrapper>
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <Combobox
                        :load-options="loadUsers"
                        v-model="leader"
                        placeholder="Leader"
                        image
                    />
                </div>
                <div class="w-full col-span-3 md:col-span-1">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                    />
                </div>
                <div
                    v-if="status !== 'Pending'"
                    class="w-full"
                >
                    <BaseListbox
                        id="statusID"
                        v-model="subscriberStatus"
                        :options="statusOptions"
                        placeholder="Filter Status"
                    />
                </div>
            </div>
            <div class="flex justify-end gap-4 items-center w-full">
                <Button
                    type="button"
                    variant="secondary"
                    @click="clearFilter"
                >
                    <span class="text-lg">Clear</span>
                </Button>
                <Button
                    type="button"
                    variant="gray"
                    class="flex gap-1 justify-center"
                    v-slot="{ iconSizeClasses }"
                >
                    <CloudDownloadIcon class="w-5 h-5" />
                    Export
                </Button>
            </div>
        </div>

        <div class="w-full">
            <TabGroup>
                <TabList class="flex space-x-1 max-w-md rounded-xl bg-gray-200 p-1">
                    <Tab
                        v-for="type in switchTypes"
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                                'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                'ring-white/60 ring-offset-2 ring-offset-primary-400 focus:outline-none focus:ring-2',
                                selected
                                ? 'bg-white text-primary-800 shadow'
                                : 'text-gray-600 hover:bg-white/[0.12] hover:text-primary-500',
                            ]"
                            @click="handleType(type.value)"
                        >
                            {{ type.label }}
                        </button>
                    </Tab>
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        v-for="switchType in switchTypes"
                    >
                        <template v-if="switchType.value === 'Pending'">
                            <PendingSwitchMaster
                                :search="search"
                                :leader="leader"
                                :date="date"
                                :status="status"
                                @update:totalRequest="totalRequest = $event"
                                @update:totalApprovedRequest="totalApprovedRequest = $event"
                                @update:totalRejectedRequest="totalRejectedRequest = $event"
                            />
                        </template>

                        <template v-else>
                            <SwitchMasterHistory
                                :search="search"
                                :leader="leader"
                                :date="date"
                                :status="status"
                                @update:totalRequest="totalRequest = $event"
                                @update:totalApprovedRequest="totalApprovedRequest = $event"
                                @update:totalRejectedRequest="totalRejectedRequest = $event"
                            />
                        </template>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>
    </AuthenticatedLayout>
</template>
