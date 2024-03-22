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
import BaseListbox from "@/Components/BaseListbox.vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    filter: String,

})

const { formatDateTime } = transactionFormat();
const leverages = ref({data: []});
const currentPage = ref(1);
const refreshDeposit = ref(props.refresh);
const leverageLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh']);

watch(
    [() => props.search, () => props.date ],
    debounce(([searchValue, dateValue]) => {
        getResults(1, searchValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', date = '') => {
    leverageLoading.value = true
    try {
        let url = `/setting/getLeverageSetting?page=${page}`;
        
        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        leverages.value = response.data;
    } catch (error) {
        console.error(error.response.data);
    } finally {
        leverageLoading.value = false

    }
}

getResults()

const leverageSettingModal = ref(false);
const leverageSettingDetail = ref();
const editLeverageSettingModal = ref(false);
const editLeverageData = ref({});

const openTnCSettingModal = (leverageDetails) => {
    leverageSettingModal.value = true
    leverageSettingDetail.value = leverageDetails
}

const closeModal = () => {
    leverageSettingModal.value = false;
    editLeverageSettingModal.value = false;

}

const openEditModal = (leverageDetails) => {
    editLeverageData.value = leverageDetails;
    editLeverageSettingModal.value = true;
    form.value = editLeverageData.value.value
    form.status = editLeverageData.value.status
};

const form = useForm({
    value: '',
    status: '',
})

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const status = [
    { value: 'Active', label: "Active" },
    { value: 'Inactive', label: "Inactive" },
];

const submit = () => {
    form.put(route('setting.editLeverageSetting', editLeverageData.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

</script>

<template>
    <div v-if="leverageLoading" class="w-full flex justify-center my-8">
        <Loading />
    </div>
    <table v-else class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-2">
        <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Date
                </th>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Leverage
                </th>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Status
                </th>
                <th scope="col" colspan="2" class="p-3 text-center">
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-if="leverages.data.length === 0">
                <th colspan="15" class="py-4 text-lg text-center">
                    No History
                </th>
            </tr>
            <tr
                v-for="leverageDetails in leverages.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-600 dark:hover:bg-gray-800"
            >
                <td class="p-3 text-center" colspan="2">
                    {{ formatDateTime(leverageDetails.created_at) }}
                </td>
                <td class="p-3 text-center" colspan="2">
                    {{ leverageDetails.display }}
                </td>
                <td class="p-3 text-center" colspan="2">
                    {{ leverageDetails.status }}
                </td>
                <td class="p-3 text-center" colspan="2">
                    <!-- <Tooltip content="View&nbsp;Leverage&nbsp;Detail" placement="bottom" class="relative">
                        <Button
                            type="button"
                            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                            variant="gray"
                            pill
                            @click="openTnCSettingModal(leverageDetails)"
                        >
                            <ClipboardListIcon aria-hidden="true" class="w-5 h-5 absolute" />
                            <span class="sr-only">View Details</span>
                        </Button>
                    </Tooltip> -->
                    <Tooltip content="Edit&nbsp;Leverage" placement="bottom" class="relative">
                        <Button
                            type="button"
                            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                            variant="gray"
                            pill
                            @click="openEditModal(leverageDetails)"
                        >
                            <Edit aria-hidden="true" class="w-5 h-5 absolute" />
                            <span class="sr-only">Edit T&C Setting</span>
                        </Button>
                    </Tooltip>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- <Modal :show="leverageSettingModal" title="Details" @close="closeModal">
        <div class="grid grid-cols-5 items-center">
            <span class="col-span-2 font-semibold dark:text-gray-400">Date</span>
            <span class="text-black col-span-3 dark:text-white py-2">{{ formatDateTime(leverageSettingDetail.created_at) }}</span>
        </div>

        <div class="grid grid-cols-5 items-center">
            <span class="col-span-2 font-semibold dark:text-gray-400">Display</span>
            <span class="text-black col-span-3 dark:text-white py-2">{{ leverageSettingDetail.display }}</span>
        </div>

        <div class="grid grid-cols-5 items-center">
            <span class="col-span-2 font-semibold dark:text-gray-400">Value</span>
            <span class="text-black col-span-3 dark:text-white py-2">{{ leverageSettingDetail.value }}</span>
        </div>

        <div class="grid grid-cols-5 items-center">
            <span class="col-span-2 font-semibold dark:text-gray-400">Status</span>
            <span class="text-black col-span-3 dark:text-white py-2">{{ leverageSettingDetail.status }}</span>
        </div>
    </Modal> -->

    <Modal :show="editLeverageSettingModal" title="Edit Leverage" max-width="2xl" @close="closeModal">
        <div class="w-full">
            <form
                @submit.prevent="submit"
                class="flex flex-col gap-5"
            >
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="value" value="Value" />
                    <div class="md:col-span-3">
                        <Input
                        id="value"
                        type="text"
                        placeholder="Enter value"
                        class="block w-full"
                        :class="form.errors.value ? 'border border-primary-500 dark:border-primary-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.value"
                    />
                </div>
                    <InputError :message="form.errors.value" class="mt-2" />
                </div>
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="status" value="Status" />
                    <BaseListbox
                        v-model="form.status"
                        :options=status
                        :error="form.errors.status"
                    />
                    <InputError :message="form.errors.status" class="mt-2" />
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
        </div>
    </Modal>
</template>