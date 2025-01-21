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
import Tag from "primevue/tag";
import Textarea from "primevue/textarea";
import InputLabel from "@/Components/Label.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    subscription: Object
});

const visible = ref(false);
const dialogType = ref('');
const {formatAmount} = transactionFormat();

const openDialog = (action) => {
    visible.value = true;
    dialogType.value = action;
}

const form = useForm({
    subscription_id: props.subscription.id,
    action: '',
    remarks: '',
})

const submitForm = () => {
    form.action = dialogType.value;
    form.patch(route('pamm.pammSubscriptionApproval'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}

const getSeverity = (status) => {
    switch (status) {
        case 'hofi':
            return 'warn';

        case 'alpha':
            return 'info';

        case 'standard_account':
            return 'success';

        case 'ecn_account':
            return 'secondary';
    }
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
            <IconCheck size="16"/>
        </Button>

        <Button
            rounded
            outlined
            severity="danger"
            class="!p-1.5"
            v-tooltip.bottom="$t('public.reject')"
            @click="openDialog('reject')"
        >
            <IconX size="16"/>
        </Button>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}_subscription`)"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center gap-4 divide-y dark:divide-gray-700 self-stretch">
            <div class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full">
                <div class="flex flex-col items-start w-full">
                    <span class="text-gray-950 dark:text-white text-sm font-medium">{{ subscription.user.name }}</span>
                    <span class="text-gray-500 text-xs">{{ subscription.user.email }}</span>
                </div>
                <div class="min-w-[180px] text-gray-950 dark:text-white font-semibold text-xl md:text-right">
                    $ {{ formatAmount(subscription.subscription_amount) }}
                </div>
            </div>

            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ dayjs(subscription.created_at).format('DD/MM/YYYY HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.account') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ subscription.meta_login }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.master') }}
                    </div>
                    <div class="flex flex-col text-gray-950 dark:text-white text-sm font-medium">
                        <div>
                            {{ subscription.master.trading_user.name }}
                            <Tag
                                :severity="getSeverity(subscription.master.trading_user.from_account_type.slug)"
                                :value="$t(`public.${subscription.master.trading_user.from_account_type.slug}`)"
                            />
                        </div>
                        <span class="text-gray-400">{{ subscription.master_meta_login }}</span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.leader') }}
                    </div>
                    <div class="flex flex-col text-gray-950 dark:text-white text-sm font-medium">
                        <span>{{ subscription.first_leader_name }}</span>
                        <span class="text-gray-400">{{ subscription.first_leader_email }}</span>
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
                <InputError :message="form.errors.remarks"/>
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
