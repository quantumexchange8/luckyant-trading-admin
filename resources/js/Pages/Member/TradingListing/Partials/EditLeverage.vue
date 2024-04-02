<script setup>
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
import {ref} from "vue";
import BaseListbox from "@/Components/BaseListbox.vue";

const props = defineProps({
    tradingListing: Object,
    leverageSel: Array,
})

const emit = defineEmits(['update:tradingModal']);

const form = useForm({
    id: props.tradingListing.id,
    margin_leverage: props.tradingListing.margin_leverage,
})

const submit = () => {
    form.post(route('member.edit_leverage'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const closeModal = () => {
    emit('update:tradingModal', false);
}
</script>

<template>
    <div class="grid grid-cols-3 items-center gap-2">
        <Label 
            for="margin_leverage"
            value="Margin Leverage"
        />
        <div class="flex flex-col w-full col-span-2">
            <BaseListbox
                    :options="leverageSel"
                    v-model="form.margin_leverage"
                />
            <InputError :message="form.errors.margin_leverage" class="mt-2" />
        </div>
    </div>
    <div class="grid grid-cols-1 items-center gap-2">
        <div class="mt-6 flex justify-end">
            <Button
                type="button"
                variant="transparent"
                @click="closeModal">
                {{ $t('public.cancel') }}
            </Button>
            <Button
                variant="primary"
                class="ml-3"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                @click="submit"
            >
                {{ $t('public.process') }}
            </Button>
        </div>
    </div>

</template>