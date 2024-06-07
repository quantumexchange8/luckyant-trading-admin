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
import MasterOverview from "@/Pages/Master/Configuration/MasterOverview.vue";
import CopyTradeConfigurationForm from "@/Pages/Master/Configuration/CopyTradeConfigurationForm.vue";
import ManagementFeeForm from "@/Pages/Master/Configuration/ManagementFeeForm.vue";
import MasterLeaderForm from "@/Pages/Master/Configuration/MasterLeaderForm.vue";

const props = defineProps({
    masterConfigurations: Object,
    subscriberCount: Number,
    settlementPeriodSel: Array,
})
</script>

<template>
    <AuthenticatedLayout title="Master Details">
        <template #header>
            <div class="flex flex-row gap-2 items-center">
                <h2 class="text-2xl font-semibold leading-tight">
                    <a class="dark:hover:text-white" href="/master/getMasterListing">Master Listing</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <h2 class="text-2xl font-semibold text-primary-600 dark:text-primary-400">
                    {{ masterConfigurations.meta_login }} - Master Configuration
                </h2>
            </div>
        </template>

        <div class="space-y-5">
            <MasterOverview
                :masterConfigurations="masterConfigurations"
                :subscriberCount="subscriberCount"
            />
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                <CopyTradeConfigurationForm
                    :masterConfigurations="masterConfigurations"
                    :settlementPeriodSel="settlementPeriodSel"
                />
                <div class="grid gap-5">
                    <ManagementFeeForm
                        :masterConfigurations="masterConfigurations"
                        :settlementPeriodSel="settlementPeriodSel"
                    />
                    <MasterLeaderForm
                        :masterConfigurations="masterConfigurations"
                        :settlementPeriodSel="settlementPeriodSel"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>
