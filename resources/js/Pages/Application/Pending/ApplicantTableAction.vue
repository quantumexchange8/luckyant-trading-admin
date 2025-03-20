<script setup>
import Button from "primevue/button";
import {ref} from "vue";
import Dialog from "primevue/dialog";
import dayjs from "dayjs";
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/Label.vue";
import Textarea from "primevue/textarea";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    applicant: Object,
});

const visible = ref(false);

const openDialog = () => {
    visible.value = true;
}

const form = useForm({
    applicant_id: props.applicant.id,
    action: '',
    remarks: ''
})

const submitForm = (action) => {
    form.action = action;
    form.post(route('application.updateApplicationApproval'), {
        onSuccess: () => {
            form.reset();
            closeDialog();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        severity="secondary"
        size="small"
        @click="openDialog"
    >
        {{ applicant.status === 'pending' ? $t('public.action') : $t('public.view') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="applicant.status === 'pending' ? $t('public.request_approval') : $t('public.view_details')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col gap-3 items-center self-stretch">
            <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.applicant') }}</span>

            <div class="flex flex-col gap-1 items-start w-full">
                <!-- Name -->
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.name') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ applicant.name }}
                    </div>
                </div>

                <!-- Email -->
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.email') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ applicant.email }}
                    </div>
                </div>

                <!-- Gender -->
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.gender') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ $t(`public.${applicant.gender}`) }}
                    </div>
                </div>

                <!-- Country -->
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.country') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        <div
                            v-if="applicant.country"
                            class="flex items-center gap-1"
                        >
                            <img
                                v-if="applicant.country.iso2"
                                :src="`https://flagcdn.com/w40/${applicant.country.iso2.toLowerCase()}.png`"
                                :alt="applicant.country.iso2"
                                width="18"
                                height="12"
                            />
                            <div class="leading-tight">
                                {{ applicant.country.name }}
                            </div>
                        </div>
                        <span v-else class="text-surface-400 dark:text-surface-500">-</span>
                    </div>
                </div>

                <!-- Phone -->
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.phone_number') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ applicant.phone_number }}
                    </div>
                </div>

                <!-- ID No -->
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.ic_passport_number') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ applicant.identity_number }}
                    </div>
                </div>

                <!-- Ticket Type -->
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.ticket_type') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ $t(`public.${applicant.ticket_type}`) }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-center self-stretch w-full pt-3">
                <span class="font-medium text-sm text-gray-600 dark:text-gray-400 w-full text-left">{{ $t('public.flight_information') }}</span>

                <!-- Transport details -->
                <div
                    v-if="applicant.requires_transport"
                    class="flex flex-col items-start gap-1 self-stretch"
                >
                    <!-- Name -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.name') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            {{ applicant.transport_detail.name }}
                        </div>
                    </div>

                    <!-- Gender -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.gender') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            {{ $t(`public.${applicant.transport_detail.gender}`) }}
                        </div>
                    </div>

                    <!-- Country -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.country') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            <div
                                v-if="applicant.transport_detail.country"
                                class="flex items-center gap-1"
                            >
                                <img
                                    v-if="applicant.transport_detail.country.iso2"
                                    :src="`https://flagcdn.com/w40/${applicant.transport_detail.country.iso2.toLowerCase()}.png`"
                                    :alt="applicant.transport_detail.country.iso2"
                                    width="18"
                                    height="12"
                                />
                                <div class="leading-tight">
                                    {{ applicant.transport_detail.country.name }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOB -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.date_of_birth') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            {{ dayjs(applicant.transport_detail.dob).format('YYYY-MM-DD') }}
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.phone_number') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            {{ applicant.transport_detail.phone_number }}
                        </div>
                    </div>

                    <!-- ID No -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.ic_passport_number') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            {{ applicant.transport_detail.identity_number }}
                        </div>
                    </div>

                    <!-- Departure -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.departure_address') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            {{ applicant.transport_detail.departure_address }}
                        </div>
                    </div>

                    <!-- Departure -->
                    <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                        <div class="w-[140px] text-gray-500 text-xs font-medium">
                            {{ $t('public.return_address') }}
                        </div>
                        <div class="text-gray-950 dark:text-white text-sm font-medium">
                            {{ applicant.transport_detail.return_address }}
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.flight') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ $t('public.no') }}
                    </div>
                </div>
            </div>

            <div v-if="applicant.status === 'pending'" class="flex flex-col items-start gap-1 self-stretch pt-4">
                <InputLabel for="remarks" :value="$t('public.remarks')" />
                <Textarea
                    id="remarks"
                    type="text"
                    v-model="form.remarks"
                    :invalid="!!form.errors.remarks"
                    :placeholder="$t('public.remarks')"
                    class="block w-full"
                    rows="5"
                    cols="30"
                />
                <InputError :message="form.errors.remarks" />
            </div>
        </div>

        <div v-if="applicant.status === 'pending'" class="flex gap-3 justify-between self-stretch pt-5 w-full">
            <Button
                type="button"
                severity="danger"
                class="w-full"
                size="small"
                :disabled="form.processing"
                @click="submitForm('reject')"
                :label="$t('public.reject')"
            />
            <Button
                type="submit"
                severity="success"
                class="w-full"
                size="small"
                :disabled="form.processing"
                @click.prevent="submitForm('approve')"
                :label="$t('public.approve')"
            />
        </div>
    </Dialog>
</template>
