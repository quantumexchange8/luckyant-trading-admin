<script setup>
import {ref} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {MemberDetailIcon, Edit, PasscodeLockIcon} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import EditLeverage from "@/Pages/Member/TradingListing/Partials/EditLeverage.vue";
import ChangePassword from "@/Pages/Member/TradingListing/Partials/ChangePassword.vue";

const props = defineProps({
    tradingListing: Object,
    leverageSel: Array,
})

const tradingModal = ref(false);
const modalComponent = ref(null);
const { formatDateTime, formatAmount, formatType } = transactionFormat();

const openTradingModal = (id, componentType) => {
    tradingModal.value = true;
    if (componentType === 'view') {
        modalComponent.value = 'View Details';
    } else if (componentType === 'change_password') {
        modalComponent.value = 'Change Password';
    } else if (componentType === 'edit_leverage') {
        modalComponent.value = 'Edit Leverage';
    }
}

const closeModal = () => {
    tradingModal.value = false
    modalComponent.value = null;
}

const form = useForm({
    id: props.tradingListing.id,
    margin_leverage: props.tradingListing.margin_leverage,
    master_password: '',
    investor_password: '',
})

const submit = () => {
    form.post(route('member.edit_leverage'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const showPassword = ref(false);
const showPassword2 = ref(false);

const toggleMasterPasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const toggleMasterPasswordVisibilityConfirm = () => {
    showPassword2.value = !showPassword2.value;
}
</script>

<template>
    <Tooltip content="Edit Leverage" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openTradingModal(tradingListing.id, 'edit_leverage')"
        >
            <Edit aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">Edit Leverage</span>
        </Button>
    </Tooltip>
    <Tooltip content="Change Password" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openTradingModal(tradingListing.id, 'change_password')"
        >
            <PasscodeLockIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">Change Password</span>
        </Button>
    </Tooltip>
    <Tooltip content="View" placement="bottom">
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openTradingModal(tradingListing.id, 'view')"
        >
            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
            <span class="sr-only">View</span>
        </Button>
    </Tooltip>

    <Modal :show="tradingModal" :title="modalComponent" @close="closeModal" max-width="lg">
        <div v-if="modalComponent === 'View Details'">
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Trading Account</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ tradingListing.meta_login }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Trading User Name</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ tradingListing.trading_user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Balance</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ formatAmount(tradingListing.balance) }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Equity</span>
                <span class="col-span-2 text-black dark:text-white py-2">$ {{ formatAmount(tradingListing.equity) }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Margin Leverage</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ tradingListing.margin_leverage }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">User</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ tradingListing.user.name }}</span>
            </div>
            <div class="grid grid-cols-3 items-center gap-2">
                <span class="col-span-1 text-sm font-semibold dark:text-gray-400">User</span>
                <span class="col-span-2 text-black dark:text-white py-2">{{ tradingListing.user.email }}</span>
            </div>
        </div>

        <div v-if="modalComponent === 'Edit Leverage'">
            <EditLeverage
                :leverageSel="leverageSel"
                :tradingListing="tradingListing"
            />
        </div>

        <div v-if="modalComponent === 'Change Password'">
            <ChangePassword 
                :tradingListing="tradingListing"
                :leverageSel="leverageSel"
            />
        </div>
    </Modal>
</template>