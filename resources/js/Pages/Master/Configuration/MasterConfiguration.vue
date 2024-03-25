<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ChevronRightIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Badge from "@/Components/Badge.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import {useForm} from "@inertiajs/vue3";
import {ref, watch} from "vue";
import {
    RadioGroup,
    RadioGroupLabel,
    RadioGroupDescription,
    RadioGroupOption,
} from '@headlessui/vue'

const props = defineProps({
    masterConfigurations: Object,
    subscriberCount: Number,
})

const form = useForm({
    master_id: props.masterConfigurations.id,
    min_join_equity: props.masterConfigurations.min_join_equity,
    sharing_profit: props.masterConfigurations.sharing_profit,
    subscription_fee: props.masterConfigurations.subscription_fee,
    signal_status: '',
    eta_montly_return: props.masterConfigurations.estimated_monthly_returns,
    eta_lot_size: props.masterConfigurations.estimated_lot_size,
    extra_fund: props.masterConfigurations.extra_fund,
    total_fund: props.masterConfigurations.total_fund,
    roi_period: props.masterConfigurations.roi_period,
    total_subscriber: props.masterConfigurations.total_subscribers,
    max_drawdown: props.masterConfigurations.max_drawdown,
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

const submit = () => {
    form.signal_status = selected.value.value;
    form.post(route('master.updateMasterConfiguration'))
}

const badgeVariant = (status) => {
    if (status === 'Active') {
        return 'bg-success-400 dark:bg-success-500'
    } else if(status === 'Inactive') {
        return 'bg-warning-400 dark:bg-warning-500'
    }
};
</script>

<template>
    <AuthenticatedLayout title="Master Details">
        <template #header>
            <div class="flex flex-row gap-2 items-center">
                <h2 class="text-sm font-semibold dark:text-gray-400">
                    <a class="dark:hover:text-white" href="/master/getMasterListing">Master Listing</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <h2 class="text-sm font-semibold dark:text-white">
                    {{ props.masterConfigurations.meta_login }} - Master Configuration
                </h2>
            </div>
        </template>

        <div class="flex gap-5 items-start self-stretch">
            <div class="flex flex-col gap-4 items-start bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-3/4 rounded-lg shadow-lg">
                <div class="flex justify-between items-center self-stretch border-b border-gray-300 dark:border-gray-500 pb-2">
                    <div class="flex items-center gap-3">
                        <div class="text-lg font-semibold">
                            Copy Trade Configuration
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <div
                            class="flex w-20 px-2 py-1 justify-center text-white mx-auto rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out"
                            :class="badgeVariant(masterConfigurations.status)">{{ masterConfigurations.status }}
                        </div>
                    </div>
                </div>

                <form class="w-full">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
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
                                for="sharing_profit"
                                value="Sharing Profit (%)"
                            />
                            <Input
                                id="sharing_profit"
                                type="number"
                                min="0"
                                placeholder="50%"
                                class="block w-full"
                                v-model="form.sharing_profit"
                                :invalid="form.errors.sharing_profit"
                            />
                            <InputError :message="form.errors.sharing_profit" />
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="subscription_fee"
                                value="Subscription Fee (Month)"
                            />
                            <Input
                                id="subscription_fee"
                                type="number"
                                min="0"
                                placeholder="$ 0.00"
                                class="block w-full"
                                v-model="form.subscription_fee"
                                :invalid="form.errors.subscription_fee"
                            />
                            <InputError :message="form.errors.subscription_fee" />
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="roi_period"
                                value="ROI Period"
                            />
                            <Input
                                id="roi_period"
                                type="number"
                                min="7"
                                placeholder="Days"
                                class="block w-full"
                                v-model="form.roi_period"
                                :invalid="form.errors.roi_period"
                            />
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
                                for="extra_fund"
                                value="Extra Fund"
                            />
                            <Input
                                id="extra_fund"
                                type="number"
                                min="0"
                                placeholder="$ 0.00"
                                class="block w-full"
                                v-model="form.extra_fund"
                                :invalid="form.errors.extra_fund"
                            />
                            <InputError :message="form.errors.extra_fund" />
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
                                for="signal_status"
                                value="Trade Signal Status"
                            />
                            <RadioGroup v-model="selected">
                                <RadioGroupLabel class="sr-only">Signal Status</RadioGroupLabel>
                                <div class="flex gap-3 items-center self-stretch w-full">
                                    <RadioGroupOption
                                        as="template"
                                        v-for="(plan, index) in plans"
                                        :key="index"
                                        :value="plan"
                                        v-slot="{ active, checked }"
                                    >
                                        <div
                                            :class="[
                                                active
                                                  ? 'ring-0 ring-white ring-offset-0'
                                                  : '',
                                                checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 bg-white dark:bg-gray-700',
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

            <div class="flex flex-col gap-4 items-start bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-1/4 rounded-lg shadow-lg">
                <div>
                    Total Subscribers
                </div>
                <div>
                    {{ subscriberCount }}
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>