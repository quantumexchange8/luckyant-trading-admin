<script setup>
import Button from "primevue/button";
import {
    IconCirclePlus,
    IconCircleCheckFilled, IconArrowNarrowRight, IconArrowNarrowLeft, IconX
} from "@tabler/icons-vue";
import {ref, watch, watchEffect} from "vue";
import ToggleSwitch from 'primevue/toggleswitch';
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import Image from "primevue/image";

const props = defineProps({
    settingPayment: Object
})

const emit = defineEmits(['update:visible']);

const countries = ref([]);
const selectedCountry = ref();
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/getCountries');
        countries.value = response.data;

        if (props.settingPayment.payment_method === 'Bank') {
            selectedCountry.value = countries.value.find(country => country.id === props.settingPayment.country) || null;
        }
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

watch(selectedCountry, (newCountry) => {
    form.currency = newCountry.currency;
});

const selectedLeaders = ref();
const users = ref([]);
const loadingUsers = ref(false);

const getLeaders = async () => {
    loadingUsers.value = true;
    try {
        const response = await axios.get('/getLeaders');
        users.value = response.data;
        const selectedLeaderUserIds = props.settingPayment.visible_leaders.map(leader => leader.user_id);
        selectedLeaders.value = users.value.filter(leader => selectedLeaderUserIds.includes(leader.id));
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loadingUsers.value = false;
    }
};

getLeaders();
getCountries();

const form = useForm({
    id: props.settingPayment.id,
    payment_method: props.settingPayment.payment_method,
    payment_account_name: props.settingPayment.payment_account_name,
    payment_platform_name: props.settingPayment.payment_platform_name,
    account_no: props.settingPayment.account_no,
    country: '',
    currency: '',
    bank_swift_code: props.settingPayment.bank_swift_code,
    network: null,
    payment_logo: '',
    leaders: null,
    status: props.settingPayment.status === 'Active',
});

const cryptoNetworks = ref([
    { name: 'TRC20' },
    { name: 'ERC20' },
    { name: 'BEP20' },
]);

const selectedNetworks = ref([]);

watchEffect(() => {
    if (props.settingPayment.payment_method === "Crypto") {
        selectedNetworks.value = cryptoNetworks.value.filter(network =>
            props.settingPayment.crypto_network.split(',').includes(network.name)
        );
    } else {
        selectedNetworks.value = []; // Reset when payment method is not Crypto
    }
});

const selectedSlip = ref(null);
const selectedSlipName = ref(null);
const handleUploadSlip = (event) => {
    const masterLogoInput = event.target;
    const file = masterLogoInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedSlip.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedSlipName.value = file.name;
        form.payment_logo = event.target.files[0];
    } else {
        selectedSlip.value = null;
    }
};

const removeSlip = () => {
    selectedSlip.value = null;
    form.payment_logo = '';
};

const submitForm = () => {
    form.leaders = selectedLeaders.value;
    form.network = selectedNetworks.value;

    if (form.payment_method === 'Bank') {
        form.country = selectedCountry.value.id
        form.currency = selectedCountry.value.currency
    }

    form.post(route('setting.updatePaymentSetting'), {
        onSuccess: () => {
            closeDialog();
        }
    })
}

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <form class="w-full">
        <div class="flex flex-col gap-6 items-center self-stretch">
            <!-- Info -->
            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.basic_information') }}</span>

                <!-- Bank -->
                <div v-if="settingPayment.payment_method === 'Bank'" class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="payment_platform_name"
                            :value="$t('public.bank_name')"
                            :invalid="!!form.errors.payment_platform_name"
                        />
                        <InputText
                            id="payment_platform_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_platform_name"
                            :placeholder="$t('public.enter_bank_name')"
                            :invalid="!!form.errors.payment_platform_name"
                        />
                        <InputError :message="form.errors.payment_platform_name" />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="bank_swift_code"
                            :value="$t('public.bank_swift_code')"
                            :invalid="!!form.errors.bank_swift_code"
                        />
                        <InputText
                            id="bank_swift_code"
                            type="text"
                            class="block w-full"
                            v-model="form.bank_swift_code"
                            :placeholder="$t('public.enter_bank_swift_code')"
                            :invalid="!!form.errors.bank_swift_code"
                        />
                        <InputError :message="form.errors.bank_swift_code" />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="payment_account_name"
                            :value="$t('public.account_name')"
                            :invalid="!!form.errors.payment_account_name"
                        />
                        <InputText
                            id="payment_account_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_account_name"
                            :placeholder="$t('public.enter_account_name')"
                            :invalid="!!form.errors.payment_account_name"
                        />
                        <InputError :message="form.errors.payment_account_name" />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="account_no"
                            :value="$t('public.account_number')"
                            :invalid="!!form.errors.account_no"
                        />
                        <InputText
                            id="account_no"
                            type="text"
                            class="block w-full"
                            v-model="form.account_no"
                            :placeholder="$t('public.enter_account_number')"
                            :invalid="!!form.errors.account_no"
                        />
                        <InputError :message="form.errors.account_no" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="country"
                            :value="$t('public.country')"
                        />
                        <Select
                            v-model="selectedCountry"
                            :options="countries"
                            optionLabel="name"
                            :placeholder="$t('public.select_country')"
                            class="w-full"
                            filter
                            :filter-fields="['name']"
                            :loading="loadingCountries"
                            :invalid="!!form.errors.country"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    {{ slotProps.value.name }}
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div>{{ slotProps.option.name }}</div>
                            </template>
                        </Select>
                        <InputError :message="form.errors.country" />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="currency"
                            :value="$t('public.currency')"
                            :invalid="!!form.errors.currency"
                        />
                        <InputText
                            id="currency"
                            type="text"
                            class="block w-full"
                            disabled
                            v-model="form.currency"
                            :placeholder="$t('public.enter_currency')"
                            :invalid="!!form.errors.currency"
                        />
                        <InputError :message="form.errors.currency" />
                    </div>
                </div>

                <!-- Crypto -->
                <div v-else-if="settingPayment.payment_method === 'Crypto'" class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="payment_platform_name"
                            :value="$t('public.tether')"
                            :invalid="!!form.errors.payment_platform_name"
                        />
                        <InputText
                            id="payment_platform_name"
                            type="text"
                            class="block w-full"
                            disabled
                            v-model="form.payment_platform_name"
                            :placeholder="$t('public.enter_bank_name')"
                            :invalid="!!form.errors.payment_platform_name"
                        />
                        <InputError :message="form.errors.payment_platform_name" />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="network"
                            :value="$t('public.network')"
                            :invalid="!!form.errors.network"
                        />
                        <MultiSelect
                            v-model="selectedNetworks"
                            :options="cryptoNetworks"
                            optionLabel="name"
                            filter
                            :filter-fields="['name']"
                            :placeholder="$t('public.select_network')"
                            :maxSelectedLabels="3"
                            class="w-full"
                            :invalid="!!form.errors.network"
                        />
                        <InputError :message="form.errors.network" />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="payment_account_name"
                            :value="$t('public.wallet_name')"
                            :invalid="!!form.errors.payment_account_name"
                        />
                        <InputText
                            id="payment_account_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_account_name"
                            :placeholder="$t('public.enter_wallet_name')"
                            :invalid="!!form.errors.payment_account_name"
                        />
                        <InputError :message="form.errors.payment_account_name" />
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="account_no"
                            :value="$t('public.token_address')"
                            :invalid="!!form.errors.account_no"
                        />
                        <InputText
                            id="account_no"
                            type="text"
                            class="block w-full"
                            v-model="form.account_no"
                            :placeholder="$t('public.enter_token_address')"
                            :invalid="!!form.errors.account_no"
                        />
                        <InputError :message="form.errors.account_no" />
                    </div>
                </div>
            </div>

            <!-- Additional -->
            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.additional') }}</span>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <!-- Upload logo -->
                    <div
                        v-if="settingPayment.payment_method === 'Bank'"
                        class="flex flex-col gap-1 items-start self-stretch"
                    >
                        <InputLabel
                            for="payment_logo"
                            :value="$t('public.bank_logo')"
                            :invalid="!!form.errors.payment_logo"
                        />
                        <div class="flex flex-col gap-3">
                            <input
                                ref="masterLogoInput"
                                id="payment_logo"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleUploadSlip"
                            />
                            <Button
                                type="button"
                                :label="$t('public.browse')"
                                severity="info"
                                size="small"
                                @click="$refs.masterLogoInput.click()"
                            />
                        </div>
                        <InputError :message="form.errors.payment_logo" />

                        <div
                            v-if="selectedSlip"
                            class="relative w-full py-3 pl-4 flex items-center justify-between rounded-lg bg-primary-50 dark:bg-gray-800"
                        >
                            <div class="inline-flex items-center gap-3">
                                <Image
                                    :src="selectedSlip"
                                    alt="Image"
                                    imageClass="max-w-full h-9 object-contain rounded"
                                    preview
                                />
                                <div class="text-sm text-surface-900 dark:text-white">
                                    {{ selectedSlipName }}
                                </div>
                            </div>
                            <Button
                                type="button"
                                severity="danger"
                                text
                                rounded
                                aria-label="Remove"
                                size="small"
                                @click="removeSlip"
                            >
                                <template #icon>
                                    <IconX size="16" />
                                </template>
                            </Button>
                        </div>
                    </div>

                    <!-- Visible to -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="leaders"
                            :value="$t('public.visible_to')"
                        />
                        <MultiSelect
                            v-model="selectedLeaders"
                            :options="users"
                            optionLabel="name"
                            filter
                            :filter-fields="['name', 'email']"
                            placeholder="Select leaders"
                            :maxSelectedLabels="3"
                            class="w-full"
                            :invalid="!!form.errors.leaders"
                        />
                        <InputError :message="form.errors.leaders" />
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="status"
                            :value="$t('public.status')"
                        />
                        <ToggleSwitch v-model="form.status"  />
                        <InputError :message="form.errors.status" />
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-5 flex items-center gap-5 justify-end self-stretch w-full">
            <Button
                type="button"
                severity="secondary"
                class="w-full md:w-auto px-4"
                :disabled="form.processing"
                @click="closeDialog"
            >
                {{ $t('public.cancel') }}
            </Button>

            <Button
                type="button"
                :disabled="form.processing"
                class="w-full md:w-auto px-4"
                @click="submitForm"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>
