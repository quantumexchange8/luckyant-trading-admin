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
import RadioButton from "primevue/radiobutton";

const transactionTypes = [
    {
        name: 'Deposit',
        value: 'Deposit',
    },
    {
        name: 'Withdrawal',
        value: 'Withdrawal',
    },
]
const selectedTransactionType = ref(transactionTypes[0]);

const props = defineProps({
    member_detail: Object,
    wallet: Object
})
const emit = defineEmits(['update:memberDetailModal']);
const { formatAmount } = transactionFormat();

const form = useForm({
    user_id: props.member_detail.id,
    wallet_id: props.wallet.id,
    type: '',
    transaction_type: '',
    amount: '',
    description: '',
})

const submit = () => {
    form.transaction_type = selectedTransactionType.value.value;
    form.post(route('member.wallet_adjustment'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
        },
    })
}

const closeModal = () => {
    emit('update:memberDetailModal', false);
}
</script>

<template>
    <form>
        <div class="h-24 flex justify-between items-center px-5 mb-8 shadow-md rounded-xl"
             :class="{
                        'bg-gradient-to-bl from-primary-400 to-primary-600': wallet.type === 'cash_wallet',
                        'bg-gradient-to-bl from-purple-300 to-purple-500': wallet.type === 'bonus_wallet',
                        'bg-gradient-to-bl from-gray-300 to-gray-500': wallet.type === 'e_wallet',
                    }"
        >
            <div class="space-y-2">
                <div class="text-base font-semibold text-gray-100 dark:text-white">
                    {{ wallet.name }}
                </div>
                <div class="text-xl font-semibold text-gray-100 dark:text-white">
                    $ {{ formatAmount(wallet.balance) }}
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
                <Label class="text-sm dark:text-white" for="amount" value="Type" />
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center">
                        <RadioButton v-model="form.type" inputId="type_wallet_adjustment" value="WalletAdjustment" />
                        <Label for="type_wallet_adjustment" class="ml-2">Adjustment</Label>
                    </div>
                    <div class="flex items-center">
                        <RadioButton v-model="form.type" inputId="type_wallet_redemption" value="WalletRedemption" />
                        <Label for="type_wallet_redemption" class="ml-2">Redemption</Label>
                    </div>
                    <div class="flex items-center">
                        <RadioButton v-model="form.type" inputId="type_wallet_return" value="ReturnedAmount" />
                        <Label for="type_wallet_return" class="ml-2">Returned Amount</Label>
                    </div>
                </div>
                <InputError :message="form.errors.type" class="mt-1" />
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
