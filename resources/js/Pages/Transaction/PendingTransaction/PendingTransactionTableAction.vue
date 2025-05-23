<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import {
    IconCheck,
    IconX
} from "@tabler/icons-vue"
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import dayjs from "dayjs";
import Image from "primevue/image";
import Textarea from "primevue/textarea";
import InputLabel from "@/Components/Label.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    pending: Object
});

const visible = ref(false);
const dialogType = ref('');
const {formatAmount} = transactionFormat();

const openDialog = (action) => {
    visible.value = true;
    dialogType.value = action;
}

const form = useForm({
    id: '',
    type: 'single',
    remarks: '',
});

const submitForm = () => {
    let submitRoute;
    if (dialogType.value === 'approve') {
        submitRoute = route('transaction.approveTransaction');
    } else if (dialogType.value === 'reject') {
        submitRoute = route('transaction.rejectTransaction');
    }

    if (submitRoute) {
        form.id = props.pending.id;
        form.post(submitRoute, {
            onSuccess: () => {
                closeDialog();
            },
        });
    } else {
        console.error('Invalid modal component:', modalComponent);
    }
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <div class="flex items-center gap-3">
        <Button
            rounded
            outlined
            severity="success"
            class="!p-1.5"
            v-tooltip.bottom="$t('public.approve')"
            @click="openDialog('approve')"
        >
            <IconCheck size="16" />
        </Button>

        <Button
            rounded
            outlined
            severity="danger"
            class="!p-1.5"
            v-tooltip.bottom="$t('public.reject')"
            @click="openDialog('reject')"
        >
            <IconX size="16" />
        </Button>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}_transaction`)"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center gap-4 divide-y dark:divide-gray-700 self-stretch">
            <div class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full">
                <div class="flex flex-col items-start w-full">
                    <span class="text-gray-950 dark:text-white text-sm font-medium">{{ pending.user.name }}</span>
                    <span class="text-gray-500 text-xs">{{ pending.user.email }}</span>
                </div>
                <div class="min-w-[180px] text-gray-950 dark:text-white font-semibold text-xl md:text-right">
                    $ {{ formatAmount(pending.amount) }}
                </div>
            </div>

            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ dayjs(pending.created_at).format('DD/MM/YYYY HH:mm:ss') }}
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.transaction_no') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ pending.transaction_number }}
                    </div>
                </div>

                <!-- Withdrawal Only -->
                <div v-if="pending.transaction_type === 'Withdrawal'" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.wallet') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ $t(`public.${pending.from_wallet.type}`) }}
                    </div>
                </div>
                <div v-if="pending.transaction_type === 'Withdrawal'" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.profit') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        $ {{ formatAmount(pending.profitAmount ?? 0) }}
                    </div>
                </div>
                <div v-if="pending.transaction_type === 'Withdrawal'" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.bonus') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        $ {{ formatAmount(pending.bonusAmount ?? 0) }}
                    </div>
                </div>
                <div v-if="pending.transaction_type === 'Withdrawal'" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.fee') }}
                    </div>
                    <div class="text-error-500 text-sm font-medium">
                        $ {{ formatAmount(pending.transaction_charges) }}
                    </div>
                </div>
                <div v-if="pending.transaction_type === 'Deposit'" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.transfer_amount') }}
                    </div>
                    <div class="text-primary-500 text-sm font-medium">
                        <span>{{ pending.setting_payment?.currency === 'USD' ? '$' : pending.setting_payment ? '¥' : '$' }}</span>
                        {{ formatAmount(pending.transaction_amount ?? 0) }}
                    </div>
                </div>
                <div v-if="pending.transaction_type === 'Withdrawal'" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.receive') }}
                    </div>
                    <div class="text-primary-500 text-sm font-medium">
                        $ {{ formatAmount(pending.transaction_amount) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.to') }}
                    </div>
                    <div v-if="pending.transaction_type === 'Withdrawal'" class="flex flex-col gap-1 text-gray-950 dark:text-white text-sm font-medium">
                        <span class="font-semibold">{{ pending.payment_account.payment_account_name ?? pending.user.name }}</span>
                        <span class="text-xs text-gray-400">{{ pending.to_wallet_address }}</span>
                    </div>
                    <div v-if="pending.transaction_type === 'Deposit'" class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ $t(`public.${pending.to_wallet.type}`) }}
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.leader') }}
                    </div>
                    <div class="flex flex-col text-gray-950 dark:text-white text-sm font-medium">
                        <span>{{ pending.first_leader_name }}</span>
                        <span class="text-gray-400">{{ pending.first_leader_email }}</span>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.payment_method') }}
                    </div>
                    <div class="flex flex-col text-gray-950 dark:text-white text-sm font-medium">
                        <span>{{ pending.payment_method }}</span>
                        <span class="text-gray-400">{{ pending.to_wallet_address }}</span>
                    </div>
                </div>
            </div>

            <div
                v-if="pending.transaction_type === 'Withdrawal' && pending.payment_method === 'Bank'"
                class="flex flex-col gap-3 items-start w-full pt-4"
            >
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.bank') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ pending.payment_account.payment_platform_name }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        Region of Bank
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ pending.payment_account.bank_region ?? '-' }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        Account Name
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ pending.payment_account.payment_account_name }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        Account No
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ pending.payment_account.account_no }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        Bank Sub Branch
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ pending.payment_account.bank_sub_branch }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        Bank SWIFT Code
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ pending.payment_account.bank_swift_code }}
                    </div>
                </div>
            </div>

            <div v-if="pending.transaction_type === 'Deposit' && pending.media.length > 0" class="flex flex-col md:flex-row md:items-start gap-1 self-stretch pt-5">
                <div class="w-[140px] text-gray-500 text-xs font-medium">
                    {{ $t('public.payment_slip') }}
                </div>
                <div class="flex gap-2 col-span-2 items-center self-stretch">
                    <div
                        v-for="media in pending.media"
                    >
                        <Image
                            :src="media.original_url"
                            alt="Image"
                            imageClass="max-w-full h-12 object-contain rounded"
                            preview
                        />
                    </div>
                </div>
            </div>

            <div v-if="dialogType === 'reject'" class="flex flex-col items-start gap-1 self-stretch pt-4">
                <InputLabel for="remarks">{{ $t('public.remarks') }}</InputLabel>
                <Textarea
                    id="remarks"
                    type="text"
                    class="flex flex-1 self-stretch"
                    v-model="form.remarks"
                    :placeholder="dialogType === 'approve' ? $t(`public.${dialogType}_subscription`) : $t(`public.${dialogType}_subscription`)"
                    :invalid="!!form.errors.remarks"
                    rows="5"
                    cols="30"
                    autofocus
                />
                <InputError :message="form.errors.remarks" />
            </div>

            <div class="pt-5 flex gap-3 justify-end items-center self-stretch w-full">
                <Button
                    type="button"
                    :label="$t('public.cancel')"
                    severity="secondary"
                    variant="outlined"
                    class="px-3 w-full md:w-auto"
                    :disabled="form.processing"
                    @click="closeDialog"
                />

                <Button
                    type="submit"
                    class="px-3 w-full md:w-auto"
                    :label="$t('public.confirm')"
                    :disabled="form.processing"
                    @click.prevent="submitForm"
                />
            </div>
        </div>
    </Dialog>
</template>
