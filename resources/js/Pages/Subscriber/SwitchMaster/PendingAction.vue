<script setup>
import Button from "@/Components/Button.vue";
import {alertTriangle, ChevronRightDoubleIcon, MemberDetailIcon} from "@/Components/Icons/outline.jsx";
import Tooltip from "@/Components/Tooltip.vue";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import {CheckIcon, XIcon} from "@heroicons/vue/outline";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import StatusBadge from "@/Components/StatusBadge.vue";

const props = defineProps({
    switchMaster: Object,
    status: String
})

const currentLocale = ref(usePage().props.locale);
const approvalModal = ref(false);
const modalComponent = ref('');
const { formatDateTime, formatAmount } = transactionFormat();

const openApprovalModal = (componentType) => {
    approvalModal.value = true;

    if (componentType === 'approve') {
        modalComponent.value = 'Approve request';
    } else if (componentType === 'reject') {
        modalComponent.value = 'Reject request';
    } else if (componentType === 'rejectRemarks') {
        modalComponent.value = 'Reject Remark';
    } else if (componentType === 'view') {
        modalComponent.value = 'View Details';
    }
}

const closeModal = () => {
    approvalModal.value = false;
}

const form = useForm({
    switch_master_id: props.switchMaster.id,
    remarks: ''
})

const submit = () => {
    let submitRoute;
    if (modalComponent.value === 'Approve request') {
        submitRoute = route('subscriber.approveSwitchMaster');
    } else if (modalComponent.value === 'Reject Remark') {
        submitRoute = route('subscriber.rejectSwitchMaster');
    }

    if (submitRoute) {
        form.post(submitRoute, {
            onSuccess: () => {
                closeModal();
                form.reset();
            },
        });
    } else {
        console.error('Invalid modal component:', modalComponent);
    }
}
</script>

<template>
    <Tooltip
        v-if="status === 'Pending'"
        content="Approve"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="success"
            @click="openApprovalModal('approve')"
        >
            <CheckIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">Approve</span>
        </Button>
    </Tooltip>
    <Tooltip
        v-if="status === 'Pending'"
        content="Reject"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="danger"
            @click="openApprovalModal('reject')"
        >
            <XIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">reject</span>
        </Button>
    </Tooltip>
    <Tooltip
        v-if="status !== 'Pending'"
        content="Details"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openApprovalModal('view')"
        >
            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">view</span>
        </Button>
    </Tooltip>

    <Modal :show="approvalModal" :title="modalComponent" @close="closeModal" max-width="4xl">
        <div v-if="modalComponent === 'Approve request' && status === 'Pending'">
            <div class="grid grid-cols-11">
                <div class="col-span-11 sm:col-span-5 bg-gray-100 dark:bg-gray-600 rounded-lg p-5">
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-center justify-center w-full">
                            <div class="flex flex-col items-start gap-3 self-stretch w-full">
                                <div class="text-lg font-semibold dark:text-white">
                                    <div v-if="currentLocale === 'en'">
                                        {{ switchMaster.old_master.trading_user.name }}
                                    </div>
                                    <div v-if="currentLocale === 'cn'">
                                        {{ switchMaster.old_master.trading_user.company ? switchMaster.old_master.trading_user.company : switchMaster.old_master.trading_user.name }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.account_number')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.meta_login }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.join_date')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ formatDateTime(switchMaster.subscriber.approval_date, false) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.join_day') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.join_days }} {{ $t('public.days') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.roi_period') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.old_master.roi_period }} {{ $t('public.days') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.sharing_profit') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.old_master.sharing_profit % 1 === 0 ? formatAmount(switchMaster.old_master.sharing_profit, 0) : formatAmount(switchMaster.old_master.sharing_profit) }}%
                                    </div>
                                </div>
                                <div class="flex items-start justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.amount')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                        $ {{ formatAmount(switchMaster.subscriber.subscribe_amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-11 sm:col-span-1 rounded-lg p-2">
                    <div class="flex items-center justify-center w-full h-full">
                        <ChevronRightDoubleIcon class="text-gray-400 rotate-90 sm:rotate-0" />
                    </div>
                </div>
                <div class="col-span-11 sm:col-span-5 bg-gray-100 rounded-lg p-5">
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-center justify-center w-full">
                            <div class="flex flex-col items-start gap-3 self-stretch w-full">
                                <div class="text-lg font-semibold dark:text-white">
                                    <div v-if="currentLocale === 'en'">
                                        {{ switchMaster.new_master.trading_user.name }}
                                    </div>
                                    <div v-if="currentLocale === 'cn'">
                                        {{ switchMaster.new_master.trading_user.company ? switchMaster.new_master.trading_user.company : switchMaster.new_master.trading_user.name }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.account_number')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.meta_login }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.estimated_monthly_returns')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.estimated_monthly_returns : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.estimated_lot_size')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.estimated_lot_size : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.max_drawdown') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.max_drawdown : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.roi_period') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.roi_period + ' ' + $t('public.days') : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.sharing_profit') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? (switchMaster.new_master.sharing_profit % 1 === 0 ? formatAmount(switchMaster.new_master.sharing_profit, 0) : formatAmount(switchMaster.new_master.sharing_profit)) + '%' : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-start justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.min_join_equity')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                        $ {{ switchMaster.new_master ? formatAmount(switchMaster.new_master.min_join_equity, 0) : $t('public.loading') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-sm font-normal dark:text-gray-400 pt-2">
                Do you want to approve this switch master request?
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button
                    type="button"
                    variant="secondary"
                    class="px-6 justify-center"
                    @click="closeModal"
                >
                    Cancel
                </Button>
                <Button
                    class="px-6 justify-center"
                    @click.prevent="submit"
                    :disabled="form.processing"
                >
                    Confirm
                </Button>
            </div>
        </div>

        <div v-if="modalComponent === 'Reject request' && status === 'Pending'">
            <div class="grid grid-cols-11">
                <div class="col-span-11 sm:col-span-5 bg-gray-100 dark:bg-gray-600 rounded-lg p-5">
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-center justify-center w-full">
                            <div class="flex flex-col items-start gap-3 self-stretch w-full">
                                <div class="text-lg font-semibold dark:text-white">
                                    <div v-if="currentLocale === 'en'">
                                        {{ switchMaster.old_master.trading_user.name }}
                                    </div>
                                    <div v-if="currentLocale === 'cn'">
                                        {{ switchMaster.old_master.trading_user.company ? switchMaster.old_master.trading_user.company : switchMaster.old_master.trading_user.name }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.account_number')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.meta_login }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.join_date')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ formatDateTime(switchMaster.subscriber.approval_date, false) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.join_day') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.join_days }} {{ $t('public.days') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.roi_period') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.old_master.roi_period }} {{ $t('public.days') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.sharing_profit') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.old_master.sharing_profit % 1 === 0 ? formatAmount(switchMaster.old_master.sharing_profit, 0) : formatAmount(switchMaster.old_master.sharing_profit) }}%
                                    </div>
                                </div>
                                <div class="flex items-start justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.amount')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                        $ {{ formatAmount(switchMaster.subscriber.subscribe_amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-11 sm:col-span-1 rounded-lg p-2">
                    <div class="flex items-center justify-center w-full h-full">
                        <ChevronRightDoubleIcon class="text-gray-400 rotate-90 sm:rotate-0" />
                    </div>
                </div>
                <div class="col-span-11 sm:col-span-5 bg-gray-100 rounded-lg p-5">
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-center justify-center w-full">
                            <div class="flex flex-col items-start gap-3 self-stretch w-full">
                                <div class="text-lg font-semibold dark:text-white">
                                    <div v-if="currentLocale === 'en'">
                                        {{ switchMaster.new_master.trading_user.name }}
                                    </div>
                                    <div v-if="currentLocale === 'cn'">
                                        {{ switchMaster.new_master.trading_user.company ? switchMaster.new_master.trading_user.company : switchMaster.new_master.trading_user.name }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.account_number')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.meta_login }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.estimated_monthly_returns')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.estimated_monthly_returns : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.estimated_lot_size')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.estimated_lot_size : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.max_drawdown') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.max_drawdown : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.roi_period') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.roi_period + ' ' + $t('public.days') : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.sharing_profit') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? (switchMaster.new_master.sharing_profit % 1 === 0 ? formatAmount(switchMaster.new_master.sharing_profit, 0) : formatAmount(switchMaster.new_master.sharing_profit)) + '%' : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-start justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.min_join_equity')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                        $ {{ switchMaster.new_master ? formatAmount(switchMaster.new_master.min_join_equity, 0) : $t('public.loading') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-sm font-normal dark:text-gray-400 pt-2">
                Do you want to reject this switch master request?
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button
                    type="button"
                    variant="secondary"
                    class="px-6 justify-center"
                    @click="closeModal"
                >
                    Cancel
                </Button>
                <Button
                    class="px-6 justify-center"
                    @click="openApprovalModal('rejectRemarks')"
                >
                    Confirm
                </Button>
            </div>
        </div>

        <div v-if="modalComponent === 'Reject Remark' && status === 'Pending'">
            <form>
                <div class="flex gap-2 mt-3 mb-8">
                    <Label class="text-sm text-black dark:text-white w-1/4 pt-0.5" for="remark" value="Remark" />
                    <div class="flex flex-col w-full">
                        <Input
                            id="remark"
                            type="text"
                            placeholder="Enter remark (visible to member)"
                            class="block w-full"
                            :class="form.errors.remarks ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                            v-model="form.remarks"
                        />
                        <InputError :message="form.errors.remarks" class="mt-2" />
                    </div>
                </div>
                <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                    <Button
                        type="button"
                        variant="secondary"
                        class="px-6 justify-center"
                        @click="closeModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        class="px-6 justify-center"
                        @click.prevent="submit"
                        :disabled="form.processing"
                    >
                        Confirm
                    </Button>
                </div>
            </form>
        </div>

        <div v-if="modalComponent === 'View Details' && status !== 'Pending'">
            <StatusBadge :value="switchMaster.status" />
            <div class="grid grid-cols-11 mt-4">
                <div class="col-span-11 sm:col-span-5 bg-gray-100 dark:bg-gray-600 rounded-lg p-5">
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-center justify-center w-full">
                            <div class="flex flex-col items-start gap-3 self-stretch w-full">
                                <div class="text-lg font-semibold dark:text-white">
                                    <div v-if="currentLocale === 'en'">
                                        {{ switchMaster.old_master.trading_user.name }}
                                    </div>
                                    <div v-if="currentLocale === 'cn'">
                                        {{ switchMaster.old_master.trading_user.company ? switchMaster.old_master.trading_user.company : switchMaster.old_master.trading_user.name }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.account_number')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.meta_login }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.join_date')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ formatDateTime(switchMaster.subscriber.approval_date, false) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.join_day') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.join_days }} {{ $t('public.days') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.roi_period') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.old_master.roi_period }} {{ $t('public.days') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.sharing_profit') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.old_master.sharing_profit % 1 === 0 ? formatAmount(switchMaster.old_master.sharing_profit, 0) : formatAmount(switchMaster.old_master.sharing_profit) }}%
                                    </div>
                                </div>
                                <div class="flex items-start justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.amount')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                        $ {{ formatAmount(switchMaster.subscriber.subscribe_amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-11 sm:col-span-1 rounded-lg p-2">
                    <div class="flex items-center justify-center w-full h-full">
                        <ChevronRightDoubleIcon class="text-gray-400 rotate-90 sm:rotate-0" />
                    </div>
                </div>
                <div class="col-span-11 sm:col-span-5 bg-gray-100 rounded-lg p-5">
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-center justify-center w-full">
                            <div class="flex flex-col items-start gap-3 self-stretch w-full">
                                <div class="text-lg font-semibold dark:text-white">
                                    <div v-if="currentLocale === 'en'">
                                        {{ switchMaster.new_master.trading_user.name }}
                                    </div>
                                    <div v-if="currentLocale === 'cn'">
                                        {{ switchMaster.new_master.trading_user.company ? switchMaster.new_master.trading_user.company : switchMaster.new_master.trading_user.name }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.account_number')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.meta_login }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.estimated_monthly_returns')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.estimated_monthly_returns : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.estimated_lot_size')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.estimated_lot_size : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.max_drawdown') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.max_drawdown : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.roi_period') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? switchMaster.new_master.roi_period + ' ' + $t('public.days') : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('public.sharing_profit') }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                        {{ switchMaster.new_master ? (switchMaster.new_master.sharing_profit % 1 === 0 ? formatAmount(switchMaster.new_master.sharing_profit, 0) : formatAmount(switchMaster.new_master.sharing_profit)) + '%' : $t('public.loading') }}
                                    </div>
                                </div>
                                <div class="flex items-start justify-between gap-2 self-stretch">
                                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                        {{$t('public.min_join_equity')}}
                                    </div>
                                    <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                        $ {{ switchMaster.new_master ? formatAmount(switchMaster.new_master.min_join_equity, 0) : $t('public.loading') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
