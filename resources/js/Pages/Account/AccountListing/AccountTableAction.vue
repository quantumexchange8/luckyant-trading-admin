<script setup>
import Button from "primevue/button";
import TieredMenu from "primevue/tieredmenu";
import Dialog from "primevue/dialog";
import {
    IconScale,
    IconAdjustmentsDollar,
    IconTrash,
    IconDotsVertical,
    IconChevronRight,
    IconPassword,
} from "@tabler/icons-vue";
import {h, ref} from "vue";
import BalanceAdjustment from "@/Pages/Account/AccountListing/BalanceAdjustment.vue";
import EditLeverage from "@/Pages/Account/AccountListing/EditLeverage.vue";
import ChangePassword from "@/Pages/Account/AccountListing/ChangePassword.vue";
import DeleteAccount from "@/Pages/Account/AccountListing/DeleteAccount.vue";

const props = defineProps({
    account: Object,
})

const menu = ref();
const visible = ref(false);
const dialogType = ref('');

const items = ref([
    {
        label: 'adjust_balance',
        icon: h(IconAdjustmentsDollar),
        command: () => {
            visible.value = true;
            dialogType.value = "adjust_balance";
        },
    },
    {
        label: 'edit_leverage',
        icon: h(IconScale),
        command: () => {
            visible.value = true;
            dialogType.value = "edit_leverage";
        },
    },
    {
        label: 'change_password',
        icon: h(IconPassword),
        command: () => {
            visible.value = true;
            dialogType.value = "change_password";
        },
    },
    {
        separator: true,
    },
    {
        label: 'delete_account',
        icon: h(IconTrash),
        command: () => {
            visible.value = true;
            dialogType.value = "delete_account";
        },
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};
</script>

<template>
    <Button
        type="button"
        severity="secondary"
        size="small"
        rounded
        outlined
        class="!p-2"
        @click="toggle"
        aria-haspopup="true"
        aria-controls="overlay_tmenu"
    >
        <IconDotsVertical size="14" stroke-width="1.5" />
    </Button>
    <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
        <template #item="{ item, props, hasSubmenu }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
            >
                <component
                    :is="item.icon"
                    size="20"
                    stroke-width="1.5"
                    :color="item.label === 'delete_account' ? '#F04438' : '#667085'"
                />
                <span
                    class="font-medium"
                    :class="{'text-error-500': item.label === 'delete_account'}"
                >{{ $t(`public.${item.label}`) }}</span>
                <span
                    v-if="hasSubmenu"
                    class="ml-auto"
                ><IconChevronRight size="20" stroke-width="1.5" /></span>
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-xs sm:dialog-md"
    >
        <template v-if="dialogType === 'adjust_balance'">
            <BalanceAdjustment
                :meta="account"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'edit_leverage'">
            <EditLeverage
                :account="account"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'change_password'">
            <ChangePassword
                :account="account"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'delete_account'">
            <DeleteAccount
                :meta="account"
                @update:visible="visible = $event"
            />
        </template>
    </Dialog>
</template>
