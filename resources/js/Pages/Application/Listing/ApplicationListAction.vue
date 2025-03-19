<script setup>
import Button from "primevue/button";
import {IconDotsVertical, IconPencilMinus, IconTrash} from "@tabler/icons-vue";
import UpdateApplicationStatus from "@/Pages/Application/Listing/Partials/UpdateApplicationStatus.vue";
import TieredMenu from "primevue/tieredmenu";
import {h, ref} from "vue";
import {useConfirm} from "primevue/useconfirm";
import {trans} from "laravel-vue-i18n";
import {router} from "@inertiajs/vue3";
import Dialog from "primevue/dialog";
import UpdateApplicationForm from "@/Pages/Application/Listing/Partials/UpdateApplicationForm.vue";

const props = defineProps({
    application: Object,
});

const menu = ref();
const visible = ref(false)
const dialogType = ref('')

const items = ref([
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true
            dialogType.value = "edit_application";
        },
    },
    {
        label: 'delete',
        icon: h(IconTrash),
        command: () => {
            requireConfirmation('delete_application_form');
        },
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        delete_application_form: {
            group: 'headless-error',
            header: trans('public.delete_application_form'),
            text: trans('public.delete_application_form_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.visit(route('application.deleteApplication', props.application.id), {
                    method: 'delete',
                    data: {
                        id: props.application.id,
                    },
                })
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
</script>

<template>
    <div class="flex gap-2 items-center self-stretch">
        <UpdateApplicationStatus
            :application="application"
        />
        <Button
            severity="secondary"
            type="button"
            rounded
            text
            @click="toggle"
            aria-haspopup="true"
            aria-controls="overlay_tmenu"
        >
            <IconDotsVertical size="16" stroke-width="1.25" color="#667085" />
        </Button>
    </div>

    <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
        <template #item="{ item, props, hasSubmenu }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
            >
                <component :is="item.icon" size="20" stroke-width="1.5" :color="item.label === 'delete' ? '#F04438' : '#667085'" />
                <span class="font-medium" :class="{'text-error-500': item.label === 'delete'}">{{ $t(`public.${item.label}`) }}</span>
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-xs md:dialog-lg"
    >
        <template v-if="dialogType === 'edit_application'">
            <UpdateApplicationForm
                :application="application"
                @update:visible="visible = $event"
            />
        </template>
    </Dialog>
</template>
