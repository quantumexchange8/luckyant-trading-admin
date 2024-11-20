<script setup>
import ManagementFeeSetting from "@/Pages/Master/MasterListing/Partials/ManagementFeeSetting.vue";
import {onMounted, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import {IconCircleX} from "@tabler/icons-vue";
import InputError from "@/Components/InputError.vue";
import Skeleton from "primevue/skeleton";

const props = defineProps({
    master: Object
})

const currentFee = ref([]);
const managementFee = ref();
const isLoading = ref(false);
const emit = defineEmits(['update:visible']);

const form = useForm({
    master_id: props.master.id,
    management_fee: null,
});

const getMasterManagementFee = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/master/getMasterManagementFee?master_id=${props.master.id}`);
        currentFee.value = response.data;

    } catch (error) {
        console.error('Error fetching management fee:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    getMasterManagementFee();
})

const submitForm = () => {
    form.management_fee = managementFee.value;
    form.patch(route('master.updateMasterManagementFee'), {
        onSuccess: () => {
            closeDialog();
        }
    })
}

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <div v-if="isLoading">
        <div class="flex flex-col items-center gap-3 self-stretch">
            <!-- Table Header -->
            <div
                class="flex justify-between items-center py-2 self-stretch border-b border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                    {{ $t('public.days') }}
                </div>
                <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                    {{ $t('public.fee_percentage') }} (%)
                </div>
            </div>
            <div class="flex flex-col items-center self-stretch max-h-[200px] overflow-y-auto">
                <div
                    class="flex justify-between gap-3 my-1 items-center self-stretch"
                >
                    <!-- Days Input -->
                    <div class="flex flex-col items-start gap-1 w-full">
                        <Skeleton width="5rem" class="my-2" />
                    </div>

                    <!-- Percentage Input -->
                    <div class="flex flex-col items-start gap-1 w-full">
                        <Skeleton width="5rem" class="my-2" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="currentFee.length > 0">
        <ManagementFeeSetting
            :currentFee="currentFee"
            :errors="form.errors"
            @get:management_fee="managementFee = $event"
        />
    </div>

    <div v-else>
        <ManagementFeeSetting
            :errors="form.errors"
            @get:management_fee="managementFee = $event"
        />
    </div>

    <div class="pt-6 flex gap-5 justify-end items-center self-stretch w-full">
        <Button
            type="button"
            :label="$t('public.cancel')"
            severity="secondary"
            variant="outlined"
            @click="closeDialog"
            :disabled="isLoading || form.processing"
        />

        <Button
            type="submit"
            :label="$t('public.update')"
            @click.prevent="submitForm"
            :disabled="isLoading || form.processing"
        />
    </div>
</template>
