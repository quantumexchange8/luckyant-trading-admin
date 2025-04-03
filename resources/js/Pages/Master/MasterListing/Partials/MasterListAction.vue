<script setup>
import Button from "primevue/button";
import TieredMenu from "primevue/tieredmenu";
import {
    IconDotsVertical,
    IconBusinessplan,
    IconPencilMinus,
    IconFile,
} from "@tabler/icons-vue";
import {h, ref} from "vue";
import Dialog from "primevue/dialog";
import EditMaster from "@/Pages/Master/MasterListing/Partials/EditMaster.vue";
import UpdateManagementFee from "@/Pages/Master/MasterListing/Partials/UpdateManagementFee.vue";
import UpdateMasterTnc from "@/Pages/Master/MasterListing/Partials/UpdateMasterTnc.vue";

const props = defineProps({
    master: Object
});

const menu = ref();
const visible = ref(false)
const dialogType = ref('')

const items = ref([
    {
        label: 'management_fee',
        icon: h(IconBusinessplan),
        command: () => {
            visible.value = true;
            dialogType.value = 'management_fee';
        },
    },
    {
        label: 'tnc',
        icon: h(IconFile),
        command: () => {
            visible.value = true;
            dialogType.value = 'tnc';
        },
    },
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true;
            dialogType.value = 'edit';
        },
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};
</script>

<template>
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
    <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
        <template #item="{ item, props, hasSubmenu }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
            >
                <component :is="item.icon" size="20" stroke-width="1.5" />
                <span class="font-medium">{{ $t(`public.${item.label}`) }}</span>
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        :class="dialogType === 'management_fee' ? 'dialog-xs md:dialog-md' : 'dialog-xs md:dialog-lg'"
    >
        <template v-if="dialogType === 'management_fee'">
            <UpdateManagementFee
                :master="master"
                @update:visible="visible = false"
            />
        </template>

        <template v-if="dialogType === 'tnc'">
            <UpdateMasterTnc
                :master="master"
                @update:visible="visible = false"
            />
        </template>

        <template v-if="dialogType === 'edit'">
            <EditMaster
                :master="master"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>
</template>
