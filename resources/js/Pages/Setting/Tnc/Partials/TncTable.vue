<script setup>
import {ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Loading from "@/Components/Loading.vue";
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import {ArrowLeftIcon, ArrowRightIcon, ClipboardListIcon} from "@heroicons/vue/outline";
import { Edit } from "@/Components/Icons/outline";
import Modal from "@/Components/Modal.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import TipTapEditor from "@/Components/TipTapEditor.vue";
import BaseListbox from "@/Components/BaseListbox.vue";

const props = defineProps({
    paymentHistories: Array,
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    filter: String,

})

const { formatDateTime } = transactionFormat();
const tnc = ref({data: []});
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const tncLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh']);

watch(
    [() => props.search, () => props.date ],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '') => {
    tncLoading.value = true
    try {
        let url = `/setting/getTncSetting?page=${page}`;
        
        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        tnc.value = response.data;
    } catch (error) {
        console.error(error.response.data);
    } finally {
        tncLoading.value = false

    }
}

getResults()


const tncSettingModal = ref(false);
const tncSettingDetail = ref();
const editTnCSettingModal = ref(false);
const editTnCSettingData = ref({});
const previewTitle = ref('');
const previewContents = ref('');

const openTnCSettingModal = (tncDetails) => {
    tncSettingModal.value = true
    tncSettingDetail.value = tncDetails
}

const closeModal = () => {
    tncSettingModal.value = false;
    editTnCSettingModal.value = false;

}

const openEditModal = (tncDetails) => {
    editTnCSettingData.value = tncDetails;
    editTnCSettingModal.value = true;
    form.type = editTnCSettingData.value.type
    form.title = editTnCSettingData.value.title
    form.contents = editTnCSettingData.value.contents
};

const form = useForm({
    type: '',
    title: '',
    contents: '',
})

watch(form, (watchFormSubject) => {
    previewTitle.value = watchFormSubject.title;
    previewContents.value = watchFormSubject.contents;
});

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const tncDetails = [
    { value: 'register', label: "Register" },
    { value: 'subscribe', label: "Subscribe" },
    { value: 'trading_account', label: "Add Trading Account" },
    { value: 'terminate', label: "Terminate" },
    { value: 'stop_renewal', label: "Stop Renewal" },
    { value: 'deposit', label: "Deposit" },
    { value: 'become_master', label: "Become Master" },
    { value: 'withdrawal', label: "Withdrawal" },
];

const submit = () => {
    form.put(route('setting.editTnCSetting', editTnCSettingData.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

</script>

<template>
    <!-- <div class="flex justify-between items-center self-stretch border-gray-300 dark:border-gray-500 pb-2">
        <div class="flex items-center gap-4">
            
        </div>
    </div> -->

    <div v-if="tncLoading" class="w-full flex justify-center my-8">
        <Loading />
    </div>
    <table v-else class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-2">
        <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Date
                </th>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Type
                </th>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Title
                </th>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Updated By
                </th>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-if="tnc.data.length === 0">
                <th colspan="15" class="py-4 text-lg text-center">
                    No History
                </th>
            </tr>
            <tr
                v-for="tncDetails in tnc.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600 dark:hover:bg-gray-800"
            >
                <td class="p-3 text-center" colspan="2">
                    {{ formatDateTime(tncDetails.created_at) }}
                </td>
                <td class="p-3 text-center" colspan="2">
                    {{ tncDetails.type }}
                </td>
                <td class="p-3 text-center" colspan="2">
                    {{ tncDetails.title }}
                </td>
                <td class="p-3 text-center" colspan="2">
                    {{ tncDetails.user.name }}
                </td>
                <td class="p-3 text-center" colspan="2">
                    <Tooltip content="View T&C" placement="bottom" class="relative">
                        <Button
                            type="button"
                            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                            variant="gray"
                            pill
                            @click="openTnCSettingModal(tncDetails)"
                        >
                            <ClipboardListIcon aria-hidden="true" class="w-5 h-5 absolute" />
                            <span class="sr-only">View Details</span>
                        </Button>
                    </Tooltip>
                    <Tooltip content="Edit T&C" placement="bottom" class="relative">
                        <Button
                            type="button"
                            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                            variant="gray"
                            pill
                            @click="openEditModal(tncDetails)"
                        >
                            <Edit aria-hidden="true" class="w-5 h-5 absolute" />
                            <span class="sr-only">Edit T&C Setting</span>
                        </Button>
                    </Tooltip>
                </td>
            </tr>
        </tbody>
    </table>

    <Modal :show="tncSettingModal" title="Details" @close="closeModal">
        <div class="text-xs dark:text-gray-400">{{ formatDateTime(tncSettingDetail.created_at) }}</div>
        <div class="my-5 dark:text-white">{{ tncSettingDetail.type }}</div>
        <div class="my-5 dark:text-white">{{ tncSettingDetail.title }}</div>
        <div class="text-black dark:text-gray-300 text-sm prose max-w-none leading-3" v-html="tncSettingDetail.contents"></div>
    </Modal>

    <Modal :show="editTnCSettingModal" title="Edit T&C Setting" max-width="6xl" @close="closeModal">
        <div class="grid grid-rows-2 md:grid-rows-1 md:grid-cols-2 gap-5 w-full">
            <form
                @submit.prevent="submit"
                class="flex flex-col gap-5"
            >
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="type" value="Type" />
                    <div class="md:col-span-3">
                    <BaseListbox
                        v-model="form.type"
                        :options=tncDetails
                        :error="form.errors.type"
                    />
                </div>
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="title" value="Title" />
                    <Input
                        id="title"
                        type="text"
                        placeholder="Enter title"
                        class="block w-full"
                        :class="form.errors.title ? 'border border-primary-500 dark:border-primary-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.title"
                    />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="content" value="Contents" />
                    <TipTapEditor
                        v-model="form.contents"
                    />
                    <InputError :message="form.errors.contents" class="mt-2" />
                </div>
                <div class="flex pt-8 gap-3 justify-end border-t dark:border-gray-700">
                    <Button variant="secondary" class="px-4 py-2 justify-center" @click="closeModal">
                        <span class="text-sm font-semibold">Cancel</span>
                    </Button>
                    <Button variant="primary" class="px-4 py-2 justify-center" :disabled="form.processing">
                        <span class="text-sm font-semibold">Confirm</span>
                    </Button>
                </div>
            </form>
            <div>
                <h3 class="font-semibold dark:text-white text-base pb-3 border-b dark:border-gray-700">Preview</h3>
                <div
                    v-if="previewTitle === '' && previewContents === ''"
                    class="flex flex-col items-center justify-center mt-12"
                >
                    <img src="/assets/no_data.png" class="w-80" alt="no preview">
                    <div class="dark:text-gray-400 mt-4">No preview</div>
                </div>
                <div v-else class="pt-8">
                    <h3 class="font-semibold text-sm dark:text-white">{{ previewTitle }}</h3>
                    <div class="mt-5 dark:text-gray-400 prose max-w-none leading-3 text-xs" v-html="previewContents"></div>
                </div>
            </div>
        </div>
    </Modal>
</template>