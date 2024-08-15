<script setup>
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import {useForm} from "@inertiajs/vue3";
import BaseListbox from "@/Components/BaseListbox.vue";
import Combobox from "@/Components/Combobox.vue";
import Button from "@/Components/Button.vue";
import { EyeIcon, EyeOffIcon } from '@heroicons/vue/outline'
import { ref } from "vue";

const props = defineProps({
    member_detail: Object,
    ranks: Array,
})

const memberInfo = ref(props.member_detail);
const memberUpline = ref(props.member_detail.upline ? { value: props.member_detail.upline.id, label: props.member_detail.upline.email } : {});

const form = useForm({
    user_id: props.member_detail.id,
    rank: '',
    upline_id: '',
    password: '',
    leader_status: '',
    is_public:'',
})

const showPassword = ref(false)
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

function loadUsers(query, setOptions) {
    fetch('/member/getAllUsers?query=' + query + '&id=' + props.member_detail.id)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(user => {
                    return {
                        value: user.id,
                        label: user.email,
                        img: user.profile_photo
                    }
                })
            )
        });
}

const submit = () => {
    form.rank = memberInfo.value.display_rank_id;
    form.upline_id = memberUpline.value;
    form.leader_status = memberInfo.value.leader_status;
    form.is_public = memberInfo.value.is_public;
    form.patch(route('member.advanceEdit_member'), {
        onSuccess: () => {
            form.reset();
        },
    })
}

const leaderStatus = [
    { label: 'Yes', value: 1},
    { label: 'No', value: 0},
];

const groupStatus = [
    { label: 'Public', value: 1},
    { label: 'Private', value: 0},
];
</script>

<template>
    <form class="w-full">
        <div class="grid grid-cols-1 gap-3">

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="password" value="Password" />
                <div class="md:col-span-3">
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full"
                            placeholder="New password"
                            :invalid="form.errors.password"
                            v-model="form.password"
                        />
                        <div
                            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                            @click="togglePasswordVisibility"
                        >
                            <template v-if="showPassword">
                                <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                            </template>
                            <template v-else>
                                <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                            </template>
                        </div>
                    </div>

                    <InputError :message="form.errors.password" class="mt-1 col-span-4" />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="rank" value="Rank" />
                <div class="md:col-span-3">
                    <BaseListbox
                        v-model="memberInfo.display_rank_id"
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
                        v-model="memberUpline"
                        :error="form.errors.upline_id"
                        image
                    />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="leader_status" value="Leader Status" />
                <div class="md:col-span-3">
                    <BaseListbox
                        v-model="memberInfo.leader_status"
                        :options="leaderStatus"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="group_status" value="Group Status" />
                <div class="md:col-span-3">
                    <BaseListbox
                        v-model="memberInfo.is_public"
                        :options="groupStatus"
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
