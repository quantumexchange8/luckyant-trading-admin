<script setup>
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/Label.vue";
import InputText from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import RadioButton from "primevue/radiobutton";
import MultiSelect from "primevue/multiselect";

const visible = ref(false);
const selectedLeaders = ref();
const leaders = ref();
const loadingLeaders = ref(false);

const getLeaders = async () => {
    loadingLeaders.value = true;
    try {
        const response = await axios.get('/setting/getLeadersSel');
        leaders.value = response.data;
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

const openModal = () => {
    visible.value = true;
    getLeaders();
}

const form = useForm({
    name: '',
    platform: '',
    payment_url: '',
    payment_app_name: '',
    secret_key: '',
    secondary_key: '',
    leaders: ''
})

const submitForm = () => {
    form.leaders = selectedLeaders.value;
    form.post(route('setting.addPaymentGateway'), {
        onSuccess: () => {
            closeModal();
            form.reset();
            selectedLeaders.value = null
        }
    })
}

const closeModal = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        type="button"
        variant="primary"
        class="w-full md:w-auto justify-center"
        @click="openModal"
    >
        Add Payment Gateway
    </Button>

    <Modal
        :show="visible"
        :title="$t('Add Payment Gateway')"
        max-width="4xl"
        @close="closeModal"
    >
        <form>
            <div class="flex flex-col gap-6 items-center self-stretch">
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <span class="font-bold text-gray-950 dark:text-white w-full text-left">{{ $t('Basics') }}</span>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="name"
                                :value="$t('public.name')"
                            />
                            <InputText
                                id="name"
                                type="text"
                                class="block w-full"
                                v-model="form.name"
                                placeholder="eg. Luckyant - ABC"
                                :invalid="form.errors.name"
                                autofocus
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="payment_url"
                                value="Payment URL"
                            />
                            <InputText
                                id="payment_url"
                                type="text"
                                class="block w-full"
                                v-model="form.payment_url"
                                placeholder="https://payment_url.com"
                                :invalid="form.errors.payment_url"
                            />
                            <InputError :message="form.errors.payment_url" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="platform"
                                value="Platform"
                            />
                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center">
                                    <RadioButton v-model="form.platform" inputId="platform_spritpayment" value="spritpayment" />
                                    <InputLabel for="platform_spritpayment" class="ml-2">Sprit Payment</InputLabel>
                                </div>
                                <div class="flex items-center">
                                    <RadioButton v-model="form.platform" inputId="platform_ttpay" value="ttpay" />
                                    <InputLabel for="platform_ttpay" class="ml-2">TT Pay</InputLabel>
                                </div>
                            </div>
                            <InputError :message="form.errors.platform" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 items-center self-stretch">
                    <span class="font-bold text-gray-950 dark:text-white w-full text-left">{{ $t('Credentials') }}</span>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="payment_app_name"
                                value="Payment App"
                            />
                            <InputText
                                id="payment_app_name"
                                type="text"
                                class="block w-full"
                                v-model="form.payment_app_name"
                                placeholder="eg. luckyant-abc"
                                :invalid="form.errors.payment_app_name"
                            />
                            <InputError :message="form.errors.payment_app_name" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="secret_key"
                                value="Secret Key"
                            />
                            <InputText
                                id="secret_key"
                                type="text"
                                class="block w-full"
                                v-model="form.secret_key"
                                placeholder="Enter key"
                                :invalid="form.errors.secret_key"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.secret_key" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="secondary_key"
                                value="Secondary Key"
                            />
                            <InputText
                                id="secondary_key"
                                type="text"
                                class="block w-full"
                                v-model="form.secondary_key"
                                placeholder="Enter key"
                                :invalid="form.errors.secondary_key"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.secondary_key" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="leaders"
                                value="Visible To"
                            />
                            <MultiSelect
                                v-model="selectedLeaders"
                                :options="leaders"
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
                    </div>
                </div>
            </div>
            <div class="pt-5 px-2 flex justify-end gap-5">
                <Button
                    type="button"
                    variant="secondary"
                    class="justify-center w-full md:w-auto"
                    @click="closeModal"
                    :disabled="form.processing"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    class="justify-center w-full md:w-auto"
                    @click.prevent="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
