<script setup>
import Button from "@/Components/Button.vue";
import {CogIcon} from "@heroicons/vue/solid";
import {ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import {WarningIcon} from "@/Components/Icons/outline.jsx";
import Modal from "@/Components/Modal.vue";
import Tooltip from "@/Components/Tooltip.vue";

const props = defineProps({
    user_id: Number,
    enable_bank_withdrawal: Number
})

const updateBankWithdrawalModal = ref(false);
const withdrawalSetting = ref(props.enable_bank_withdrawal);
const form = useForm({
    user_id: props.user_id,
    bank_status: withdrawalSetting.value,
})

watch(() => props.user_id, (newUserId) => {
    form.user_id = newUserId;
});

const updateGroup = () => {
    const temp = withdrawalSetting.value === 1 ? 0 : 1;
    form.bank_status = temp;

    form.post(route('setting.updateBankWithdrawalSetting'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
            form.reset();
            // location.reload();
        },
    })
}

const openModal = () => {
    updateBankWithdrawalModal.value = true
}

const closeModal = () => {
    updateBankWithdrawalModal.value = false
}

</script>

<template>
    <Tooltip
        content="Change Bank Withdrawal Setting"
        placement="bottom"
    >
        <Button
            type="button"
            variant="primary"
            size="sm"
            class="items-center gap-2 max-w-md"
            v-slot="{ iconSizeClasses }"
            @click="openModal"
        >
            <CogIcon aria-hidden="true" :class="iconSizeClasses" />
        </Button>
    </Tooltip>

    <Modal :show="updateBankWithdrawalModal" title="Update Bank Withdrawal Setting" max-width="2xl" @close="closeModal">
        <div>
            <WarningIcon aria-hidden="true" class="w-12 h-12" />
        </div>
        <div class="mt-5">
            <h1 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">
                Change Bank Withdrawal Setting
            </h1>
            <p class="dark:text-gray-400 text-sm">
                Are you sure you want to change the bank withdrawal setting for this group?
            </p>
        </div>
        <div class="mt-5 flex gap-3 justify-center">
            <Button variant="secondary" class="px-6 w-1/2 justify-center" @click="closeModal">
                <span class="text-sm font-semibold">Cancel</span>
            </Button>
            <Button v-if="props.enable_bank_withdrawal === 0" class="px-6 w-1/2 justify-center" variant="primary" @click.prevent="updateGroup" :disabled="form.processing">
                <span class="text-sm font-semibold">Enable</span>
            </Button>
            <Button v-else class="px-6 w-1/2 justify-center" variant="danger" @click.prevent="updateGroup" :disabled="form.processing">
                <span class="text-sm font-semibold">Disable</span>
            </Button>
        </div>
    </Modal>

</template>
