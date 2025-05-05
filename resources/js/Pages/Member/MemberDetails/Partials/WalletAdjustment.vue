<script setup>
import Button from "primevue/button";
import {CreditEditIcon} from "@/Components/Icons/outline.jsx";
import {ref } from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {useForm} from "@inertiajs/vue3";
import Dialog from "primevue/dialog";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";

const props = defineProps({
    wallet: Object,
})

const visible = ref(false);
const { formatAmount,formatType } = transactionFormat();

const openDialog = () => {
    visible.value = true;
}

const adjustmentTypes = [
    'WalletAdjustment',
    'WalletRedemption',
    'ReturnedAmount',
    'IncorrectBonusCorrection'
];

const selectedAdjustmentType = ref('');

const selectAdjustmentType = (type) => {
    selectedAdjustmentType.value = type;
}

const adjustmentActions = [
    'add',
    'deduct',
];

const selectedAdjustmentAction = ref('');

const selectAdjustmentActions = (type) => {
    selectedAdjustmentAction.value = type;
}

const form = useForm({
    wallet_id: props.wallet.id,
    adjustment_type: '',
    adjustment_action: '',
    amount: null,
    remarks: ''
});

const submitForm = () => {
    form.adjustment_type = selectedAdjustmentType.value;
    form.adjustment_action = selectedAdjustmentAction.value;
    form.post(route('member.wallet_adjustment'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <div class="flex">
        <Tooltip :content="$t('public.adjust_balance')" placement="right">
            <Button
                type="button"
                size="small"
                @click="openDialog"
                rounded
                outlined
            >
                <template #icon>
                    <CreditEditIcon class="w-4 h-4" />
                </template>
            </Button>
        </Tooltip>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.adjust_balance')"
        class="dialog-xs md:dialog-md"
    >
        <form class="flex flex-col gap-5 items-center self-stretch">
            <div class="flex flex-col items-center self-stretch bg-gray-100 dark:bg-gray-800 p-5">
                <div class="text-xs text-gray-500">
                    {{ $t('public.balance') }}
                </div>
                <div class="text-lg font-semibold">
                    ${{ formatAmount(wallet.balance) }}
                </div>
            </div>

            <div class="w-full flex flex-col gap-3 items-center self-stretch">
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="adjustment_type"
                        :value="$t('public.adjustment_type')"
                    />
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 gap-3 self-stretch w-full"
                    >
                        <div
                            v-for="adjustment in adjustmentTypes"
                            @click="selectAdjustmentType(adjustment)"
                            class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                            :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedAdjustmentType === adjustment,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedAdjustmentType !== adjustment,
                                }"
                        >
                            <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedAdjustmentType === adjustment,
                                        'text-gray-950 dark:text-white': selectedAdjustmentType !== adjustment
                                    }"
                                >
                                    {{ $t(`public.${formatType(adjustment).toLowerCase().replace(/\s+/g, '_')}`) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.adjustment_type" />
                </div>

                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="adjustment_action"
                        :value="$t('public.adjustment')"
                    />
                    <div
                        class="flex items-start gap-3 self-stretch w-full overflow-x-auto"
                    >
                        <div
                            v-for="action in adjustmentActions"
                            @click="selectAdjustmentActions(action)"
                            class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                            :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedAdjustmentAction === action,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedAdjustmentAction !== action,
                                }"
                        >
                            <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedAdjustmentAction === action,
                                        'text-gray-950 dark:text-white': selectedAdjustmentAction !== action
                                    }"
                                >
                                    {{ $t(`public.${action}`) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.adjustment_action" />
                </div>

                <div class="flex flex-col gap-1 items-start self-stretch">
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

                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="remarks"
                        :value="$t('public.remarks')"
                    />
                    <Textarea
                        id="remarks"
                        type="text"
                        v-model="form.remarks"
                        :invalid="!!form.errors.remarks"
                        :placeholder="$t('public.remarks')"
                        class="block w-full"
                        rows="5"
                        cols="30"
                    />
                    <InputError :message="form.errors.remarks" />
                </div>

                <div class="flex items-center justify-end pt-5 gap-3 self-stretch">
                    <Button
                        type="button"
                        severity="secondary"
                        size="small"
                        class="w-full md:w-auto"
                        :label="$t('public.cancel')"
                        @click="closeDialog"
                        :disabled="form.processing"
                    />

                    <Button
                        type="submit"
                        size="small"
                        class="w-full md:w-auto"
                        :label="$t('public.confirm')"
                        @click="submitForm"
                        :disabled="form.processing"
                    />
                </div>
            </div>
        </form>
    </Dialog>
</template>
