<script setup>
import ToggleSwitch from "primevue/toggleswitch";
import {h, ref, watch} from "vue";
import {useConfirm} from "primevue/useconfirm";
import {router} from "@inertiajs/vue3";
import {trans} from "laravel-vue-i18n";

const props = defineProps({
    application: Object,
});

const checked = ref(props.application.status === 'Active');

watch(() => props.application.status, (newStatus) => {
    checked.value = newStatus === 'Active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_application_form: {
            group: 'headless-success',
            header: trans('public.activate_application_form'),
            text: trans('public.activate_application_form_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.visit(route('application.updateStatus', props.application.id), {
                    method: 'put',
                    data: {
                        id: props.application.id,
                    },
                })

                checked.value = !checked.value;
            }
        },
        deactivate_application_form: {
            group: 'headless-primary',
            header: trans('public.deactivate_application_form'),
            text: trans('public.deactivate_application_form_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.visit(route('application.updateStatus', props.application.id), {
                    method: 'put',
                    data: {
                        id: props.application.id,
                    },
                })

                checked.value = !checked.value;
            }
        },
    };

    const { group, header, text, dynamicText, suffix, actionType, cancelButton, acceptButton, action } = messages[action_type];

    confirm.require({
        group,
        header,
        actionType,
        text,
        dynamicText,
        suffix,
        cancelButton,
        acceptButton,
        accept: action
    });
};

const handleApplicationFormStatus = () => {
    if (props.application.status === 'Active') {
        requireConfirmation('deactivate_application_form');
    } else {
        requireConfirmation('activate_application_form')
    }
}
</script>

<template>
    <ToggleSwitch
        v-model="checked"
        readonly
        @click="handleApplicationFormStatus"
    />
</template>
