<script setup>
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import {useForm} from "@inertiajs/vue3";
import BaseListbox from "@/Components/BaseListbox.vue";
import {ref} from "vue";
import { EyeIcon, EyeOffIcon } from '@heroicons/vue/outline'

const props = defineProps({
    member_detail: Object,
    countries: Array,
})

const form = useForm({
    name: props.member_detail.name,
    email: props.member_detail.email,
    phone: props.member_detail.phone,
    dob: props.member_detail.dob,
    country: props.member_detail.country,
    password: '',
})

const showPassword = ref(false)
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <form class="w-full">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="name" value="Name" />
                <div class="md:col-span-3">
                    <Input
                        id="name"
                        type="text"
                        class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                        v-model="form.name"
                        autofocus
                        autocomplete="name"
                        :invalid="form.errors.name"
                    />
                    <InputError :message="form.errors.name" class="mt-1 col-span-4" />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="email" value="Email" />
                <div class="md:col-span-3">
                    <Input
                        id="email"
                        type="email"
                        class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                        v-model="form.email"
                        disabled
                    />
                    <InputError :message="form.errors.email" class="mt-1 col-span-4" />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="country" value="Country" />
                <div class="md:col-span-3">
                    <BaseListbox
                        v-model="form.country"
                        :options="countries"
                    />
                    <InputError :message="form.errors.country" class="mt-1 col-span-4" />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="phone" value="Phone" />
                <div class="md:col-span-3">
                    <Input
                        id="phone"
                        type="text"
                        class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                        v-model="form.phone"
                        :invalid="form.errors.phone"
                    />
                    <InputError :message="form.errors.phone" class="mt-1 col-span-4" />
                </div>
            </div>

            <div class="space-y-2">
                <Label class="text-sm dark:text-white" for="dob" value="Date of Birth" />
                <div class="md:col-span-3">
                    <Input
                        id="dob"
                        type="text"
                        class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                        v-model="form.dob"
                        :invalid="form.errors.dob"
                    />
                    <InputError :message="form.errors.dob" class="mt-1 col-span-4" />
                </div>
            </div>

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
        </div>
    </form>
</template>

<style scoped>

</style>
