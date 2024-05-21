<script setup>
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";
import {Wallet} from "@/Components/Icons/outline.jsx";
import {transactionFormat} from "@/Composables/index.js";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";

const transactionTypes = [
    {
        name: 'Deposit',
        value: 'BalanceIn',
    },
    {
        name: 'Withdrawal',
        value: 'BalanceOut',
    },
]
const selectedTransactionType = ref(transactionTypes[0]);

const fundTypes = [
    {
        name: 'Real Fund',
        value: 'RealFund',
    },
    {
        name: 'Demo Fund',
        value: 'DemoFund',
    },
]
const selectedFundType = ref(fundTypes[0]);

const props = defineProps({
    tradingListing: Object,
})
const emit = defineEmits(['update:tradingModal']);
const { formatAmount } = transactionFormat();

const form = useForm({
    user_id: props.tradingListing.user_id,
    meta_login: props.tradingListing.meta_login,
    transaction_type: '',
    fund_type: '',
    amount: '',
    description: '',
})

const submit = () => {
    form.transaction_type = selectedTransactionType.value.value;
    form.fund_type = selectedFundType.value.value;
    form.post(route('member.balanceAdjustment'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const closeModal = () => {
    emit('update:tradingModal', false);
}
</script>

<template>
    <form>
        <div class="h-24 flex justify-between items-center px-5 mb-8 shadow-md rounded-xl bg-gray-200 dark:bg-gray-700 w-full">
            <div class="space-y-2 w-full">
                <div class="flex justify-between text-base font-semibold text-gray-600 dark:text-white">
                    <div>
                        Account Number
                    </div>
                    <div>
                        {{ tradingListing.meta_login }}
                    </div>
                </div>
                <div class="flex justify-between items-center font-semibold text-gray-600 dark:text-white w-full">
                    <div>
                        Balance
                    </div>
                    <div class="text-xl text-primary-700">
                        $ {{ formatAmount(tradingListing.balance) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-4 mt-3 mb-8">
            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="transaction_type" value="Transaction Type" />
                <div class="md:col-span-3">
                    <RadioGroup v-model="selectedTransactionType">
                        <RadioGroupLabel class="sr-only">Transaction Type</RadioGroupLabel>
                        <div class="flex gap-3 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(transactionType, index) in transactionTypes"
                                :key="index"
                                :value="transactionType"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                            active
                                                ? 'ring-0 ring-white ring-offset-0'
                                                : '',
                                            checked ? 'border-gray-400 dark:border-white bg-gray-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                                                    {{ transactionType.name }}
                                                </div>
                                            </RadioGroupLabel>
                                        </div>
                                    </div>
                                </div>
                            </RadioGroupOption>
                        </div>
                    </RadioGroup>
                    <InputError :message="form.errors.transaction_type" class="mt-1 col-span-4" />
                </div>
            </div>
            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="fund_type" value="Fund Type" />
                <div class="md:col-span-3">
                    <RadioGroup v-model="selectedFundType">
                        <RadioGroupLabel class="sr-only">Fund Type</RadioGroupLabel>
                        <div class="flex gap-3 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(fundType, index) in fundTypes"
                                :key="index"
                                :value="fundType"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                            active
                                                ? 'ring-0 ring-white ring-offset-0'
                                                : '',
                                            checked ? 'border-gray-400 dark:border-white bg-gray-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                                                    {{ fundType.name }}
                                                </div>
                                            </RadioGroupLabel>
                                        </div>
                                    </div>
                                </div>
                            </RadioGroupOption>
                        </div>
                    </RadioGroup>
                    <InputError :message="form.errors.fund_type" class="mt-1" />
                </div>
            </div>
            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="amount" value="Amount" />
                <Input
                    id="amount"
                    type="number"
                    min="0"
                    placeholder="$ 0.00"
                    class="block w-full"
                    v-model="form.amount"
                    autofocus
                    :invalid="form.errors.amount"
                />
                <InputError :message="form.errors.amount" class="mt-1" />
            </div>
            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="description" value="Description" />
                <Input
                    id="description"
                    type="text"
                    placeholder="Description"
                    class="block w-full"
                    v-model="form.description"
                    :invalid="form.errors.description"
                />
                <InputError :message="form.errors.description" class="mt-1 col-span-4" />
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <Button
                type="button"
                variant="transparent"
                class="px-4 py-2 justify-center"
                @click="closeModal"
            >
                <span class="text-sm font-semibold">Cancel</span>
            </Button>
            <Button
                variant="primary"
                class="px-4 py-2 justify-center"
                :disabled="form.processing"
                @click.prevent="submit"
            >
                <span class="text-sm font-semibold">Confirm</span>
            </Button>
        </div>
    </form>
</template>
