<script setup>
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import InputText from "primevue/inputtext";
import {useForm} from "@inertiajs/vue3";
import RadioButton from "primevue/radiobutton";
import InputNumber from "primevue/inputnumber";
import Select from "primevue/select";
import {onMounted, ref} from "vue";
import Button from "primevue/button"
import MultiSelect from "primevue/multiselect";
import {IconX} from "@tabler/icons-vue";
import Image from "primevue/image";

const props = defineProps({
    master: Object
})

const emit = defineEmits(['update:visible'])

const form = useForm({
    master_id: props.master.id,
    category: props.master.category,
    type: props.master.type,
    max_fund_percentage: props.master.max_fund_percentage,
    min_investment: Number(props.master.min_join_equity),
    sharing_profit: Number(props.master.sharing_profit),
    market_profit: Number(props.master.market_profit),
    company_profit: Number(props.master.company_profit),
    join_period: props.master.join_period,
    roi_period: props.master.roi_period,
    extra_fund: Number(props.master.extra_fund),
    estimated_monthly_returns: props.master.estimated_monthly_returns,
    estimated_lot_size: props.master.estimated_lot_size,
    total_subscribers: props.master.total_subscribers,
    max_drawdown: props.master.max_drawdown,
    is_public: props.master.is_public,
    can_top_up: props.master.can_top_up,
    can_revoke: props.master.can_revoke,
    delivery_requirement: props.master.delivery_requirement,
    leaders: '',
    master_logo: null,
})

const roiOptions = ref([]);
const loadingRoiOptions = ref(false);

const getSettlementPeriods = async () => {
    loadingRoiOptions.value = true;
    try {
        const response = await axios.get('/getSettlementPeriods');
        roiOptions.value = response.data;
        const defaultOption = response.data.find(option => option.value === props.master.roi_period);
        form.roi_period = defaultOption ? defaultOption.value : response.data[0]?.value;
    } catch (error) {
        console.error('Error fetching groupLeaders:', error);
    } finally {
        loadingRoiOptions.value = false;
    }
};

const selectedLeaders = ref();
const leaders = ref();
const loadingLeaders = ref(false);

const getLeaders = async () => {
    loadingLeaders.value = true;
    try {
        const response = await axios.get('/setting/getLeadersSel');
        leaders.value = response.data;

        const selectedLeaderUserIds = props.master.leaders.map(leader => leader.user_id);
        selectedLeaders.value = leaders.value.filter(leader => selectedLeaderUserIds.includes(leader.id));
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

onMounted(() => {
    getSettlementPeriods();
    getLeaders();
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
        form.master_logo = event.target.files[0];
    } else {
        selectedSlip.value = null;
    }
};

const removeSlip = () => {
    selectedSlip.value = null;
    form.master_logo = '';
};


const submitForm = () => {
    form.leaders = selectedLeaders.value;
    form.post(route('master.updateMaster'), {
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
    <form>
        <div class="flex flex-col gap-6 items-center self-stretch">
            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.basics') }}</span>
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

                    <!-- Extra Fund -->
                    <div
                        class="flex flex-col items-start gap-1 self-stretch"
                    >
                        <InputLabel
                            for="extra_fund"
                            :value="$t('public.extra_fund')"
                            :invalid="!!form.errors.extra_fund"
                        />
                        <InputNumber
                            v-model="form.extra_fund"
                            inputId="extra_fund"
                            class="w-full"
                            :min="0"
                            :step="100"
                            fluid
                            mode="currency"
                            currency="USD"
                            locale="en-US"
                            :invalid="!!form.errors.extra_fund"
                        />
                        <InputError :message="form.errors.extra_fund" />
                    </div>

                    <!-- Estimated Monthly Return -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="estimated_monthly_returns"
                            :value="$t('public.estimated_monthly_returns')"
                            :invalid="!!form.errors.estimated_monthly_returns"
                        />
                        <InputText
                            id="estimated_monthly_return"
                            type="text"
                            class="block w-full"
                            v-model="form.estimated_monthly_returns"
                            :placeholder="$t('public.estimated_monthly_return_placeholder')"
                            :invalid="!!form.errors.estimated_monthly_returns"
                        />
                        <InputError :message="form.errors.estimated_monthly_returns" />
                    </div>

                    <!-- Estimated Lot Size -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="estimated_lot_size"
                            :value="$t('public.estimated_lot_size')"
                            :invalid="!!form.errors.estimated_lot_size"
                        />
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

                    <!-- Total Subscribers -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="total_subscribers"
                            :value="$t('public.total_subscribers')"
                            :invalid="!!form.errors.total_subscribers"
                        />
                        <InputNumber
                            v-model="form.total_subscribers"
                            inputId="total_subscribers"
                            class="w-full"
                            :min="0"
                            fluid
                            :invalid="!!form.errors.total_subscribers"
                        />
                        <InputError :message="form.errors.total_subscribers" />
                    </div>

                    <!-- Max Drawdown -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="max_drawdown"
                            :value="$t('public.max_drawdown')"
                            :invalid="!!form.errors.max_drawdown"
                        />
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

                    <!-- Visible To -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="leaders"
                            :value="$t('public.visible_to')"
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
                    <div class="flex flex-col items-start gap-1 self-stretch">
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

            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.join_setting') }}</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <!-- Min Investment -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
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
                            autofocus
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

            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.upload_image') }}</span>
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <span class="text-xs text-gray-500">{{ $t('public.upload_image_caption') }}</span>
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <div class="flex flex-col gap-3">
                            <input
                                ref="masterLogoInput"
                                id="master_logo"
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
                        <InputError :message="form.errors.master_logo" />

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
                </div>
            </div>
        </div>
        <div class="pt-6 flex gap-5 justify-end items-center self-stretch w-full">
            <Button
                type="button"
                :label="$t('public.cancel')"
                severity="secondary"
                variant="outlined"
                @click="closeDialog"
            />

            <Button
                type="submit"
                :label="$t('public.confirm')"
                @click.prevent="submitForm"
            />
        </div>
    </form>
</template>
