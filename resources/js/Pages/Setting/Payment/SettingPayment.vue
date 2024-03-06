<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {useForm} from "@inertiajs/vue3";
import {
  RadioGroup, RadioGroupLabel, RadioGroupOption,
  Tab, TabGroup, TabList, TabPanel, TabPanels
} from '@headlessui/vue'
import { ref, watch } from "vue"
import { CogIcon, SearchIcon, RefreshIcon } from "@heroicons/vue/solid"
import Modal from "@/Components/Modal.vue";
import PaymentHistory from "@/Pages/Setting/Payment/Partials/PaymentHistory.vue";
import PaymentActive from "@/Pages/Setting/Payment/Partials/PaymentActive.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";

const props = defineProps({
    countries: Array,
    paymentDetails: Object,
    paymentHistories: Array,
})

const refresh = ref(false);
const isLoading = ref(false);
const search = ref('');
const date = ref('');
const filter = ref('');
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const form = useForm({
    payment_method: '',
    payment_account_name: props.paymentDetails.payment_account_name,
    payment_platform_name: props.paymentDetails.payment_platform_name,
    account_no: props.paymentDetails.account_no,
    country: 132,
    currency: 'USD',
    bank_swift_code: props.paymentDetails.bank_swift_code,
});

watch(() => form.bank_swift_code, (newValue, oldValue) => {
    form.bank_swift_code = newValue.toUpperCase();
});

const plans = [
  {
    name: 'Banks',
    value: 'Bank',
  },
  {
    name: 'Crypto',
    value: 'Crypto',
  },
]

const selected = ref(plans.find(plan => plan.value === props.paymentDetails.payment_method) || plans[0]);
const configureSetting = ref(false)

// Watch for changes in props.payment_method and update selected ref accordingly
watch(() => props.paymentDetails.payment_method, (newValue) => {
    const selectedPlan = plans.find(plan => plan.value === newValue);
    if (selectedPlan) {
        selected.value = selectedPlan;
    }
});

watch((selected), (newSelect) => {
    
    if (newSelect && newSelect.value !== props.paymentDetails.payment_method)
    {
        form.payment_method = newSelect.value;
        form.payment_account_name = '',
        form.payment_platform_name = '',
        form.account_no = ''
    }
});

const configurePaymentSetting = () => {
    configureSetting.value = true
}

const closeModal = () => {
    configureSetting.value = false
}

const submit = () => {
    form.payment_method = selected.value.value;
    form.post(route('setting.updatePaymentSetting'), {
        onSuccess: () => {
            closeModal();
        },
    })
}

const transactionVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}

function refreshTable() {
    search.value = '';
    date.value = '';
    filter.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

const type = ref('Active');
const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};
</script>

<template>
    <AuthenticatedLayout title="Setting Payment">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Payment Setting
                </h2>
                
                <div>
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        class="items-center gap-2 max-w-md"
                        v-slot="{ iconSizeClasses }"
                        @click="configurePaymentSetting"
                    >
                        <CogIcon aria-hidden="true" :class="iconSizeClasses" />
                        <span>Configure Payment</span>
                    </Button>
                </div>
            </div>

        </template>

        <div class="pt-3 md:flex md:justify-end items-center">
            <div class="flex flex-wrap items-center md:flex-nowrap gap-3 mt-3 md:mt-0">
                <div class="w-full">
                    <InputIconWrapper class="w-full md:w-[280px]">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="search" type="text" class="block w-full" placeholder="Search" v-model="search" />
                    </InputIconWrapper>
                </div>
                <div class="w-full">
                    <vue-tailwind-datepicker
                        placeholder="Select dates"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                        class="w-full"
                    />
                </div>
                <!-- <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full md:w-[155px] dark:text-white dark:bg-gray-600"
                        v-model="filter"
                        :options="statusList"
                        placeholder="Filter status"
                    />
                </div> -->
                <div>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="refreshTable"
                    >
                        Clear
                    </Button>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900  my-8">
            <div class="text-lg font-semibold pb-4">
                Payment Details
            </div>
            <div class="w-full">
                <TabGroup>
                    <TabList class="max-w-md flex py-1">
                        <Tab
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                @click="updateTransactionType('Active')"
                                :class="[
                                    'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                    selected
                                        ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                        : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                            >
                                Active Payment
                            </button>
                        </Tab>
                        <Tab
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                @click="updateTransactionType('History')"
                                :class="[
                                        'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                                        'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                        selected
                                            ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                            : 'border-b border-gray-300 dark:border-gray-700',
                                    ]"
                            >
                                Payment History
                            </button>
                        </Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel>
                            <PaymentActive
                                :paymentHistories="paymentHistories"
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :filter="filter"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                                @update:export="exportStatus = $event"
                            />
                            
                        </TabPanel>
                        <TabPanel>
                            <PaymentHistory 
                                :paymentHistories="paymentHistories"
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :filter="filter"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                                @update:export="exportStatus = $event"
                            />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>

        <Modal :show="configureSetting" :title="$t('Payment Setting')" @close="closeModal">
            <form class="space-y-2">
                <div class="space-y-2">
                    <Label
                        for="leverage"
                        :value="$t('Payment Method')"
                    />
                    <RadioGroup v-model="selected">
                        <RadioGroupLabel class="sr-only">Signal Status</RadioGroupLabel>
                        <div class="flex gap-3 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(plan, index) in plans"
                                :key="index"
                                :value="plan"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                        active
                                            ? 'ring-0 ring-white ring-offset-0'
                                            : '',
                                        checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 bg-white dark:bg-gray-800 dark:text-white',
                                    ]"
                                    class="relative flex cursor-pointer rounded-xl border p-3 focus:outline-none w-full"
                                >
                                    <div class="flex items-center w-full">
                                        <div class="text-sm flex flex-col gap-3 w-full">
                                            <RadioGroupLabel
                                                as="div"
                                                class="font-medium"
                                            >
                                                <div class="flex justify-center items-center gap-3">
                                                    {{ plan.name }}
                                                </div>
                                            </RadioGroupLabel>
                                        </div>
                                    </div>
                                </div>
                            </RadioGroupOption>
                        </div>
                    </RadioGroup>
                    <InputError :message="form.errors.leverage" />
                </div>

                <div v-if="selected.name == 'Banks'" class="space-y-2">
                    <!-- <BankSetting/> -->
                    <div class="space-y-2">
                        <Label
                            for="bank_account_name"
                            value="Bank Account Name"
                        />
                        <Input
                            id="bank_account_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_account_name"
                            :invalid="form.errors.payment_account_name"
                        />
                        <InputError :message="form.errors.payment_account_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="bank_name"
                            value="Bank Name"
                        />
                        <Input
                            id="bank_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_platform_name"
                            :invalid="form.errors.payment_platform_name"
                        />
                        <InputError :message="form.errors.payment_platform_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="account_number"
                            value="Account Number"
                        />
                        <Input
                            id="account_number"
                            type="number"
                            min="0"
                            class="block w-full"
                            v-model="form.account_no"
                            :invalid="form.errors.account_no"
                        />
                        <InputError :message="form.errors.account_no" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="bank_swift"
                            value="Bank Swift Code"
                        />
                        <Input
                            id="bank_swift"
                            type="text"
                            class="block w-full"
                            v-model="form.bank_swift_code"
                            :invalid="form.errors.bank_swift_code"
                        />
                        <InputError :message="form.errors.bank_swift_code" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="country"
                            :value="$t('Country')"
                        />
                        <BaseListbox
                            :options="countries"
                            v-model="form.country"
                        />
                        <InputError :message="form.errors.country" />
                    </div>
                    <!-- <div class="space-y-2">
                        <Label
                            for="currency"
                            value="Currency"
                        />
                        <BaseListbox v-model="form.currency">
                            <option value="USD">USD</option>
                        </BaseListbox>

                        <InputError :message="form.errors.currency" />
                    </div> -->
                </div>

                <div v-else-if="selected.name == 'Crypto'" class="space-y-2">
                    <!-- <CryptoSetting/> -->
                    <div class="space-y-2">
                        <Label
                            for="crypto_account_name"
                            value="Crypto Wallet Name"
                        />
                        <Input
                            id="crypto_account_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_account_name"
                            :invalid="form.errors.payment_account_name"
                        />
                        <InputError :message="form.errors.payment_account_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="crypto_name"
                            value="Tether"
                        />
                        <Input
                            id="crypto_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_platform_name"
                            :invalid="form.errors.payment_platform_name"
                        />
                        <InputError :message="form.errors.payment_platform_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="account_number"
                            value="Wallet Address"
                        />
                        <Input
                            id="account_number"
                            type="text"
                            min="0"
                            class="block w-full"
                            v-model="form.account_no"
                            :invalid="form.errors.account_no"
                        />
                        <InputError :message="form.errors.account_no" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="network"
                            value="Network"
                        />
                        <!-- <BaseListbox
                            :options="countries"
                            v-model="form.country"
                        /> -->
                    </div>
                </div>

                <div class="pt-5 flex justify-end">
                        <Button
                            class="flex justify-center"
                            @click="submit"
                            :disabled="form.processing"
                        >
                            {{ $t('public.Save') }}
                        </Button>
                    </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>