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

const props = defineProps({
    last_allocate_date: String,
})

const visible = ref(false);

const openDialog = () => {
    visible.value = true;
}

// Initialize the pool_allocations as an empty array
const pool_allocations = ref([]);
const lastAddedDate = ref(
    props.last_allocate_date ? dayjs(props.last_allocate_date) : dayjs()
);

// Initialize Inertia form
const form = useForm({
    pool_allocations: [],
});

const addDividend = () => {
    let nextDay = lastAddedDate.value.add(1, 'day'); // Start from the correct lastAddedDate

    pool_allocations.value.push({
        date: nextDay.format('D/M'), // Displayed format
        full_date: nextDay.format('YYYY-MM-DD'),
        pool_amount: null,
    });

    lastAddedDate.value = nextDay;
};

addDividend();

const removeDividend = (index) => {
    if (index >= 0 && index < pool_allocations.value.length) {
        pool_allocations.value.splice(index, 1);

        // Ensure lastAddedDate updates to the last remaining date
        if (pool_allocations.value.length > 0) {
            lastAddedDate.value = dayjs(pool_allocations.value[pool_allocations.value.length - 1].full_date);
        } else {
            lastAddedDate.value = dayjs(); // Reset if all removed
        }
    }
};

const submitForm = () => {
    form.pool_allocations = pool_allocations.value;
    form.post(route('world_pool.allocateWorldPool'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            pool_allocations.value = [];
            addDividend();
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
        <div class="flex flex-col items-center gap-3 self-stretch">
            <div class="flex justify-between items-center py-2 self-stretch border-b border-gray-200 bg-gray-100 dark:bg-gray-800 dark:border-gray-900">
                <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                    {{ $t('public.date') }}
                </div>
                <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                    {{ $t('public.pool_amount') }} ($)
                </div>
            </div>

            <div class="flex flex-col items-center self-stretch max-h-[400px] overflow-y-auto">
                <div
                    v-for="(profitDay, index) in pool_allocations"
                    class="flex justify-between py-1 items-center self-stretch"
                >
                    <div class="text-gray-950 dark:text-white px-2 w-full">
                        {{ profitDay.date }}
                    </div>
                    <div class="flex flex-col gap-1 items-start px-2 w-full">
                        <div class="flex items-center gap-1 w-full">
                            <InputNumber
                                v-model="profitDay.pool_amount"
                                :minFractionDigits="2"
                                fluid
                                placeholder="0.00"
                                inputClass="w-20"
                                :invalid="!!form.errors[`pool_allocations.${index}.pool_amount`]"
                            />
                            <Button
                                v-if="pool_allocations.length === 1 || index !== pool_allocations.length - 1"
                                type="button"
                                severity="info"
                                size="small"
                                class="!p-2"
                                text
                                @click="addDividend"
                            >
                                <IconCirclePlus size="20" />
                            </Button>
                            <Button
                                v-else
                                type="button"
                                severity="danger"
                                size="small"
                                text
                                @click="removeDividend(index)"
                                class="!p-2"
                            >
                                <IconCircleX size="20" />
                            </Button>
                        </div>
                        <InputError :message="form.errors[`pool_allocations.${index}.pool_amount`]" />
                    </div>
                </div>
            </div>

            <Button
                type="submit"
                size="small"
                class="w-full"
                @click="submitForm"
            >
                {{ $t('public.save') }}
            </Button>
        </div>
    </Dialog>
</template>
