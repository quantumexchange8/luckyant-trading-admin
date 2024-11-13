<script setup>
import {TrashIcon} from "@heroicons/vue/outline";
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import {Edit} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import UpdatePaymentGateway from "@/Pages/Setting/PaymentGateway/Partials/UpdatePaymentGateway.vue";
import DeletePaymentGateway from "@/Pages/Setting/PaymentGateway/Partials/DeletePaymentGateway.vue";

const props = defineProps({
    paymentGateway: Object
})

const visible = ref(false);
const modalType = ref('');

const openModal = (modal_type) => {
    visible.value = true;
    modalType.value = modal_type;
}

const closeModal = () => {
    visible.value = false;
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

    <Modal
        :show="visible"
        :title="$t(`public.${modalType}`)"
        @close="closeModal"
        max-width="xl"
    >
        <template v-if="modalType === 'edit'">
            <UpdatePaymentGateway
                :paymentGateway="paymentGateway"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="modalType === 'delete'">
            <DeletePaymentGateway
                :paymentGateway="paymentGateway"
                @update:visible="visible = $event"
            />
        </template>
    </Modal>
</template>
