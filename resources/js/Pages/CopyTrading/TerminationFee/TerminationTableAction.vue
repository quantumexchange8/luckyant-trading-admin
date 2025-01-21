<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import {ref} from "vue";
import {
    IconFileSearch
} from "@tabler/icons-vue";
import dayjs from "dayjs";
import Tag from "primevue/tag";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    termination: Object
});

const visible = ref(false);
const {formatAmount} = transactionFormat();

const getJoinedDays = (data) => {
    const approvalDate = dayjs(data.approval_date);
    const endDate = dayjs(data.termination_date);

    return endDate.diff(approvalDate, 'day'); // Calculate the difference in days
};

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
    <Button
        type="button"
        severity="secondary"
        outlined
        rounded
        size="sm"
        class="!p-2"
        @click="visible = true"
    >
        <IconFileSearch size="14" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.termination_details')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center gap-4 divide-y dark:divide-gray-700 self-stretch">
            <div class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full">
                <div class="flex flex-col items-start w-full">
                    <span class="text-gray-950 dark:text-white text-sm font-medium">{{ termination.user.name }}</span>
                    <span class="text-gray-500 text-xs">{{ termination.user.email }}</span>
                </div>
                <div class="min-w-[180px] text-gray-950 dark:text-white font-semibold text-xl md:text-right">
                    $ {{ formatAmount(termination.subscription_batch_amount) }}
                </div>
            </div>

            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.join_date') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ dayjs(termination.approval_date).format('DD/MM/YYYY HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.account') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ termination.meta_login }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.master') }}
                    </div>
                    <div class="flex flex-col text-gray-950 dark:text-white text-sm font-medium">
                        <div>
                            {{ termination.master.trading_user.name }}
                            <Tag
                                :severity="getSeverity(termination.master.trading_user.from_account_type.slug)"
                                :value="$t(`public.${termination.master.trading_user.from_account_type.slug}`)"
                            />
                        </div>
                        <span class="text-gray-400">{{ termination.master_meta_login }}</span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.leader') }}
                    </div>
                    <div class="flex flex-col text-gray-950 dark:text-white text-sm font-medium">
                        <span>{{ termination.first_leader_name }}</span>
                        <span class="text-gray-400">{{ termination.first_leader_email }}</span>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.termination_date') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ dayjs(termination.termination_date).format('DD/MM/YYYY HH:mm:ss') }} ({{ getJoinedDays(termination) }} {{ $t('public.days') }})
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.management_fee') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        $ {{ formatAmount(termination.penalty_amount) }} ({{ formatAmount(termination.penalty_percent, 0) }}%)
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.returned') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        $ {{ formatAmount(termination.return_amount) }}
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
