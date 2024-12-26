<script setup>
import Button from "primevue/button";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    user: Object,
    action: String,
});

const getConfirmationMessage = (action) => {
    if (action === 'promote_member') {
        return "promote_member_caption";
    } else if (action === 'demote_leader') {
        return "demote_leader_caption";
    }
    return "Are you sure you want to proceed with this action?";
};

const form = useForm({
    user_id: props.user.id,
    action: props.action,
});

const submitForm = () => {
    form.post(route('member.updateLeaderStatus'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    });
}

const emit = defineEmits(['update:visible']);

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <form>
        <div class="flex flex-col gap-5 items-center self-stretch">
            <div class="flex items-center gap-3 self-stretch bg-gray-200 dark:bg-gray-800 p-4">
                <!--                <div class="w-8 h-8 rounded-full overflow-hidden grow-0 shrink-0">-->
                <!--                    <template v-if="user.profile_photo">-->
                <!--                        <img :src="user.profile_photo" alt="profile_photo">-->
                <!--                    </template>-->
                <!--                </div>-->
                <div class="flex flex-col items-start">
                    <div class="text-sm font-medium">
                        {{ user.name }} <span class="font-normal text-gray-400">@{{ user.username }}</span>
                    </div>
                    <div class="text-gray-500 text-xs">
                        {{ user.email }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-1 text-sm w-full">
                <span class="font-semibold">{{ $t('public.direct_clients') }}: {{ user.children_count }}</span>
                <span class="font-semibold">{{ $t('public.total_clients') }}: {{ user.total_clients }}</span>
                <span>{{ $t(`public.${getConfirmationMessage(action)}`) }}</span>
            </div>

            <div class="flex gap-3 justify-end self-stretch pt-2 w-full">
                <Button
                    type="button"
                    severity="secondary"
                    text
                    @click="closeDialog"
                    :disabled="form.processing"
                    class="w-full md:w-auto px-4"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    @click="submitForm"
                    :disabled="form.processing"
                    class="w-full md:w-auto px-4"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </form>
</template>
