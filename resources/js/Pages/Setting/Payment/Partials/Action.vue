<script setup>
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import { Edit } from "@/Components/Icons/outline.jsx";
import {ClipboardListIcon, TrashIcon, UserGroupIcon, PlusIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import AvatarInput from "@/Pages/Member/MemberDetails/Partials/AvatarInput.vue";
import DeletePayment from "@/Pages/Setting/Payment/Partials/DeletePayment.vue";
import BankImg from "/public/assets/bank.jpg"
import BaseListbox from "@/Components/BaseListbox.vue";
import {
  RadioGroup,
  RadioGroupLabel,
  RadioGroupDescription,
  RadioGroupOption,
} from '@headlessui/vue'
import Combobox from "@/Components/Combobox.vue";

const props = defineProps({
    bank: Object,
    countries: Array,
})

const editDetailModal = ref(false);
const modalComponent = ref(null);

const openEditModal = (paymentId, componentType) => {
    editDetailModal.value = true;
    if (componentType === 'editPayment') {
        modalComponent.value = 'Edit Payment';
    } else if (componentType === 'deletePayment') {
        modalComponent.value = 'Delete Payment';
    }
}

const closeModal = () => {
    editDetailModal.value = false
    modalComponent.value = null;
    selectedNetworks.value = [];
}

const status = [
    {
        name: 'Active',
        value: 'Active',
    },
    {
        name: 'Inactive',
        value: 'Inactive',
    }
]

const selected = ref(status.find(plan => plan.value === props.bank.status) || status[0]);

const form = useForm({
    id: props.bank.id,
    payment_method: props.bank.payment_method,
    payment_account_name: props.bank.payment_account_name,
    payment_platform_name: props.bank.payment_platform_name,
    account_no: props.bank.account_no,
    country: props.bank.country,
    bank_swift_code: props.bank.bank_swift_code,
    bank_code: props.bank.bank_code ? props.bank.bank_code : '',
    network: null,
    payment_logo: '',
    status: '',
    crypto_network: '',
});

const selectedNetworks = ref([]);

async function loadNetworks(query, setOptions) {
    const response = await fetch('/setting/getCryptoNetworks?query=' + query);
    const results = await response.json();

    const options = results.map(network => ({
        value: network.id,
        label: network.crypto_network,
    }));

    setOptions(options);

    const cryptoNetworks = props.bank.crypto_network.split(',').map(network => network.trim());
    
    cryptoNetworks.forEach(network => {
        const selectedOption = options.find(option => option.label === network);
        
        if (selectedOption) {
            selectedNetworks.value.push(selectedOption);
        }
    });

    // if (selectedNetworks.value.length === 0 && options.length > 0) {
    //     selectedNetworks.value.push(...options);
    // }
}

const submit = () => {
    form.status = selected.value.value;
    form.network = selectedNetworks.value.map(network => network.label)
    form.post(route('setting.updatePaymentSetting'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}


</script>

<template>
    <div class="flex justify-center">
        <Tooltip content="Edit Details" placement="bottom">
            <Button
                type="button"
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="gray"
                pill
                @click="openEditModal(bank.id, 'editPayment')"
            >
                <Edit aria-hidden="true" class="w-5 h-5 absolute" />
                <span class="sr-only">View Details</span>
            </Button>
        </Tooltip>
        <Tooltip content="Delete" placement="bottom">
            <Button
                type="button"
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="danger"
                pill
                @click="openEditModal(bank.id, 'deletePayment')"
            >
                <TrashIcon aria-hidden="true" class="w-5 h-5 absolute" />
                <span class="sr-only">Delete</span>
            </Button>
        </Tooltip>
    </div>

    <Modal :show="editDetailModal" :title="modalComponent" @close="closeModal" max-width="xl">
        <template v-if="modalComponent === 'Edit Payment'">
            <form>
                <div v-if="props.bank.payment_method == 'Bank'" class="flex flex-col gap-2">
                    <div class="space-y-2">
                        <AvatarInput class="w-20 h-20 rounded-full" v-model="form.payment_logo" :default-src="bank.bank_logo_url ? bank.bank_logo_url : BankImg" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="bank_name"
                            :value="$t('Bank Name')"
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
                            :value="$t('Account Number')"
                        />
                        <Input
                            id="account_number"
                            type="text"
                            class="block w-full"
                            v-model="form.account_no"
                            :invalid="form.errors.account_no"
                        />
                        <InputError :message="form.errors.account_no" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="account_name"
                            :value="$t('Bank Account Name')"
                        />
                        <Input
                            id="account_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_account_name"
                            :invalid="form.errors.payment_account_name"
                        />
                        <InputError :message="form.errors.payment_account_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="bank_swift_code"
                            :value="$t('Bank Swift Code')"
                        />
                        <Input
                            id="bank_swift_code"
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
                    <div class="space-y-2">
                        <Label
                            for="bank_code"
                            :value="$t('Bank Code')"
                        />
                        <Input
                            id="bank_code"
                            type="text"
                            class="block w-full"
                            v-model="form.bank_code"
                            :invalid="form.errors.bank_code"
                        />
                        <InputError :message="form.errors.bank_code" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="status"
                            :value="$t('Status')"
                        />
                        <RadioGroup v-model="selected">
                            <RadioGroupLabel class="sr-only">Status</RadioGroupLabel>
                            <div class="flex gap-3 items-center self-stretch w-full">
                                <RadioGroupOption
                                    as="template"
                                    v-for="(plan, index) in status"
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
                        <InputError :message="form.errors.bank_code" />
                    </div>
                </div>

                <div v-if="props.bank.payment_method == 'Crypto'" class="flex flex-col gap-2">
                    <div class="space-y-2">
                        <Label
                            for="tether"
                            :value="$t('Tether')"
                        />
                        <Input
                            id="tether"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_platform_name"
                            :invalid="form.errors.payment_platform_name"
                        />
                        <InputError :message="form.errors.payment_platform_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="network"
                            :value="$t('Network')"
                        />
                        <Combobox
                            multiple
                            :disabled="form.crypto_network==='everyone'"
                            placeholder="Please Select"
                            :load-options="loadNetworks"
                            v-model="selectedNetworks"
                            :error="form.errors.network"
                        />
                        <InputError :message="form.errors.payment_platform_name" />
                    </div>
                    <div class="space-y-2">
                        <Label
                            for="crypto_wallet_name"
                            :value="$t('Crypto Wallet Name')"
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
                            for="wallet_addres"
                            :value="$t('Wallet Address')"
                        />
                        <Input
                            id="wallet_addres"
                            type="text"
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
        </template>

        <template v-if="modalComponent === 'Delete Payment'">
            <DeletePayment
                :bank="bank"
                @update:editDetailModal="editDetailModal = $event"
            />
        </template>
    </Modal>
</template>
