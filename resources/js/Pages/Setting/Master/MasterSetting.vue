<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "@/Components/Button.vue";
import { CogIcon } from "@heroicons/vue/solid"
import Modal from "@/Components/Modal.vue";
import { ref, watch } from "vue"
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon, RefreshIcon} from "@heroicons/vue/outline";
import MasterTable from "@/Pages/Setting/Master/MasterTable.vue";

const props = defineProps({
    settings: Array,
    withdrawal: Object,
})
const configureWithdrawal = ref(false)
const { formatDateTime, formatAmount } = transactionFormat();
const search = ref('');

const form = useForm({
    id: '',
    value: props.withdrawal.value,
});

const configureSetting = (setting) => {
    
    if (setting.slug == 'withdrawal-fee') {
        configureWithdrawal.value = true;
        
        form.id = setting.id;
        form.value = setting.value;
    }
}

const closeModal = () => {
    configureWithdrawal.value = false
}
</script>

<template>
<AuthenticatedLayout title="Master Setting">
    <template #header>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight">
                Master Setting
            </h2>
        </div>
    </template>

    <div class="flex justify-end items-center self-stretch pb-2">
        <div>
            <InputIconWrapper class="w-full md:w-[280px]">
                <template #icon>
                    <SearchIcon aria-hidden="true" class="w-5 h-5" />
                </template>
                <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
            </InputIconWrapper>
        </div>
    </div>

    <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900">
        <MasterTable 
            :settings="settings" 
            :withdrawal="withdrawal" 
            :search="search"
        />
    </div>

</AuthenticatedLayout>
</template>