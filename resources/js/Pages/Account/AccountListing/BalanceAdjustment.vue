<script setup>
import InputLabel from "@/Components/Label.vue";
import RadioButton from "primevue/radiobutton";
import {transactionFormat} from "@/Composables/index.js";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import Button from "@/Components/Button.vue";

const props = defineProps({
    meta: Object
});

const {formatAmount} = transactionFormat();
const emit = defineEmits(["update:visible"]);

const account = ref();
const isLoading = ref(false);

const getResults = async () => {
    isLoading.value = true;

    try {
        let response;
        response = await axios.get(`/account/getAccountByMetaLogin?meta_login=${props.meta.meta_login}`);
        account.value = response.data
    } catch (error) {
        console.error('Error updating data:', error);
    } finally {
        isLoading.value = false;
    }
};

getResults();

const actions = ref([
    { label: 'balance_in', value: 'BalanceIn' },
    { label: 'balance_out', value: 'BalanceOut' }
]);

const fundTypes = ref([
    { label: 'real_fund', value: 'RealFund' },
    { label: 'demo_fund', value: 'DemoFund' },
]);

const form = useForm({
    user_id: '',
    meta_login: '',
    transaction_type: 'BalanceIn',
    fund_type: 'RealFund',
    amount: null,
    description: '',
});

const submitForm = () => {
    form.user_id = account.value.user_id;
    form.meta_login = account.value.meta_login;

    form.post(route('account.balanceAdjustment'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <form>
        <div class="flex flex-col items-center gap-8 self-stretch md:gap-10">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div
                    class="flex flex-col justify-center items-center py-4 px-8 gap-2 self-stretch bg-gray-200 dark:bg-gray-950">
                    <div class="w-full text-gray-500 text-center text-sm font-medium">
                        #{{ meta.meta_login }} - {{ $t('public.balance') }}
                    </div>
                    <div class="w-full text-gray-950 dark:text-white text-center text-xl font-semibold">
                        {{ isLoading ? $t('public.loading') : '$ ' + formatAmount(account.balance ?? 0) }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="transaction_type"
                        :value="$t('public.action')"
                    />
                    <div class="flex items-center gap-4 md:gap-5">
                        <div
                            v-for="(action, index) in actions"
                            :key="index"
                            class="flex items-center gap-2 text-gray-950"
                        >
                            <RadioButton
                                v-model="form.transaction_type"
                                :inputId="action.value"
                                :value="action.value"
                            />
                            <label :for="action.value" class="dark:text-white text-sm">{{ $t(`public.${action.label}`) }}</label>
                        </div>
                    </div>
                    <InputError :message="form.errors.transaction_type"/>
                </div>

                <!-- Fund type -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="fund_type"
                        :value="$t('public.fund_type')"
                    />
                    <div class="flex items-center gap-4 md:gap-5">
                        <div
                            v-for="(fund, index) in fundTypes"
                            :key="index"
                            class="flex items-center gap-2 text-gray-950"
                        >
                            <RadioButton
                                v-model="form.fund_type"
                                :inputId="fund.value"
                                :value="fund.value"
                            />
                            <label :for="fund.value" class="dark:text-white text-sm">{{ $t(`public.${fund.label}`) }}</label>
                        </div>
                    </div>
                    <InputError :message="form.errors.fund_type"/>
                </div>

                <!-- Amount -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="amount"
                        :value="$t('public.amount')"
                    />
                    <InputNumber
                        v-model="form.amount"
                        inputId="amount"
                        class="w-full"
                        :min="0"
                        :step="100"
                        fluid
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        placeholder="$0.00"
                        :invalid="!!form.errors.amount"
                    />
                    <InputError :message="form.errors.amount"/>
                </div>

                <!-- Descriptions -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="description"
                        :value="$t('public.description')"
                    />
                    <Textarea
                        id="description"
                        type="text"
                        class="w-full h-20"
                        v-model="form.description"
                        :placeholder="$t('public.enter_description')"
                        :invalid="!!form.errors.description"
                        rows="5"
                        cols="30"
                    />
                    <InputError :message="form.errors.description"/>
                </div>
            </div>
            <div class="flex gap-3 w-full justify-end">
                <Button
                    type="button"
                    variant="transparent"
                    class="w-full md:w-auto justify-center"
                    @click="closeDialog"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary"
                    :disabled="form.processing"
                    class="w-full md:w-auto justify-center"
                    @click.prevent="submitForm"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </form>
</template>
