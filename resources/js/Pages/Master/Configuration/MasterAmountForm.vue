<script setup>
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import Tooltip from "@/Components/Tooltip.vue";
import {PlusIcon} from "@heroicons/vue/outline";
import {Trash03Icon} from "@/Components/Icons/outline.jsx";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    masterConfigurations: Object,
    settlementPeriodSel: Array,
})
const { formatAmount } = transactionFormat();

const form = useForm({
    master_id: props.masterConfigurations.id,
    amounts: props.masterConfigurations.master_subscription_package.map((sub_package) => sub_package.amount) || { amount: null },
    max_out_amounts: props.masterConfigurations.master_subscription_package.map((sub_package) => sub_package.max_out_amount) || { max_out_amount: null },
})

const addDividend = () => {
    form.amounts.push('');
    form.max_out_amounts.push('');
}

const removeDividend = (index) => {
    form.amounts.splice(index, 1)
    form.max_out_amounts.splice(index, 1)
}

const submit = () => {
    form.post(route('master.updateMasterSubscriptionPackage'))
}
</script>

<template>
    <div class="flex flex-col items-start gap-5 bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
        <div class="flex items-center gap-3">
            <div class="text-lg font-semibold">
                PAMM Amount Package
            </div>
        </div>
        <div class="space-y-2 p-5 rounded-xl bg-gray-100 dark:bg-gray-800 w-full">
            <div class="text-sm font-semibold dark:text-gray-200">
                Preview [ Amount - Max Out Amount ]
            </div>
            <div
                v-for="(amount, index) in form.amounts"
                class="flex justify-between items-center w-full"
            >
                <div class="text-gray-600 dark:text-gray-400">
                    $ {{ formatAmount(amount ? amount : 0, 0) }}
                </div>
                <div class="font-semibold">
                    $ {{ formatAmount(form.max_out_amounts[index] ? form.max_out_amounts[index] : 0, 0) }}
                </div>
            </div>
        </div>
        <form class="w-full">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-4 col-span-3">
                    <div v-for="(max_out, index) in form.max_out_amounts" class="flex flex-col">
                        <div class="flex items-center gap-3">
                            <Input
                                :id="`management_day_${index}`"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="$ 0.00"
                                class="block w-40"
                                :aria-label="`Amount ${index+1}`"
                                v-model="form.amounts[index]"
                                :invalid="form.errors[`amounts.${index}`]"
                            />
                            <Input
                                :id="`max_out_amounts_${index}`"
                                type="number"
                                min="0"
                                step=".01"
                                placeholder="$ 0.00"
                                class="block w-full"
                                :aria-label="`Max Out Amount ${index+1}`"
                                v-model="form.max_out_amounts[index]"
                                :invalid="form.errors[`amounts.${index}`]"
                            />
                            <Tooltip content="Remove" placement="bottom">
                                <Button
                                    type="button"
                                    pill
                                    class="justify-center px-4 pt-2 mx-1 rounded-full w-8 h-8 focus:outline-none"
                                    variant="danger"
                                    @click="removeDividend(index)"
                                >
                                    <Trash03Icon aria-hidden="true" class="w-5 h-5 absolute" />
                                    <span class="sr-only">Delete</span>
                                </Button>
                            </Tooltip>
                        </div>
                        <InputError :message="form.errors[`amounts.${index}`]" class="mt-2" />
                    </div>
                    <Button
                        type="button"
                        variant="transparent"
                        @click="addDividend"
                        size="sm"
                        v-slot="{iconSizeClasses}"
                        class="flex gap-1 pl-0 pt-0 text-primary-600 dark:text-primary-400 hover:text-primary-400 dark:hover:text-primary-600"
                    >
                        <PlusIcon
                            :class="iconSizeClasses"
                        />
                        Add Another
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
