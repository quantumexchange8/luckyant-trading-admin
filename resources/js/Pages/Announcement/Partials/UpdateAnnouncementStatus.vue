<script setup>
import ToggleSwitch from "primevue/toggleswitch";
import {h, ref, watch} from "vue";
import {useConfirm} from "primevue/useconfirm";
import {router} from "@inertiajs/vue3";
import {IconUserCancel, IconUserCheck} from "@tabler/icons-vue";
import {trans} from "laravel-vue-i18n";

const props = defineProps({
    announcement: Object,
});

const checked = ref(props.announcement.status === 'Active');

watch(() => props.announcement.status, (newStatus) => {
    checked.value = newStatus === 'Active';
});

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_announcement: {
            group: 'headless-success',
            header: trans('public.activate_announcement'),
            text: trans('public.activate_announcement_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.visit(route('announcement.updateStatus', props.announcement.id), {
                    method: 'put',
                    data: {
                        id: props.announcement.id,
                    },
                })

                checked.value = !checked.value;
            }
        },
        deactivate_announcement: {
            group: 'headless-primary',
            header: trans('public.deactivate_announcement'),
            text: trans('public.deactivate_announcement_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.visit(route('announcement.updateStatus', props.announcement.id), {
                    method: 'put',
                    data: {
                        id: props.announcement.id,
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

const handleAnnouncementStatus = () => {
    if (props.announcement.status === 'Active') {
        requireConfirmation('deactivate_announcement');
    } else {
        requireConfirmation('activate_announcement')
    }
}
</script>

<template>
    <ToggleSwitch
        v-model="checked"
        readonly
        @click="handleAnnouncementStatus"
    />
</template>
