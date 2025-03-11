<script setup>
import Button from "primevue/button";
import {h, ref} from "vue";
import TieredMenu from "primevue/tieredmenu";
import {IconTrash, IconDotsVertical, IconPencilMinus} from "@tabler/icons-vue";
import UpdateAnnouncementStatus from "@/Pages/Announcement/Partials/UpdateAnnouncementStatus.vue";
import {useConfirm} from "primevue/useconfirm";
import {trans} from "laravel-vue-i18n";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    announcement: Object,
});

const menu = ref();
const visible = ref(false)
const dialogType = ref('')

const items = ref([
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            window.location.href = `/announcement/edit_announcement/${props.announcement.id}`;
        },
    },
    {
        label: 'delete',
        icon: h(IconTrash),
        command: () => {
            requireConfirmation('delete_announcement');
        },
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        delete_announcement: {
            group: 'headless-error',
            header: trans('public.delete_announcement'),
            text: trans('public.delete_announcement_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.visit(route('announcement.deleteAnnouncement', props.announcement.id), {
                    method: 'delete',
                    data: {
                        id: props.announcement.id,
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
        <UpdateAnnouncementStatus
            :announcement="announcement"
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
</template>
