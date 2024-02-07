<script setup>
import Button from "@/Components/Button.vue";
import {CreditEditIcon} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import WalletAdjustment from "@/Pages/Member/MemberDetails/Partials/WalletAdjustment.vue";
import Tooltip from "@/Components/Tooltip.vue";

const props = defineProps({
    member_detail: Object,
    type: String,
    wallet: Object,
})

const memberDetailModal = ref(false);
const modalComponent = ref(null);

const openMemberModal = (componentType) => {
    memberDetailModal.value = true;
    if (componentType === 'wallet_adjustment') {
        modalComponent.value = 'Wallet Adjustment';
    }
}

const closeModal = () => {
    memberDetailModal.value = false
    modalComponent.value = null;
}

</script>

<template>
    <div class="flex">
        <Tooltip content="Wallet Adjustment" placement="right" v-if="type === 'wallet'">
            <Button
                type="button"
                class="justify-center p-1 w-8 h-8 relative focus:outline-none"
                variant="opacity"
                @click="openMemberModal('wallet_adjustment')"
                pill
            >
                <CreditEditIcon aria-hidden="true" class="w-5 h-5 absolute" />
                <span class="sr-only">Wallet Adjustment</span>
            </Button>
        </Tooltip>
    </div>


    <Modal :show="memberDetailModal" :title="modalComponent" @close="closeModal" max-width="xl">
        <template v-if="modalComponent==='Wallet Adjustment'">
            <WalletAdjustment
                :member_detail="member_detail"
                :wallet="wallet"
                @update:memberDetailModal="memberDetailModal = $event"
            />
        </template>
    </Modal>
</template>
