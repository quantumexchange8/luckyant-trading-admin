<script setup>
import {CheckIcon, XIcon, QuestionMarkCircleIcon, ClipboardListIcon} from "@heroicons/vue/outline";
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import {ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import Input from "@/Components/Input.vue";
import Label from "@/Components/Label.vue";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import CountryLists from "../../../../../public/data/countries.json";
import VueTailwindDatepicker from "vue-tailwind-datepicker";

const props = defineProps({
    member: Object,
    countries: Array,
    nationalities: Array,
})

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MMM'
})

const kycApprovalModal = ref(false);
const modalComponent = ref('');
const approvalType = ref('');
const selectedCountry = ref(props.member.country.id);
const selectedNationality = ref(props.member.nationality);
const { formatDateTime } = transactionFormat();

const openKycApprovalModal = (ibId, componentType) => {
    kycApprovalModal.value = true;
    if (componentType === 'approve') {
        approvalType.value = 'approve'
        modalComponent.value = 'Approve KYC';
    } else if (componentType === 'reject') {
        approvalType.value = 'reject'
        modalComponent.value = 'Reject KYC';
    } else if (componentType === 'view') {
        modalComponent.value = 'KYC Details';
    }
}

const form = useForm({
    id: props.member.id,
    type: '',
    remark: '',
});

const genders = [
    {
        name: 'male',
        value: 'male',
    },
    {
        name: 'female',
        value: 'female',
    },
]

const getUserGender = (user_gender) => {
    return genders.find(gender => gender.value === user_gender);
}
const selected = ref(getUserGender(props.member.gender));

watch(selectedCountry, (newCountry) => {
    const foundNationality = props.nationalities.find(nationality => nationality.id === newCountry);

    if (foundNationality) {
        selectedNationality.value = foundNationality.value;
    } else {
        selectedNationality.value = null; // Reset if not found
    }
})

const closeModal = () => {
    kycApprovalModal.value = false
    modalComponent.value = null;
}

const submitForm = () => {
    form.type = approvalType.value;
    form.post(route('member.verify_member'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const userForm = useForm({
    user_id: props.member.id,
    name: props.member.name,
    username: props.member.username,
    email: props.member.email,
    dob: props.member.dob,
    dial_code: props.member.dial_code,
    phone: props.member.phone,
    gender: props.member.gender,
    address: props.member.address_1,
    country: '',
    nationality: '',
    identification_number: props.member.identification_number,
});

const handleButton = (type) => {
    userForm.gender = selected.value ? selected.value.value : null;
    userForm.country = selectedCountry.value;
    userForm.nationality = selectedNationality.value;
    userForm.post(route('member.validateKyc'), {
        onSuccess: () => {
            // If form submission is successful
            if (type === 'reject') {
                approvalType.value = 'reject';
                modalComponent.value = 'Reject KYC';
            } else if (type === 'approve') {
                approvalType.value = 'approve';
                modalComponent.value = 'Approve KYC';
            }
        },
    });
};
</script>

<template>
    <div class="inline-flex justify-center items-center gap-2">
        <Tooltip content="Approve" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="success"
                @click="openKycApprovalModal(member.id, 'approve')"
            >
                <CheckIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">View</span>
            </Button>
        </Tooltip>
        <Tooltip content="Reject" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="danger"
                @click="openKycApprovalModal(member.id, 'reject')"
            >
                <XIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">Transfer Upline</span>
            </Button>
        </Tooltip>
        <Tooltip content="View" placement="bottom">
            <Button
                type="button"
                pill
                class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
                variant="gray"
                @click="openKycApprovalModal(member.id, 'view')"
            >
                <ClipboardListIcon aria-hidden="true" class="w-6 h-6 absolute" />
                <span class="sr-only">Reset</span>
            </Button>
        </Tooltip>
    </div>

    <Modal :show="kycApprovalModal" :title="modalComponent" @close="closeModal" :max-width="modalComponent === 'KYC Details' ? '4xl' : 'lg'">

        <div v-if="modalComponent === 'Approve KYC'">
            <div class="px-2 space-y-2">
                <div class="flex justify-center">
                    <div class="flex items-center justify-center rounded-full w-14 h-14 grow-0 shrink-0 bg-warning-300">
                        <QuestionMarkCircleIcon class="w-14 text-white" />
                    </div>
                </div>
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve KYC</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve KYC and verify this member?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">Confirm</Button>
            </div>
        </div>

        <!-- Reject -->
        <div v-if="modalComponent === 'Reject KYC'">
            <div class="flex gap-2 mt-3 mb-8">
                <Label class="text-sm dark:text-white w-1/4 pt-0.5" for="remark" value="Remark" />
                <div class="flex flex-col w-full">
                    <Input
                        id="remark"
                        type="text"
                        placeholder="Enter remark (visible to member)"
                        class="block w-full"
                        :class="form.errors.remark ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.remark"
                    />
                    <InputError :message="form.errors.remark" class="mt-2" />
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4 border-t dark:border-gray-700">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="submitForm">Confirm</Button>
            </div>
        </div>

        <!-- View -->
        <div v-if="modalComponent === 'KYC Details'">
            <div class="flex justify-center">
                <img :src="member.profile_photo_url ? member.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" class="w-20 h-20 rounded-full" alt="">
            </div>
            <div class="py-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="name" :value="$t('public.name')" />

                        <Input
                            id="name"
                            type="text"
                            class="block w-full"
                            v-model="userForm.name"
                            autofocus
                            autocomplete="name"
                        />

                        <InputError class="mt-2" :message="userForm.errors.name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="username" :value="$t('public.username')" />

                        <Input
                            id="username"
                            type="text"
                            class="block w-full"
                            v-model="userForm.username"
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="userForm.errors.username" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="email" :value="$t('public.email')" />

                        <Input
                            id="email"
                            type="email"
                            class="block w-full"
                            v-model="userForm.email"
                            autocomplete="email"
                        />

                        <InputError class="mt-2" :message="userForm.errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="phone"
                            :value="$t('public.mobile_phone')"
                        />
                        <div class="flex gap-3">
                            <BaseListbox
                                class="w-[240px]"
                                :options="CountryLists"
                                v-model="userForm.dial_code"
                                with-img
                                is-phone-code
                                :error="!!userForm.errors.phone"
                            />
                            <Input
                                id="phone"
                                type="text"
                                class="block w-full"
                                :placeholder="$t('public.phone_placeholder')"
                                v-model="userForm.phone"
                                :invalid="userForm.errors.phone"
                            />
                        </div>
                        <InputError :message="userForm.errors.phone"/>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-sm dark:text-white" for="dob" value="Date of Birth" />
                        <div class="md:col-span-3">
                            <vue-tailwind-datepicker
                                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                                v-model="userForm.dob"
                                as-single
                                :formatter="formatter"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="gender"
                            :value="$t('public.gender')"
                        />
                        <RadioGroup v-model="selected">
                            <RadioGroupLabel class="sr-only">{{ $t('public.signal_status') }}</RadioGroupLabel>
                            <div class="flex gap-3 items-center self-stretch w-full">
                                <RadioGroupOption
                                    as="template"
                                    v-for="(gender, index) in genders"
                                    :key="index"
                                    :value="gender"
                                    v-slot="{ active, checked }"
                                >
                                    <div
                                        :class="[
                                    active
                                      ? 'ring-0 ring-white ring-offset-0'
                                      : '',
                                    checked ? 'border-gray-400 dark:border-white bg-gray-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                                                        {{ $t('public.' + gender.name) }}
                                                    </div>
                                                </RadioGroupLabel>
                                            </div>
                                        </div>
                                    </div>
                                </RadioGroupOption>
                            </div>
                            <InputError :message="userForm.errors.gender" class="mt-2" />
                        </RadioGroup>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="country"
                            :value="$t('public.country')"
                        />
                        <BaseListbox
                            v-model="selectedCountry"
                            :options="countries"
                            :placeholder="$t('public.country')"
                            class="w-full"
                            :error="!!userForm.errors.country"
                        />
                        <InputError class="mt-2" :message="userForm.errors.country" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="nationality"
                            :value="$t('public.nationality')"
                        />
                        <BaseListbox
                            v-model="selectedNationality"
                            :options="nationalities"
                            :placeholder="$t('public.nationality')"
                            class="w-full"
                            :error="!!userForm.errors.nationality"
                        />
                        <InputError class="mt-2" :message="userForm.errors.nationality" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="address" :value="$t('public.address')" />
                        <Input
                            id="address"
                            type="text"
                            class="block w-full"
                            v-model="userForm.address"
                            autocomplete="address"
                            :invalid="userForm.errors.address"
                        />
                        <InputError class="mt-2" :message="userForm.errors.address" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="identification_number" :value="$t('public.identification_no')" />
                        <Input
                            id="identification_number"
                            type="text"
                            class="block w-full"
                            v-model="userForm.identification_number"
                            :invalid="userForm.errors.identification_number"
                        />
                        <InputError class="mt-2" :message="userForm.errors.identification_number" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="text-sm text-left w-full dark:text-white">
                            Proof of Identity (FRONT)
                        </div>
                        <div class="p-2 dark:bg-white rounded-lg w-full flex justify-center border dark:border-white">
                            <img :src="member.front_identity" class="max-h-64 rounded-lg" alt="">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="text-sm text-left w-full dark:text-white">
                            Proof of Identity (BACK)
                        </div>
                        <div class="p-2 dark:bg-white rounded-lg w-full flex justify-center border dark:border-white">
                            <img :src="member.back_identity" class="max-h-64 rounded-lg" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="pt-8 border-t dark:border-gray-700">
                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="danger"
                        class="flex justify-center px-6"
                        @click="handleButton('reject')"
                    >
                        Reject
                    </Button>
                    <Button
                        type="button"
                        variant="success"
                        class="flex justify-center px-6"
                        @click="handleButton('approve')"
                    >
                        Approve KYC
                    </Button>
                </div>
            </div>
        </div>

    </Modal>
</template>
