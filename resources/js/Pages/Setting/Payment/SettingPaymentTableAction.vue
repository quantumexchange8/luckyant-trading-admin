<script setup>
import {TrashIcon} from "@heroicons/vue/outline";
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import {Edit} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import Dialog from "primevue/dialog";
import UpdateSettingPayment from "@/Pages/Setting/Payment/Partials/UpdateSettingPayment.vue";
import DeleteSettingPayment from "@/Pages/Setting/Payment/Partials/DeleteSettingPayment.vue";

const props = defineProps({
    settingPayment: Object
})

const visible = ref(false);
const modalType = ref('');

const openModal = (modal_type) => {
    visible.value = true;
    modalType.value = modal_type;
}
</script>

<template>
    <div class="flex justify-center">
        <Tooltip content="Edit Details" placement="bottom">
            <Button
                type="button"
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="gray"
                pill
                @click="openModal('edit')"
            >
                <Edit aria-hidden="true" class="w-5 h-5 absolute" />
                <span class="sr-only">View Details</span>
            </Button>
        </Tooltip>
        <Tooltip content="Delete" placement="bottom">
            <Button
                type="button"
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="danger"
                pill
                @click="openModal('delete')"
            >
                <TrashIcon aria-hidden="true" class="w-5 h-5 absolute" />
                <span class="sr-only">Delete</span>
            </Button>
        </Tooltip>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${modalType}`)"
        class="dialog-xs md:dialog-md"
    >
        <template v-if="modalType === 'edit'">
            <UpdateSettingPayment
                :settingPayment="settingPayment"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="modalType === 'delete'">
            <DeleteSettingPayment
                :settingPayment="settingPayment"
                @update:visible="visible = $event"
            />
        </template>
    </Dialog>
</template>
