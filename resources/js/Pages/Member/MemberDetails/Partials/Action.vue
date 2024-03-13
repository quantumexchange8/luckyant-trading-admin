<script setup>
import Button from "@/Components/Button.vue";
import {CreditEditIcon} from "@/Components/Icons/outline.jsx";
import {ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import WalletAdjustment from "@/Pages/Member/MemberDetails/Partials/WalletAdjustment.vue";
import RebateAdjustment from "@/Pages/Member/MemberDetails/Partials/RebateAdjustment.vue";
import EWalletAdjustment from "@/Pages/Member/MemberDetails/Partials/EWalletAdjustment.vue";
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
    if (componentType === 'cash_wallet') {
        modalComponent.value = 'Wallet Adjustment';
    } else if (componentType === 'bonus_wallet') {
        modalComponent.value = 'Bonus Wallet Adjustment';
    } else if (componentType === 'e_wallet') {
        modalComponent.value = 'E-Wallet Adjustment';
    }
}

const closeModal = () => {
    memberDetailModal.value = false
    modalComponent.value = null;
}

const tooltipContent = computed(() => {
  if (props.wallet.type === 'cash_wallet') {
    return 'Wallet Adjustment';
  } else if (props.wallet.type === 'bonus_wallet') {
    return 'Bonus Wallet';
  } else if (props.wallet.type === 'e_wallet') {
    return 'E-Wallet';
  } else {
    // Handle other cases or return a default value
    return 'Unknown Wallet Type';
  }
});

</script>

<template>
    <div class="flex">
        <Tooltip :content="tooltipContent" placement="right" v-if="type === 'wallet'">
            <Button
                type="button"
                class="justify-center p-1 w-8 h-8 relative focus:outline-none"
                variant="opacity"
                @click="openMemberModal(props.wallet.type)"
                pill
            >
                <CreditEditIcon aria-hidden="true" class="w-5 h-5 absolute" />
                <span class="sr-only">Wallet Adjustment</span>
            </Button>
        </Tooltip>
    </div>


    <Modal :show="memberDetailModal" :title="modalComponent" @close="closeModal" max-width="xl">
        <template v-if="modalComponent === 'Wallet Adjustment'">
            <WalletAdjustment
                :member_detail="member_detail"
                :wallet="wallet"
                @update:memberDetailModal="memberDetailModal = $event"
            />
        </template>
        <template v-if="modalComponent === 'Bonus Wallet Adjustment'">
            <RebateAdjustment
                :member_detail="member_detail"
                :wallet="wallet"
                @update:memberDetailModal="memberDetailModal = $event"
            />
        </template>
        <template v-if="modalComponent === 'E-Wallet Adjustment'">
            <EWalletAdjustment
                :member_detail="member_detail"
                :wallet="wallet"
                @update:memberDetailModal="memberDetailModal = $event"
            />
        </template>
    </Modal>
</template>
