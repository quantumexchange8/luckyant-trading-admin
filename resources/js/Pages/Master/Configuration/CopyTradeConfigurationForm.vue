<script setup>
import Input from "@/Components/Input.vue";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";
import BaseListbox from "@/Components/BaseListbox.vue";

const props = defineProps({
    masterConfigurations: Object,
    settlementPeriodSel: Array,
})

const form = useForm({
    master_id: props.masterConfigurations.id,
    min_join_equity: props.masterConfigurations.min_join_equity,
    sharing_profit: props.masterConfigurations.sharing_profit,
    market_profit: props.masterConfigurations.market_profit,
    company_profit: props.masterConfigurations.company_profit,
    subscription_fee: props.masterConfigurations.subscription_fee,
    delivery_requirement: '',
    eta_montly_return: props.masterConfigurations.estimated_monthly_returns,
    eta_lot_size: props.masterConfigurations.estimated_lot_size,
    join_period: props.masterConfigurations.join_period,
    total_fund: props.masterConfigurations.total_fund,
    roi_period: props.masterConfigurations.roi_period,
    total_subscriber: props.masterConfigurations.total_subscribers,
    max_drawdown: props.masterConfigurations.max_drawdown,
    is_public: '',
    category: '',
    type: '',
    en_tnc_pdf: null,
    cn_tnc_pdf: null,
    en_tree_pdf: null,
    cn_tree_pdf: null,
})

const plans = [
    {
        name: 'Enable',
        value: 1,
    },
    {
        name: 'Disable',
        value: 0,
    },
]

const getSelectedPlan = (status) => {
    return plans.find(plan => plan.value === status);
}
const selected = ref(getSelectedPlan(props.masterConfigurations.signal_status));

const masterTypes = [
    {
        name: 'Copy Trade',
        value: 'copy_trade',
    },
    {
        name: 'PAMM',
        value: 'pamm',
    },
]

const getSelectedMasterTypes = (master_type) => {
    return masterTypes.find(plan => plan.value === master_type);
}
const selectedMasterTypes = ref(getSelectedMasterTypes(props.masterConfigurations.category));

const pammTypes = [
    {
        name: 'Standard',
        value: 'Standard',
    },
    {
        name: 'ESG',
        value: 'ESG',
    },
]

const getSelectedPammTypes = (pamm_type) => {
    if (pamm_type === 'CopyTrade') {
        pamm_type = 'Standard'
    }

    console.log(pamm_type)
    return pammTypes.find(plan => plan.value === pamm_type);
}

const selectedPammTypes = ref(getSelectedPammTypes(props.masterConfigurations.type));

const publicStatus = [
    {
        name: 'Public',
        value: 1,
    },
    {
        name: 'Private',
        value: 0,
    },
]

const getSelectedPublicStatus = (public_status) => {
    return publicStatus.find(plan => plan.value === public_status);
}
const selectedPublicStatus = ref(getSelectedPublicStatus(props.masterConfigurations.is_public));

const requireDeliverySel = [
    {
        name: 'Required',
        value: 1,
    },
    {
        name: 'Not Required',
        value: 0,
    },
]

const getRequireDeliverySel = (status) => {
    return requireDeliverySel.find(delivery => delivery.value === status);
}
const selectedRequiredDelivery = ref(getRequireDeliverySel(props.masterConfigurations.delivery_requirement));

// en pdf
const selectedEnTncPdf = ref(null);

const onEnTncPdfChange = (event) => {
    const planLogoInput = event.target;
    const file = planLogoInput.files[0];

    if(file) {
        //Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedEnTncPdf.value = reader.result;
        };
        reader.readAsDataURL(file);
        form.en_tnc_pdf = file;
    } else {
        selectedEnTncPdf.value = null;
    }
}

// cn pdf
const selectedCnTncPdf = ref(null);

const onCnTncPdfChange = (event) => {
    const planLogoInput = event.target;
    const file = planLogoInput.files[0];

    if(file) {
        //Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedCnTncPdf.value = reader.result;
        };
        reader.readAsDataURL(file);
        form.cn_tnc_pdf = file;
    } else {
        selectedCnTncPdf.value = null;
    }
}

// cn pdf
const selectedEnTreePdf = ref(null);

const onEnTreePdfChange = (event) => {
    const planLogoInput = event.target;
    const file = planLogoInput.files[0];

    if(file) {
        //Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedEnTreePdf.value = reader.result;
        };
        reader.readAsDataURL(file);
        form.en_tree_pdf = file;
    } else {
        selectedEnTreePdf.value = null;
    }
}

// cn pdf
const selectedCnTreePdf = ref(null);

const onCnTreePdfChange = (event) => {
    const planLogoInput = event.target;
    const file = planLogoInput.files[0];

    if(file) {
        //Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedCnTreePdf.value = reader.result;
        };
        reader.readAsDataURL(file);
        form.cn_tree_pdf = file;
    } else {
        selectedCnTreePdf.value = null;
    }
}

const submit = () => {
    form.delivery_requirement = selectedRequiredDelivery.value.value;
    form.is_public = selectedPublicStatus.value.value;
    form.category = selectedMasterTypes.value.value;
    form.type = selectedMasterTypes.value.value === 'pamm' ? selectedPammTypes.value.value : '';
    form.post(route('master.updateMasterConfiguration'))
}
</script>

<template>
    <div class="flex flex-col items-start gap-5 bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
        <div class="flex items-center gap-3">
            <div class="text-lg font-semibold">
                Master Setting
            </div>
        </div>
        <form class="w-full" @submit.prevent="submit">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                <div class="space-y-2 sm:col-span-2">
                    <Label
                        for="master_type"
                        value="Master Type"
                    />
                    <RadioGroup v-model="selectedMasterTypes">
                        <RadioGroupLabel class="sr-only">Master Types</RadioGroupLabel>
                        <div class="flex gap-4 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(masterType, index) in masterTypes"
                                :key="index"
                                :value="masterType"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                            active
                                                ? 'ring-0 ring-white ring-offset-0'
                                                : '',
                                            checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                                                    {{ masterType.name }}
                                                </div>
                                            </RadioGroupLabel>
                                        </div>
                                    </div>
                                </div>
                            </RadioGroupOption>
                        </div>
                    </RadioGroup>
                </div>
                <div
                    v-if="selectedMasterTypes.value === 'pamm'"
                    class="space-y-2 sm:col-span-2"
                >
                    <Label
                        for="pamm_type"
                        value="PAMM Type"
                    />
                    <RadioGroup v-model="selectedPammTypes">
                        <RadioGroupLabel class="sr-only">PAMM Types</RadioGroupLabel>
                        <div class="flex gap-4 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(pammType, index) in pammTypes"
                                :key="index"
                                :value="pammType"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                            active
                                                ? 'ring-0 ring-white ring-offset-0'
                                                : '',
                                            checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                                                    {{ pammType.name }}
                                                </div>
                                            </RadioGroupLabel>
                                        </div>
                                    </div>
                                </div>
                            </RadioGroupOption>
                        </div>
                    </RadioGroup>
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <Label
                                for="sharing_profit"
                                value="Sharing Profit (%)"
                            />
                            <Input
                                id="sharing_profit"
                                type="number"
                                min="0"
                                placeholder="60%"
                                class="block w-full"
                                v-model="form.sharing_profit"
                                :invalid="form.errors.sharing_profit"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="market_profit"
                                value="Market Profit (%)"
                            />
                            <Input
                                id="market_profit"
                                type="number"
                                min="0"
                                placeholder="20%"
                                class="block w-full"
                                v-model="form.market_profit"
                                :invalid="form.errors.market_profit"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="company_profit"
                                value="Company Profit (%)"
                            />
                            <Input
                                id="company_profit"
                                type="number"
                                min="0"
                                placeholder="20%"
                                class="block w-full"
                                v-model="form.company_profit"
                                :invalid="form.errors.company_profit"
                            />
                        </div>
                    </div>
                    <InputError :message="form.errors.sharing_profit" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="min_join_equity"
                        value="Minimum Equity to Join"
                    />
                    <Input
                        id="min_join_equity"
                        type="number"
                        min="0"
                        placeholder="$ 0.00"
                        class="block w-full"
                        v-model="form.min_join_equity"
                        :invalid="form.errors.min_join_equity"
                    />
                    <InputError :message="form.errors.min_join_equity" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="roi_period"
                        value="ROI Period"
                    />
                    <BaseListbox
                        v-model="form.roi_period"
                        :options="settlementPeriodSel"
                        :error="!!form.errors.roi_period"
                    />
                    <InputError :message="form.errors.roi_period" />
                </div>
<!--                <div class="space-y-2">-->
<!--                    <Label-->
<!--                        for="subscription_fee"-->
<!--                        value="Subscription Fee (Month)"-->
<!--                    />-->
<!--                    <Input-->
<!--                        id="subscription_fee"-->
<!--                        type="number"-->
<!--                        min="0"-->
<!--                        placeholder="$ 0.00"-->
<!--                        class="block w-full"-->
<!--                        v-model="form.subscription_fee"-->
<!--                        :invalid="form.errors.subscription_fee"-->
<!--                    />-->
<!--                    <InputError :message="form.errors.subscription_fee" />-->
<!--                </div>-->
                <div class="space-y-2">
                    <Label
                        for="join_period"
                        value="Join Period (Days)"
                    />
                    <Input
                        id="join_period"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="block w-full"
                        v-model="form.join_period"
                        :invalid="form.errors.join_period"
                    />
                    <InputError :message="form.errors.join_period" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="total_fund"
                        value="Total Fund"
                    />
                    <Input
                        id="total_fund"
                        type="number"
                        min="0"
                        placeholder="$ 0.00"
                        class="block w-full"
                        v-model="form.total_fund"
                        :invalid="form.errors.total_fund"
                    />
                    <InputError :message="form.errors.total_fund" />
                </div>

                <div class="space-y-2">
                    <Label
                        for="eta_montly_return"
                        value="Estimated Monthly Return (%)"
                    />
                    <Input
                        id="eta_montly_return"
                        type="text"
                        placeholder="%"
                        class="block w-full"
                        v-model="form.eta_montly_return"
                        :invalid="form.errors.eta_montly_return"
                    />
                    <InputError :message="form.errors.eta_montly_return" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="eta_lot_size"
                        value="Estimated Lot Size"
                    />
                    <Input
                        id="eta_lot_size"
                        type="text"
                        placeholder="0"
                        class="block w-full"
                        v-model="form.eta_lot_size"
                        :invalid="form.errors.eta_lot_size"
                    />
                    <InputError :message="form.errors.eta_lot_size" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="total_subscriber"
                        value="Total Subscribers"
                    />
                    <Input
                        id="total_subscriber"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="block w-full"
                        v-model="form.total_subscriber"
                        :invalid="form.errors.total_subscriber"
                    />
                    <InputError :message="form.errors.total_subscriber" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="max_drawdown"
                        value="Max Drawdown"
                    />
                    <Input
                        id="max_drawdown"
                        type="text"
                        placeholder="%"
                        class="block w-full"
                        v-model="form.max_drawdown"
                        :invalid="form.errors.max_drawdown"
                    />
                    <InputError :message="form.errors.max_drawdown" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="master_status"
                        value="Master Status"
                    />
                    <RadioGroup v-model="selectedPublicStatus">
                        <RadioGroupLabel class="sr-only">Master Status</RadioGroupLabel>
                        <div class="flex gap-4 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(public_status, index) in publicStatus"
                                :key="index"
                                :value="public_status"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                            active
                                                ? 'ring-0 ring-white ring-offset-0'
                                                : '',
                                            checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                                                    {{ public_status.name }}
                                                </div>
                                            </RadioGroupLabel>
                                        </div>
                                    </div>
                                </div>
                            </RadioGroupOption>
                        </div>
                    </RadioGroup>
                </div>
                <div class="space-y-2">
                    <Label
                        for="delivery_requirement"
                        value="Delivery Requirement"
                    />
                    <RadioGroup v-model="selectedRequiredDelivery">
                        <RadioGroupLabel class="sr-only">Delivery Requirement</RadioGroupLabel>
                        <div class="flex gap-4 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(plan, index) in requireDeliverySel"
                                :key="index"
                                :value="plan"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                            active
                                                ? 'ring-0 ring-white ring-offset-0'
                                                : '',
                                            checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                </div>

                <!-- upload en tnc pdf-->
                <div class="space-y-2">
                    <Label
                        for="en_tnc_pdf"
                        value="PAMM TNC (En)"
                    />
                    <input
                        ref="enTncPdf"
                        id="en_tnc_pdf"
                        type="file"
                        class="hidden"
                        accept="application/pdf"
                        @change="onEnTncPdfChange"
                    />
                    <Button
                        type="button"
                        variant="gray"
                        @click="$refs.enTncPdf.click()"
                        class="justify-center gap-2"
                    >
                        <span>Browse</span>
                    </Button>
                </div>

                <!-- upload cn tnc pdf-->
                <div class="space-y-2">
                    <Label
                        for="cn_tnc_pdf"
                        value="PAMM TNC (Cn)"
                    />
                    <input
                        ref="cnTncPdf"
                        id="cn_tnc_pdf"
                        type="file"
                        class="hidden"
                        accept="application/pdf"
                        @change="onCnTncPdfChange"
                    />
                    <Button
                        type="button"
                        variant="gray"
                        @click="$refs.cnTncPdf.click()"
                        class="justify-center gap-2"
                    >
                        <span>Browse</span>
                    </Button>
                </div>

                <!-- upload en tree pdf-->
                <div class="space-y-2">
                    <Label
                        for="en_tree_pdf"
                        value="Tree TNC (En)"
                    />
                    <input
                        ref="enTreePdf"
                        id="en_tree_pdf"
                        type="file"
                        class="hidden"
                        accept="application/pdf"
                        @change="onEnTreePdfChange"
                    />
                    <Button
                        type="button"
                        variant="gray"
                        @click="$refs.enTreePdf.click()"
                        class="justify-center gap-2"
                    >
                        <span>Browse</span>
                    </Button>
                </div>

                <!-- upload en tree pdf-->
                <div class="space-y-2">
                    <Label
                        for="cn_tree_pdf"
                        value="Tree TNC (En)"
                    />
                    <input
                        ref="cnTreePdf"
                        id="cn_tree_pdf"
                        type="file"
                        class="hidden"
                        accept="application/pdf"
                        @change="onCnTreePdfChange"
                    />
                    <Button
                        type="button"
                        variant="gray"
                        @click="$refs.cnTreePdf.click()"
                        class="justify-center gap-2"
                    >
                        <span>Browse</span>
                    </Button>
                </div>
            </div>

            <div class="pt-5 flex justify-end">
                <Button
                    class="flex justify-center"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{ $t('public.save') }}
                </Button>
            </div>
        </form>
    </div>
</template>
