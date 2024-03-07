<script setup>
import Button from "@/Components/Button.vue";
import {CogIcon} from "@heroicons/vue/solid";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Combobox from "@/Components/Combobox.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {ref, watch} from "vue";
import AvatarInput from "@/Pages/Member/MemberDetails/Partials/AvatarInput.vue";
import BankImg from "/public/assets/bank.jpg"

const props = defineProps({
    countries: Array,
    paymentDetails: Object,
})

const form = useForm({
    payment_method: '',
    payment_account_name: '',
    payment_platform_name: '',
    account_no: '',
    country: 132,
    bank_swift_code: '',
    bank_code: '',
    network: null,
    payment_logo: '',
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

const selected = ref(plans[0]);
const configureSetting = ref(false)
const cryptoName = ref('USDT')

watch((selected), (newSelect) => {

    if (newSelect) {
        form.payment_method = newSelect.value;
        form.payment_account_name = ''
        form.payment_platform_name = ''
        form.account_no = ''
    }
});

const configurePaymentSetting = () => {
    configureSetting.value = true
}

const closeModal = () => {
    configureSetting.value = false
}

const selectedNetworks = ref([]);

async function loadNetworks(query, setOptions) {
    const response = await fetch('/setting/getCryptoNetworks?query=' + query);
    const results = await response.json();

    const options = results.map(network => ({
        value: network.id,
        label: network.crypto_network,
    }));

    setOptions(options);

    if (selectedNetworks.value.length === 0 && options.length > 0) {
        selectedNetworks.value.push(...options);
    }
}

const submit = () => {
    form.payment_method = selected.value.value;

    if (form.payment_method === 'Crypto') {
        form.payment_platform_name = cryptoName.value;
        form.network = selectedNetworks.value.map(network => network.label);
        form.country = undefined;
    }

    form.post(route('setting.addPaymentSetting'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}
</script>

<template>
    <Button
        type="button"
        variant="primary"
        size="sm"
        class="items-center gap-2 max-w-md"
        v-slot="{ iconSizeClasses }"
        @click="configurePaymentSetting"
    >
        <CogIcon aria-hidden="true" :class="iconSizeClasses" />
        <span>Add Payment</span>
    </Button>

    <Modal :show="configureSetting" :title="$t('Payment Setting')" max-width="4xl" @close="closeModal">
        <form class="space-y-2">
            <div class="space-y-2 mb-6">
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

            <div v-if="selected.name === 'Banks'" class="space-y-2">
                <!-- <BankSetting/> -->

                <div class="flex flex-col gap-2 justify-center items-center">
                    <AvatarInput class="w-20 h-20 rounded-full" v-model="form.payment_logo" :default-src="BankImg" />
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Bank Logo
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                            for="country"
                            :value="$t('Country')"
                        />
                        <BaseListbox
                            :options="countries"
                            v-model="form.country"
                        />
                        <InputError :message="form.errors.country" />
                    </div>
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
                            for="bank_code"
                            value="Bank Code"
                        />
                        <Input
                            id="bank_code"
                            type="text"
                            class="block w-full"
                            placeholder="Optional"
                            v-model="form.bank_code"
                            :invalid="form.errors.bank_code"
                        />
                        <InputError :message="form.errors.bank_code" />
                    </div>
                </div>

            </div>

            <div v-else-if="selected.name === 'Crypto'" class="space-y-2">
                <!-- <CryptoSetting/> -->

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="space-y-2">
                        <Label
                            for="crypto_name"
                            value="Tether"
                        />
                        <Input
                            id="crypto_name"
                            type="text"
                            class="block w-full"
                            readonly
                            v-model="cryptoName"
                            :invalid="form.errors.payment_platform_name"
                        />
                        <InputError :message="form.errors.payment_platform_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="network"
                            value="Network"
                        />
                        <Combobox
                            multiple
                            :disabled="form.network==='everyone'"
                            placeholder="Please Select"
                            :load-options="loadNetworks"
                            v-model="selectedNetworks"
                            :error="form.errors.network"
                        />
                        <InputError :message="form.errors.network" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="crypto_wallet_name"
                            value="Crypto Wallet Name"
                        />
                        <Input
                            id="crypto_wallet_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_account_name"
                            :invalid="form.errors.payment_account_name"
                        />
                        <InputError :message="form.errors.payment_account_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="wallet_address"
                            value="Wallet Address"
                        />
                        <Input
                            id="wallet_address"
                            type="text"
                            min="0"
                            class="block w-full"
                            v-model="form.account_no"
                            :invalid="form.errors.account_no"
                        />
                        <InputError :message="form.errors.account_no" />
                    </div>
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
</template>
