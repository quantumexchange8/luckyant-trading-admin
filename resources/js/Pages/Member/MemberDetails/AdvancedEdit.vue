<script setup>
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import {useForm} from "@inertiajs/vue3";
import BaseListbox from "@/Components/BaseListbox.vue";
import Combobox from "@/Components/Combobox.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    member_detail: Object,
    ranks: Array,
})

const form = useForm({
    user_id: props.member_detail.id,
    rank: props.member_detail.setting_rank_id,
    upline_id: props.member_detail.upline ? { value: props.member_detail.upline.id, label: props.member_detail.upline.name } : {},
})

function loadUsers(query, setOptions) {
    fetch('/member/getAllUsers?query=' + query + '&id=' + props.member_detail.id)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(user => {
                    return {
                        value: user.id,
                        label: user.name,
                        img: user.profile_photo
                    }
                })
            )
        });
}

const submit = () => {
    form.patch(route('member.advanceEdit_member'), {
        onSuccess: () => {
            form.reset();
        },
    })
}
</script>

<template>
    <form class="w-full">
        <div class="grid grid-cols-1 gap-3">
            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="rank" value="Rank" />
                <div class="md:col-span-3">
                    <BaseListbox
                        v-model="form.rank"
                        :options="ranks"
                    />
                    <InputError :message="form.errors.rank" class="mt-1 col-span-4" />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="rank" value="Referrer" />
                <div class="md:col-span-3">
                    <Combobox
                        :load-options="loadUsers"
                        v-model="form.upline_id"
                        :error="form.errors.upline_id"
                        image
                    />
                </div>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <Button
                variant="primary"
                :disabled="form.processing"
                @click.prevent="submit"
            >
                <span>Save</span>
            </Button>
        </div>
    </form>
</template>
