<script setup>
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {useForm} from "@inertiajs/vue3";
import {ref, watch} from "vue";
// import {IdNoIcon, PhoneIcon} from "@/Components/Icons/outline.jsx";
import { MailIcon, KeyIcon, EyeOffIcon, EyeIcon } from '@heroicons/vue/outline';
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";
// import Combobox from "@/Components/Combobox.vue";
import Label from "@/Components/Label.vue";

const props = defineProps({
    settingRanks: Array,
    countries: Array
})
const emit = defineEmits(['update:memberDetailModal']);

const form = useForm({
    name: "",
    country: null,
    phone: "",
    email: "",
    verification_type: "nric",
    identity_number: "",
    password: "",
    ranking: "",
    upline_id: {},
})

const showPassword = ref(false);
const user = ref()
function loadUsers(query, setOptions) {
    fetch('/member/getAllUsers?query=' + query)
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

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.upline_id = user.value;
    form.post(route('member.addMember'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const selectedCountry = ref(form.country);

function onchangeDropdown() {
    const selectedCountryName = selectedCountry.value;
    const country = props.countries.find((country) => country.label === selectedCountryName);

    if (country) {
        form.phone = `${country.phone_code}`;
        form.country = selectedCountry;
    }
}

watch(selectedCountry, () => {
    onchangeDropdown();
});

const closeModal = () => {
    emit('update:memberDetailModal', false);
}
</script>

<template>
    <form
        @submit.prevent="submit"
    >
        <div class="flex flex-col gap-4 md:gap-8 mb-8">
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="name" value="Name" />
                <div class="md:col-span-3">
                    <Input
                        id="name"
                        type="text"
                        class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                        placeholder="Enter full name"
                        v-model="form.name"
                        autofocus
                        :class="form.errors.name ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        autocomplete="name"
                    />
                    <InputError :message="form.errors.name" class="mt-1 col-span-4" />
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="email" value="Email" />
                <div class="md:col-span-3">
                    <InputIconWrapper class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600">
                        <template #icon>
                            <MailIcon aria-hidden="true" class="w-5 h-5 dark:text-gray-400" />
                        </template>
                        <Input
                                withIcon id="email" type="email" class="w-full"  placeholder="Email" v-model="form.email" autocomplete="email"
                                :class="form.errors.email ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        />
                    </InputIconWrapper>
                    <InputError :message="form.errors.email" class="mt-1 col-span-4" />
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="country" value="Country" />
                <div class="md:col-span-3">
                    <BaseListbox
                        v-model="selectedCountry"
                        :options="props.countries"
                        :error="form.errors.country"
                    />
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="phone" value="Phone Number" />
                <div class="md:col-span-3">
                    <InputIconWrapper class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600">
                        <template #icon>
                            <PhoneIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input
                            withIcon id="phone" type="text" class="w-full"  placeholder="Phone" v-model="form.phone" autocomplete="phone"
                            :class="form.errors.phone ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        />
                    </InputIconWrapper>
                    <InputError :message="form.errors.phone" class="mt-1 col-span-4" />
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="verification_type" value="Verification Type" />
                <div class="flex gap-x-12">
                    <div class="flex">
                        <input type="radio" name="verification_type" v-model="form.verification_type" class="shrink-0 mt-0.5 border-gray-200 rounded-full text-blue-600 focus:ring-pink-500 dark:bg-gray-800 dark:border-gray-400 dark:checked:bg-pink-500 dark:checked:border-pink-500 dark:focus:ring-offset-gray-800" id="hs-radio-group-1" value="nric">
                        <label for="hs-radio-group-1" class="text-sm text-gray-300 ml-2 dark:text-white">NRIC</label>
                    </div>

                    <div class="flex">
                        <input type="radio" name="verification_type" v-model="form.verification_type" class="shrink-0 mt-0.5 border-gray-200 rounded-full text-blue-600 focus:ring-pink-500 dark:bg-gray-800 dark:border-gray-400 dark:checked:bg-pink-500 dark:checked:border-pink-500 dark:focus:ring-offset-gray-800" id="hs-radio-group-2" value="passport">
                        <label for="hs-radio-group-2" class="text-sm text-gray-500 ml-2 dark:text-white">Passport</label>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="identity_number" value="Identity Number" />
                <div class="md:col-span-3">
                    <InputIconWrapper class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600">
                        <template #icon>
                            <IdNoIcon aria-hidden="true" class="w-5 h-5 dark:text-gray-400" />
                        </template>
                        <Input
                                withIcon id="identity_number" type="text" class="w-full"  placeholder="Identity Number" v-model="form.identity_number" autocomplete="identity_number"
                                :class="form.errors.identity_number ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        />
                    </InputIconWrapper>
                    <InputError :message="form.errors.identity_number" class="mt-1 col-span-4" />
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="password" value="Password" />
                <div class="md:col-span-3">
                    <InputIconWrapper class="relative flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600">
                        <template #icon>
                            <KeyIcon aria-hidden="true" class="w-5 h-5 dark:text-gray-400" />
                        </template>
                        <Input
                            withIcon
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            :class="form.errors.password ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                            class="block w-full"
                            placeholder="Minimum 8 characters"
                            v-model="form.password"
                            autocomplete="current-password"
                        />
                        <div
                            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                            @click="togglePasswordVisibility"
                        >
                            <template v-if="showPassword">
                                <EyeIcon aria-hidden="true" class="w-5 h-5 dark:text-gray-400" />
                            </template>
                            <template v-else>
                                <EyeOffIcon aria-hidden="true" class="w-5 h-5 dark:text-gray-400" />
                            </template>
                        </div>
                    </InputIconWrapper>
                    <InputError :message="form.errors.password" class="mt-1 col-span-4" />
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="rankID" value="Ranking" />
                <div class="md:col-span-3">
                    <BaseListbox
                        id="rankID"
                        class="w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600"
                        :class="form.errors.ranking? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.ranking"
                        :options="props.settingRanks"
                        placeholder = "Please Select"
                    />
                    <InputError :message="form.errors.ranking" class="mt-1 col-span-4" />
                </div>
            </div>
            <div class="flex flex-col gap-1 md:grid md:grid-cols-4">
                <Label class="text-sm dark:text-white" for="upline" value="Upline" />
                <div class="md:col-span-3">
                    <Combobox
                        :load-options="loadUsers"
                        v-model="user"
                        :error="form.errors.upline_id"
                        image
                    />
                    <InputError :message="form.errors.upline_id" class="mt-1 col-span-4" />
                </div>
            </div>
        </div>
        <div class="flex pt-8 gap-3 justify-end border-t dark:border-gray-700">
            <Button variant="secondary" class="px-4 py-2 justify-center" @click="closeModal">
                <span class="text-sm font-semibold">Cancel</span>
            </Button>
            <Button variant="primary" class="px-4 py-2 justify-center" :disabled="form.processing">
                <span class="text-sm font-semibold">Confirm</span>
            </Button>
        </div>
    </form>
</template>
