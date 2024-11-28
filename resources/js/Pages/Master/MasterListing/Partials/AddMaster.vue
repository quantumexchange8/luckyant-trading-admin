<script setup>
import Button from "primevue/button";
import {
    IconUserPlus,
    IconArrowNarrowLeft,
    IconArrowNarrowRight
} from "@tabler/icons-vue"
import {ref} from "vue";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import StepPanels from 'primevue/steppanels';
import Step from 'primevue/step';
import StepPanel from 'primevue/steppanel';
import {useForm, usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import MultiSelect from 'primevue/multiselect';
import ManagementFeeSetting from "@/Pages/Master/MasterListing/Partials/ManagementFeeSetting.vue";
import RadioButton from "primevue/radiobutton";

const visible = ref(false);
const activeStep = ref(2);
const totalSteps = 3;
const loadingUsers = ref(false);
const users = ref([]);
const selectedUser = ref();
const selectedLeaders = ref();
const managementFee = ref();

const openDialog = () => {
    visible.value = true;
    getLeaders();
    getSettlementPeriods();
}

const getLeaders = async () => {
    loadingUsers.value = true;
    try {
        const response = await axios.get('/getLeaders');
        users.value = response.data;
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loadingUsers.value = false;
    }
};

const initialFormState = {
    step: '',
    leader: '',
    type: '',
    category: 'copy_trade',
    strategy_type: '',
    estimated_monthly_return: '',
    estimated_lot_size: '',
    max_drawdown: '',
    total_fund: null,
    max_fund_percentage: null,
    total_subscribers: '',
    min_investment: null,
    sharing_profit: '',
    market_profit: '',
    company_profit: '',
    join_period: null,
    roi_period: '',
    delivery_requirement: '',
    is_public: 1,
    can_top_up: '',
    can_revoke: '',
    leaders: null,
    management_fee: '',
};

const form = useForm({ ...initialFormState });

const resetForm = () => {
    Object.keys(initialFormState).forEach(key => {
        form[key] = initialFormState[key];
    });
};

const handleContinue = () => {
    form.step = activeStep.value;
    form.leader = selectedUser.value;
    form.leaders = selectedLeaders.value;
    form.management_fee = managementFee.value;
    form.post(route('master.addMaster'), {
        onSuccess: () => {
            if (activeStep.value === totalSteps) {
                closeDialog();
            } else {
                activeStep.value += 1;
            }
        }
    });
};

const closeDialog = () => {
    visible.value = false;
    resetForm();
    activeStep.value = 1;
};

const roiOptions = ref([]);
const loadingRoiOptions = ref(false);

const getSettlementPeriods = async () => {
    loadingRoiOptions.value = true;
    try {
        const response = await axios.get('/getSettlementPeriods');
        roiOptions.value = response.data;
        form.roi_period = 30;
    } catch (error) {
        console.error('Error fetching groupLeaders:', error);
    } finally {
        loadingRoiOptions.value = false;
    }
};
</script>

<template>
    <Button
        type="button"
        size="small"
        class="flex gap-2"
        @click="openDialog"
    >
        <IconUserPlus size="20" />
        {{ $t('public.add_master') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.add_master')"
        class="dialog-xs md:dialog-lg"
    >
        <form @submit.prevent>
            <Stepper v-model:value="activeStep" linear>
                <StepList>
                    <Step :value="1">{{ $t('public.profile') }}</Step>
                    <Step :value="2">{{ $t('public.settings') }}</Step>
                    <Step :value="3">{{ $t('public.fee') }}</Step>
                </StepList>
                <StepPanels>
                    <StepPanel :value="1">
                        <div class="flex flex-col gap-6 items-center self-stretch">
                            <div class="flex flex-col gap-3 items-center self-stretch">
                                <span class="font-bold text-sm text-surface-950 dark:text-white w-full text-left">{{ $t('public.basics') }}</span>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="type"
                                            :value="$t('public.type')"
                                            :invalid="!!form.errors.category"
                                        />
                                        <div class="flex flex-wrap gap-4">
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.category"
                                                    inputId="type_copy_trade"
                                                    value="copy_trade"
                                                />
                                                <InputLabel for="type_copy_trade" class="ml-2">{{ $t('public.copy_trade') }}</InputLabel>
                                            </div>
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.category"
                                                    inputId="type_pamm"
                                                    value="pamm"
                                                />
                                                <InputLabel for="type_pamm" class="ml-2">{{ $t('public.pamm') }}</InputLabel>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.category" />
                                    </div>

                                    <!-- Copy Trading Type -->
                                    <div
                                        class="flex flex-col items-start gap-1 self-stretch"
                                    >
                                        <InputLabel
                                            for="strategy_type"
                                            :value="$t('public.strategy_type')"
                                            :invalid="!!form.errors.strategy_type"
                                        />
                                        <div class="flex flex-wrap gap-4">
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.strategy_type"
                                                    inputId="strategy_type_hofi"
                                                    value="HOFI"
                                                />
                                                <InputLabel for="strategy_type_hofi" class="ml-2">HOFI</InputLabel>
                                            </div>
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.strategy_type"
                                                    inputId="strategy_type_alpha"
                                                    value="Alpha"
                                                />
                                                <InputLabel for="strategy_type_alpha" class="ml-2">Alpha</InputLabel>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.strategy_type" />
                                    </div>

                                    <!-- PAMM Type -->
                                    <div
                                        v-if="form.category === 'pamm'"
                                        class="flex flex-col items-start gap-1 self-stretch"
                                    >
                                        <InputLabel
                                            for="pamm_type"
                                            :value="$t('public.pamm_type')"
                                            :invalid="!!form.errors.type"
                                        />
                                        <div class="flex flex-wrap gap-4">
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.type"
                                                    inputId="type_esg"
                                                    value="ESG"
                                                />
                                                <InputLabel for="type_esg" class="ml-2">{{ $t('public.esg') }}</InputLabel>
                                            </div>
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.type"
                                                    inputId="type_standard_group"
                                                    value="StandardGroup"
                                                />
                                                <InputLabel for="type_standard_group" class="ml-2">{{ $t('public.standard') }}</InputLabel>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.type" />
                                    </div>

                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="leader"
                                            :value="$t('public.leader')"
                                            :invalid="!!form.errors.leader"
                                        />
                                        <Select
                                            v-model="selectedUser"
                                            :options="users"
                                            filter
                                            optionLabel="name"
                                            :placeholder="$t('public.select_leader')"
                                            class="w-full"
                                            :loading="loadingUsers"
                                            :invalid="!!form.errors.leader"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div>{{ slotProps.value.name }}</div>
                                                </div>
                                                <span v-else>{{ slotProps.placeholder }}</span>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center gap-1 w-full truncate">
                                                    <span>{{ slotProps.option.name }}</span>
                                                </div>
                                            </template>
                                        </Select>
                                        <InputError :message="form.errors.leader" />
                                    </div>

                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="estimated_monthly_return"
                                            :invalid="!!form.errors.estimated_monthly_return"
                                        >
                                            {{ $t('public.estimated_monthly_returns') }}
                                        </InputLabel>
                                        <InputText
                                            id="estimated_monthly_return"
                                            type="text"
                                            class="block w-full"
                                            v-model="form.estimated_monthly_return"
                                            :placeholder="$t('public.estimated_monthly_return_placeholder')"
                                            :invalid="!!form.errors.estimated_monthly_return"
                                        />
                                        <InputError :message="form.errors.estimated_monthly_return" />
                                    </div>

                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="estimated_lot_size"
                                            :invalid="!!form.errors.estimated_lot_size"
                                        >
                                            {{ $t('public.estimated_lot_size') }}
                                        </InputLabel>
                                        <InputText
                                            id="estimated_lot_size"
                                            type="text"
                                            class="block w-full"
                                            v-model="form.estimated_lot_size"
                                            :placeholder="$t('public.estimated_lot_size_placeholder')"
                                            :invalid="!!form.errors.estimated_lot_size"
                                        />
                                        <InputError :message="form.errors.estimated_lot_size" />
                                    </div>

                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="max_drawdown"
                                            :invalid="!!form.errors.max_drawdown"
                                        >
                                            {{ $t('public.max_drawdown') }}
                                        </InputLabel>
                                        <InputText
                                            id="max_drawdown"
                                            type="text"
                                            class="block w-full"
                                            v-model="form.max_drawdown"
                                            :placeholder="$t('public.max_drawdown_placeholder')"
                                            :invalid="!!form.errors.max_drawdown"
                                        />
                                        <InputError :message="form.errors.max_drawdown" />
                                    </div>

                                    <!-- Total Fund -->
                                    <div
                                        v-if="form.category === 'copy_trade'"
                                        class="flex flex-col items-start gap-1 self-stretch"
                                    >
                                        <InputLabel
                                            for="total_fund"
                                            :value="$t('public.total_fund')"
                                            :invalid="!!form.errors.total_fund"
                                        />
                                        <InputNumber
                                            v-model="form.total_fund"
                                            inputId="total_fund"
                                            class="w-full"
                                            :min="0"
                                            :step="100"
                                            fluid
                                            mode="currency"
                                            currency="USD"
                                            locale="en-US"
                                            placeholder="$0.00"
                                            :invalid="!!form.errors.total_fund"
                                        />
                                        <InputError :message="form.errors.total_fund" />
                                    </div>

                                    <!-- Max Fund Percentage -->
                                    <div
                                        v-if="form.strategy_type === 'Alpha'"
                                        class="flex flex-col items-start gap-1 self-stretch"
                                    >
                                        <InputLabel
                                            for="max_fund_percentage"
                                            :value="$t('public.max_fund_percentage')"
                                            :invalid="!!form.errors.max_fund_percentage"
                                        />
                                        <InputNumber
                                            v-model="form.max_fund_percentage"
                                            inputId="max_fund_percentage"
                                            class="w-full"
                                            :min="0"
                                            fluid
                                            placeholder="eg. 20%"
                                            suffix="%"
                                            :invalid="!!form.errors.max_fund_percentage"
                                        />
                                        <InputError :message="form.errors.max_fund_percentage" />
                                    </div>

                                    <!-- Total Subscribers -->
                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="total_subscribers"
                                            :value="$t('public.total_subscribers')"
                                            :invalid="!!form.errors.total_subscribers"
                                        />
                                        <InputText
                                            id="total_subscribers"
                                            type="text"
                                            class="block w-full"
                                            v-model="form.total_subscribers"
                                            :placeholder="$t('public.total_subscribers_placeholder')"
                                            :invalid="!!form.errors.total_subscribers"
                                        />
                                        <InputError :message="form.errors.total_subscribers" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 items-center self-stretch">
                                <span class="font-bold text-sm text-surface-950 dark:text-white w-full text-left">{{ $t('public.upload_image') }}</span>
                                <div class="flex flex-col items-start gap-3 self-stretch">
                                    <span class="text-xs text-gray-500">{{ $t('public.upload_image_caption') }}</span>
                                    <div class="flex flex-col gap-3">
                                        <Button
                                            type="button"
                                            severity="info"
                                        >
                                            {{ $t('public.browse') }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex pt-6 justify-end">
                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                @click="handleContinue"
                                :disabled="form.processing"
                            >
                                {{ $t('public.next') }}
                                <IconArrowNarrowRight size="20" stroke-witdth="1.5" />
                            </Button>
                        </div>
                    </StepPanel>

                    <!-- Step 2 -->
                    <StepPanel :value="2">
                        <div class="flex flex-col items-center gap-8 self-stretch">
                            <div class="flex flex-col items-center gap-3 self-stretch">
                                <span class="self-stretch text-surface-950 dark:text-white text-sm font-bold">{{ $t('public.join_setting') }}</span>
                                <div class="w-full grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <div class="flex flex-col items-start gap-1 self-stretch md:flex-grow">
                                        <InputLabel
                                            for="min_investment"
                                            :value="$t('public.min_investment')"
                                            :invalid="!!form.errors.min_investment"
                                        />
                                        <InputNumber
                                            v-model="form.min_investment"
                                            inputId="min_investment"
                                            class="w-full"
                                            :min="0"
                                            :step="100"
                                            fluid
                                            placeholder="$ 1,000.00"
                                            mode="currency"
                                            currency="USD"
                                            locale="en-US"
                                            :invalid="!!form.errors.min_investment"
                                        />
                                        <InputError :message="form.errors.min_investment" />
                                    </div>

                                    <!-- Sharing Profit -->
                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                for="sharing_profit"
                                                :value="$t('public.shared')"
                                                :invalid="!!form.errors.sharing_profit"
                                            />
                                            <InputNumber
                                                v-model="form.sharing_profit"
                                                inputId="sharing_profit"
                                                class="w-full"
                                                :min="0"
                                                fluid
                                                suffix="%"
                                                :invalid="!!form.errors.sharing_profit"
                                            />
                                            <InputError :message="form.errors.sharing_profit" />
                                        </div>

                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                for="market_profit"
                                                :value="$t('public.market')"
                                                :invalid="!!form.errors.market_profit"
                                            />
                                            <InputNumber
                                                v-model="form.market_profit"
                                                inputId="market_profit"
                                                class="w-full"
                                                :min="0"
                                                fluid
                                                suffix="%"
                                                :invalid="!!form.errors.market_profit"
                                            />
                                            <InputError :message="form.errors.market_profit" />
                                        </div>

                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                for="company_profit"
                                                :value="$t('public.company')"
                                                :invalid="!!form.errors.company_profit"
                                            />
                                            <InputNumber
                                                v-model="form.company_profit"
                                                inputId="company_profit"
                                                class="w-full"
                                                :min="0"
                                                fluid
                                                suffix="%"
                                                :invalid="!!form.errors.company_profit"
                                            />
                                            <InputError :message="form.errors.company_profit" />
                                        </div>
                                    </div>

                                    <!-- ROI Period -->
                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="roi_period"
                                            :value="$t('public.roi_period')"
                                            :invalid="!!form.errors.roi_period"
                                        />
                                        <Select
                                            v-model="form.roi_period"
                                            :options="roiOptions"
                                            optionLabel="value"
                                            optionValue="value"
                                            :placeholder="$t('public.select_days')"
                                            class="w-full"
                                            :invalid="!!form.errors.roi_period"
                                        >
                                            <template #value="slotProps">
                                                <div v-if="slotProps.value" class="flex items-center">
                                                    <div>{{ slotProps.value }} {{ $t('public.days') }}</div>
                                                </div>
                                                <span v-else>{{ slotProps.placeholder }}</span>
                                            </template>
                                            <template #option="slotProps">
                                                <div class="flex items-center gap-1 max-w-[220px] truncate">
                                                    <span>{{ slotProps.option.value }} {{ $t('public.days') }}</span>
                                                </div>
                                            </template>
                                        </Select>
                                        <InputError :message="form.errors.roi_period" />
                                    </div>

                                    <!-- Join Period -->
                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="join_period"
                                            :value="$t('public.join_period')"
                                            :invalid="!!form.errors.join_period"
                                        />
                                        <InputNumber
                                            v-model="form.join_period"
                                            inputId="join_period"
                                            class="w-full"
                                            :min="0"
                                            fluid
                                            :placeholder="$t('public.join_period_placeholder')"
                                            :suffix="` ${$t('public.days')}`"
                                            :invalid="!!form.errors.join_period"
                                        />
                                        <InputError :message="form.errors.join_period" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-center gap-3 self-stretch">
                                <span class="self-stretch text-surface-950 dark:text-white text-sm font-bold">{{ $t('public.master_settings') }}</span>
                                <div class="w-full grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <!-- Visible To -->
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

                                    <!-- Master Topup Status -->
                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="top_up_status"
                                            :value="$t('public.top_up')"
                                            :invalid="!!form.errors.can_top_up"
                                        />
                                        <div class="flex flex-wrap gap-4">
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.can_top_up"
                                                    inputId="can_top_up_true"
                                                    :value="1"
                                                />
                                                <InputLabel for="can_top_up_true" class="ml-2">{{ $t('public.yes') }}</InputLabel>
                                            </div>
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.can_top_up"
                                                    inputId="can_top_up_false"
                                                    :value="0"
                                                />
                                                <InputLabel for="can_top_up_false" class="ml-2">{{ $t('public.no') }}</InputLabel>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.can_top_up" />
                                    </div>

                                    <!-- Master Revoke Status -->
                                    <div class="flex flex-col items-start gap-1 self-stretch">
                                        <InputLabel
                                            for="revoke_status"
                                            :value="$t('public.revoke')"
                                            :invalid="!!form.errors.can_revoke"
                                        />
                                        <div class="flex flex-wrap gap-4">
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.can_revoke"
                                                    inputId="can_revoke_true"
                                                    :value="1"
                                                />
                                                <InputLabel for="can_revoke_true" class="ml-2">{{ $t('public.yes') }}</InputLabel>
                                            </div>
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.can_revoke"
                                                    inputId="can_revoke_false"
                                                    :value="0"
                                                />
                                                <InputLabel for="can_revoke_false" class="ml-2">{{ $t('public.no') }}</InputLabel>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.can_revoke" />
                                    </div>

                                    <!-- Delivery Requirement -->
                                    <div
                                        v-if="form.category === 'PAMM' && form.type === 'ESG'"
                                        class="flex flex-col items-start gap-1 self-stretch"
                                    >
                                        <InputLabel
                                            for="delivery_requirement"
                                            :value="$t('public.delivery_requirement')"
                                            :invalid="!!form.errors.delivery_requirement"
                                        />
                                        <div class="flex flex-wrap gap-4">
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.delivery_requirement"
                                                    inputId="delivery_requirement_true"
                                                    :value="1"
                                                />
                                                <InputLabel for="delivery_requirement_true" class="ml-2">{{ $t('public.required') }}</InputLabel>
                                            </div>
                                            <div class="flex items-center">
                                                <RadioButton
                                                    v-model="form.delivery_requirement"
                                                    inputId="delivery_requirement_false"
                                                    :value="0"
                                                />
                                                <InputLabel for="delivery_requirement_false" class="ml-2">{{ $t('public.not_required') }}</InputLabel>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.delivery_requirement" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 flex justify-between items-center self-stretch w-full">
                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                :disabled="form.processing"
                                @click="activeStep = 1"
                            >
                                <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                                {{ $t('public.back') }}
                            </Button>

                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                @click="handleContinue"
                                :disabled="form.processing"
                            >
                                {{ $t('public.next') }}
                                <IconArrowNarrowRight size="20" stroke-witdth="1.5" />
                            </Button>
                        </div>
                    </StepPanel>

                    <!-- Step 3 -->
                    <StepPanel :value="3">
                        <div class="flex flex-col items-center gap-3 self-stretch">
                            <span class="self-stretch text-surface-950 dark:text-white text-sm font-bold">{{ $t('public.management_fee') }}</span>

                            <ManagementFeeSetting
                                @get:management_fee="managementFee = $event"
                            />
                        </div>

                        <div class="pt-6 flex justify-between items-center self-stretch w-full">
                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                :disabled="form.processing"
                                @click="activeStep = 2"
                            >
                                <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                                {{ $t('public.back') }}
                            </Button>

                            <Button
                                size="small"
                                @click="handleContinue"
                                :disabled="form.processing"
                                class="w-full md:w-auto"
                            >
                                {{ $t('public.create') }}
                            </Button>
                        </div>
                    </StepPanel>
                </StepPanels>
            </Stepper>
        </form>
    </Dialog>
</template>
