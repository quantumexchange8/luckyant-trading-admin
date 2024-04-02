<script setup>
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import { ref } from "vue";
import { EyeIcon, EyeOffIcon } from '@heroicons/vue/outline'
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {
  RadioGroup,
  RadioGroupLabel,
  RadioGroupDescription,
  RadioGroupOption,
} from '@headlessui/vue'
import AvatarInput from "@/Pages/Member/MemberDetails/Partials/AvatarInput.vue";
import CountryLists from "../../../../../public/data/countries.json";

const props = defineProps({
    member_detail: Object,
    countries: Array,
    nationalities: Array,
})

const plans = [
  {
    name: 'Male',
    value: 'male',
  },
  {
    name: 'Female',
    value: 'female',
  },
]

const selected = ref(plans.find(plan => plan.value === props.member_detail.gender) || plans[0]);
const memberInfo = ref(props.member_detail);

const form = useForm({
    user_id: props.member_detail.id,
    name: '',
    username: '',
    email: '',
    dial_code: '',
    phone: '',
    dob: '',
    country: '',
    gender: '',
    address: '',
    nationality: '',
    identification_number: '',
    profile_photo: null,
})

const formatter = ref({
  date: 'YYYY-MM-DD',
  month: 'MMM'
})

const showPassword = ref(false)
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.name = memberInfo.value.name;
    form.username = memberInfo.value.username;
    form.email = memberInfo.value.email;
    form.dial_code = memberInfo.value.dial_code;
    form.phone = memberInfo.value.phone;
    form.dob = memberInfo.value.dob;
    form.country = memberInfo.value.country;
    form.gender = selected.value.value;
    form.address = memberInfo.value.address_1;
    form.nationality = memberInfo.value.nationality;
    form.identification_number = memberInfo.value.identification_number;
    form.post(route('member.edit_member'), {
        onSuccess: () => {
            form.reset();
        },
    })
}

const openInNewTab = (url) => {
    window.open(url, '_blank');
}

</script>

<template>
    <div class="w-full flex items-end">
        <form class="w-full">
            <div class="flex justify-between items-center self-stretch pb-2">
                <div class="flex items-center gap-4">
                    <div>
                        <AvatarInput class="w-16 h-16 rounded-full" v-model="form.profile_photo" :default-src="member_detail.profile_photo_url ? member_detail.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" />
                        <!-- <img
                        class="object-cover w-16 h-16 rounded-full"
                        :src="props.member_detail.profile_photo_url ? props.member_detail.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                        /> -->
                    </div>
                    <div class="flex flex-col">
                        <div class="font-semibold">
                            {{ member_detail.name }}
                        </div>
                        <div>
                            {{ member_detail.email }}
                        </div>
                    </div>
                </div>
                <div>
                    <Button
                        type="button"
                        variant="success"
                        size="base"
                        class="items-center gap-2 max-w-md"
                        @click="openInNewTab(route('member.impersonate', member_detail.id))"
                    >
                        <span>Access</span>
                    </Button>
                </div>

            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="name" value="Name" />
                    <div class="md:col-span-3">
                        <Input
                            id="name"
                            type="text"
                            class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                            v-model="memberInfo.name"
                            autofocus
                            autocomplete="name"
                            :invalid="form.errors.name"
                        />
                        <InputError :message="form.errors.name" class="mt-1 col-span-4" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="username" value="Username" />
                    <div class="md:col-span-3">
                        <Input
                            id="username"
                            type="text"
                            class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                            v-model="memberInfo.username"
                            autocomplete="username"
                            :invalid="form.errors.username"
                        />
                        <InputError :message="form.errors.username" class="mt-1 col-span-4" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="email" value="Email" />
                    <div class="md:col-span-3">
                        <Input
                            id="email"
                            type="email"
                            class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                            v-model="memberInfo.email"
                        />
                        <InputError :message="form.errors.email" class="mt-1 col-span-4" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label
                        for="phone"
                        :value="$t('public.mobile_phone')"
                    />
                    <div class="flex gap-3">
                        <BaseListbox
                            class="w-[240px]"
                            :options="CountryLists"
                            v-model="memberInfo.dial_code"
                            with-img
                            is-phone-code
                            :error="!!form.errors.phone"
                        />
                        <Input
                            id="phone"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.phone_placeholder')"
                            v-model="memberInfo.phone"
                            :invalid="form.errors.phone"
                        />
                    </div>
                    <InputError :message="form.errors.phone"/>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="dob" value="Date of Birth" />
                    <div class="md:col-span-3">
                        <!-- <Input
                            id="dob"
                            type="text"
                            class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                            v-model="form.dob"
                            :invalid="form.errors.dob"
                        />
                        <InputError :message="form.errors.dob" class="mt-1 col-span-4" /> -->
                        <vue-tailwind-datepicker
                            input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"                            v-model="memberInfo.dob"
                            as-single
                            :formatter="formatter"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="gender" value="Gender" />
                    <div class="md:col-span-3">
                        <RadioGroup v-model="selected">
                            <RadioGroupLabel class="sr-only">Gender</RadioGroupLabel>
                            <div class="flex gap-3 items-center self-stretch w-full">
                                <RadioGroupOption
                                    as="template"
                                    v-for="(plan, index) in plans"
                                    :key="index"
                                    :value="plan"
                                    v-slot="{ active, checked }"
                                >
                                    <div
                                        :class="[
                                            active
                                                ? 'ring-0 ring-white ring-offset-0'
                                                : '',
                                            checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
                                        ]"
                                        class="relative flex cursor-pointer rounded-xl border p-3 focus:outline-none w-full"
                                    >
                                        <div class="flex items-center w-full">
                                            <div class="text-sm flex flex-col gap-3 w-full">
                                                <RadioGroupLabel
                                                    as="div"
                                                    class="font-medium"
                                                >
                                                    <div class="flex justify-center items-center gap-3">
                                                        {{ plan.name }}
                                                    </div>
                                                </RadioGroupLabel>
                                            </div>
                                        </div>
                                    </div>
                                </RadioGroupOption>
                            </div>
                        </RadioGroup>
                        <InputError :message="form.errors.gender" class="mt-1 col-span-4" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="country" value="Country" />
                    <div class="md:col-span-3">
                        <BaseListbox
                            v-model="memberInfo.country"
                            :options="countries"
                        />
                        <InputError :message="form.errors.country" class="mt-1 col-span-4" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="nationality" value="Nationality" />
                    <div class="md:col-span-3">
                        <BaseListbox
                            v-model="memberInfo.nationality"
                            :options="nationalities"
                        />
                        <InputError :message="form.errors.nationality" class="mt-1 col-span-4" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="address" value="Address" />
                    <div class="md:col-span-3">
                        <Input
                            id="address"
                            type="text"
                            class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                            v-model="memberInfo.address_1"

                        />
                        <InputError :message="form.errors.address" class="mt-1 col-span-4" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="identification_number" value="Identity Number" />
                    <div class="md:col-span-3">
                        <Input
                            id="identification_number"
                            type="text"
                            class="flex flex-row items-center gap-3 w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600 px-3 py-0"
                            v-model="memberInfo.identification_number"

                        />
                        <InputError :message="form.errors.identification_number" class="mt-1 col-span-4" />
                    </div>
                </div>

            </div>
            <div class="flex justify-end items-end mt-5">
                <Button
                    variant="primary"
                    :disabled="form.processing"
                    @click.prevent="submit"
                >
                    <span>Save</span>
                </Button>
            </div>
        </form>
    </div>
</template>

<style scoped>

</style>
