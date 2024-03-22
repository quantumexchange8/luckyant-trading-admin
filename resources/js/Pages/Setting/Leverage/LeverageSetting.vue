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
import LeverageTable from "@/Pages/Setting/Leverage/LeverageTable.vue";
import AddLeverage from "@/Pages/Setting/Leverage/AddLeverage.vue";

const props = defineProps({
    leverageSettings: Object,
})
const configureWithdrawal = ref(false)
const { formatDateTime, formatAmount } = transactionFormat();
const search = ref('');

const form = useForm({
    id: '',
    value: '',
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
<AuthenticatedLayout title="Leverage Setting">
    <template #header>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold leading-tight">
                Leverage Setting
            </h2>
            <div>
                <AddLeverage />
            </div>
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
        <LeverageTable 
            :search="search"
        />
    </div>

</AuthenticatedLayout>
</template>