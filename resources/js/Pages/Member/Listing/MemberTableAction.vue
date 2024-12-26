<script setup>
import {h, ref} from "vue";
import {
    IconDotsVertical,
    IconId,
    IconSitemap,
    IconUserDollar,
    IconDeviceLaptop,
    IconChevronRight,
    IconUserUp,
    IconUserDown,
    IconUserCheck,
} from "@tabler/icons-vue";
import TieredMenu from "primevue/tieredmenu";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import ExtraBonus from "@/Pages/Member/Listing/Partials/ExtraBonus.vue";
import EditLeader from "@/Pages/Member/Listing/Partials/EditLeader.vue";
import KycApproval from "@/Pages/Member/Listing/Partials/KycApproval.vue";

const props = defineProps({
    user: Object,
})

const menu = ref();
const visible = ref(false);
const dialogType = ref('');

const items = ref([
    {
        label: 'member_details',
        icon: h(IconId),
        command: () => {
            window.location.href = `/member/member_details/${props.user.id}`;
        },
    },
    {
        label: 'access_portal',
        icon: h(IconDeviceLaptop),
        command: () => {
            window.open(route('member.impersonate', props.user.id), '_blank');
        },
    },
    {
        label: 'member_network',
        icon: h(IconSitemap),
        command: () => {
            window.location.href = `/member/member_affiliates/${props.user.id}`;
        },
    },
    {
        separator: true,
    },
    {
        label: props.user.leader_status ? 'demote_leader' : 'promote_member',
        icon: props.user.leader_status ? h(IconUserDown) : h(IconUserUp),
        command: () => {
            visible.value = true;
            dialogType.value = props.user.leader_status ? 'demote_leader' : 'promote_member';
        },
    },
    {
        label: 'extra_bonus',
        icon: h(IconUserDollar),
        command: () => {
            visible.value = true;
            dialogType.value = 'extra_bonus';
        },
    },
]);

if (props.user.kyc_approval === 'Pending') {
    items.value.push({
        label: 'kyc_approval',
        icon: h(IconUserCheck),
        command: () => {
            visible.value = true;
            dialogType.value = 'kyc_approval';
        },
    });
}

const toggle = (event) => {
    menu.value.toggle(event);
};
</script>

<template>
    <div class="flex items-center justify-center">
        <Button
            type="button"
            severity="secondary"
            rounded
            size="small"
            class="!p-2"
            @click="toggle"
            aria-haspopup="true"
            aria-controls="overlay_tmenu"
        >
            <IconDotsVertical size="12" />
        </Button>
    </div>

    <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
        <template #item="{ item, props, hasSubmenu }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
            >
                <component :is="item.icon" size="20" stroke-width="1.25" :color="item.label === 'delete_member' ? '#F04438' : '#667085'" />
                <span class="font-medium" :class="{'text-error-500': item.label === 'delete_member'}">{{ $t(`public.${item.label}`) }}</span>
                <span v-if="hasSubmenu" class="ml-auto">
                        <IconChevronRight size="20" stroke-width="1.25" />
                    </span>
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-xs sm:dialog-md"
    >
        <template v-if="dialogType === 'extra_bonus'">
            <ExtraBonus
                :user="user"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'demote_leader' || dialogType === 'promote_member'">
            <EditLeader
                :user="user"
                :action="dialogType"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'kyc_approval'">
            <KycApproval
                :user="user"
                :action="dialogType"
                @update:visible="visible = $event"
            />
        </template>
    </Dialog>
</template>
