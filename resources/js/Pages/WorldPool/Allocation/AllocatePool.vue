<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import {
    IconDeviceImacDollar,
    IconCirclePlus,
    IconCircleX
} from "@tabler/icons-vue";
import {ref} from "vue";
import dayjs from "dayjs";
import InputNumber from "primevue/inputnumber";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import {transactionFormat} from "@/Composables/index.js";
import InputText from "primevue/inputtext";
import InputLabel from "@/Components/Label.vue";
import DatePicker from "primevue/datepicker";

const props = defineProps({
    active_pamm_capital: Number,
    active_subscriptions_capital: Number,
    extra_fund_sum: Number,
})

const visible = ref(false);
const {formatAmount} = transactionFormat();

const openDialog = () => {
    visible.value = true;
}

const form = useForm({
    allocation_date: '',
    allocation_amount: null,
});

const submitForm = () => {
    form.post(route('world_pool.allocateWorldPool'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    });
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        class="flex gap-2 w-full md:w-auto"
        @click="openDialog"
    >
        <IconDeviceImacDollar size="16" stroke-width="1.5" />
        {{ $t('public.allocate_pool') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.allocate_pool')"
        class="dialog-xs md:dialog-md"
    >
        <form class="flex flex-col items-center gap-5 self-stretch">
            <div class="flex flex-col gap-3 items-center p-5 self-stretch bg-gray-100 dark:bg-gray-800">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[160px] text-gray-500 text-xs font-medium">
                        {{ $t('public.total_capital') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        ${{ formatAmount(active_pamm_capital + active_subscriptions_capital) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[160px] text-gray-500 text-xs font-medium">
                        {{ $t('public.extra_fund') }} ({{ $t('public.until_today') }})
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        ${{ formatAmount(extra_fund_sum) }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="allocation_date"
                        :invalid="!!form.errors.allocation_date"
                    >
                        {{ $t('public.date') }}
                    </InputLabel>
                    <DatePicker
                        v-model="form.allocation_date"
                        dateFormat="yy-mm-dd"
                        class="w-full font-normal"
                        placeholder="YYYY-MM-DD"
                        :invalid="!!form.errors.allocation_date"
                    />
                    <InputError :message="form.errors.allocation_date" />
                </div>
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="allocation_amount"
                        :invalid="!!form.errors.allocation_amount"
                    >
                        {{ $t('public.amount') }} ($)
                    </InputLabel>
                    <InputNumber
                        v-model="form.allocation_amount"
                        :minFractionDigits="2"
                        fluid
                        placeholder="0.00"
                        :invalid="!!form.errors.allocation_amount"
                    />
                    <InputError :message="form.errors.allocation_amount" />
                </div>
            </div>

            <div class="flex w-full justify-end gap-3">
                <Button
                    type="button"
                    severity="secondary"
                    size="small"
                    class="w-full md:w-auto"
                    @click="closeDialog"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="submit"
                    size="small"
                    class="w-full md:w-auto"
                    @click.prevent="submitForm"
                >
                    {{ $t('public.save') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
