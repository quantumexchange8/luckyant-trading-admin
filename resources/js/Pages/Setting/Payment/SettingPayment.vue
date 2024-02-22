<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {useForm} from "@inertiajs/vue3";
import {
  RadioGroup,
  RadioGroupLabel,
  RadioGroupDescription,
  RadioGroupOption,
} from '@headlessui/vue'
import { ref, watch } from "vue"
import { CogIcon } from "@heroicons/vue/solid"
import Modal from "@/Components/Modal.vue";
import PaymentHistory from "@/Pages/Setting/Payment/Partials/PaymentHistory.vue";

const props = defineProps({
    countries: Array,
    paymentDetails: Object,
    paymentHistories: Array,
})

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

const selected = ref(plans[0])
const configureSetting = ref(false)

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
</script>

<template>
    <AuthenticatedLayout title="Setting Payment">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Payment Setting
                </h2>
                
            </div>
        </template>

        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-900">
            <div class="flex justify-between items-center self-stretch border-b border-gray-300 dark:border-gray-500 pb-2">
                <div class="flex items-center gap-3">
                    <div class="text-lg font-semibold">
                        Payment Details
                    </div>
                </div>
                <div>
                    <div
                        class="flex w-full px-2 py-1 justify-center text-white mx-auto rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out"
                        
                    >
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
            </div>

            <div>
                <table class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
                    <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                        <tr>
                            <th scope="col" colspan="2" class="px-3 py-2.5 text-center">
                                Payment Method
                            </th>
                            <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-56">
                                Payment Account Name
                            </th>
                            <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-56">
                                Platform Name
                            </th>
                            <th scope="col" colspan="2" class="px-3 py-2.5 text-center w-56">
                                Account No / Wallet Address
                            </th>
                            <th v-if="props.paymentDetails.payment_method == 'Banks'" scope="col" colspan="2" class="px-3 py-2.5 text-center w-56">
                                Bank Swift Code
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-3 py-2.5 text-center" colspan="2">
                                {{ props.paymentDetails.payment_method }}
                            </td>
                            <td class="px-3 py-2.5 text-center" colspan="2">
                                {{ props.paymentDetails.payment_account_name }}
                            </td>
                            <td class="px-3 py-2.5 text-center" colspan="2">
                                {{ props.paymentDetails.payment_platform_name }}
                            </td>
                            <td class="px-3 py-2.5 text-center" colspan="2">
                                {{ props.paymentDetails.account_no }}
                            </td>
                            <td v-if="props.paymentDetails.payment_method == 'Banks'" class="px-3 py-2.5 text-center" colspan="2">
                                {{ props.paymentDetails.bank_swift_code }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <PaymentHistory
            :paymentHistories="paymentHistories"
        />

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