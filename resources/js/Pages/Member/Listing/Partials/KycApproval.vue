<script setup>
import {useForm} from "@inertiajs/vue3";
import dayjs from "dayjs";
import Tag from "primevue/tag";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import CountryLists from "../../../../../../public/data/countries.json";
import {ref, watch} from "vue";
import {XIcon} from "@/Components/Icons/outline.jsx";
import {IconArrowNarrowLeft, IconCircleCheckFilled} from "@tabler/icons-vue";
import Image from "primevue/image";
import Textarea from "primevue/textarea";

const props = defineProps({
    user: Object,
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
];

const countries = ref([]);
const selectedDialCode = ref(props.user.dial_code);
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/getCountries');
        countries.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

getCountries();

const selectedCountry = ref(props.user.of_country);
const selectedNationality = ref(props.user.nationality);

watch(selectedCountry, (newCountry) => {
    const foundNationality = countries.value.find(nationality => nationality.id === newCountry.id);

    if (foundNationality) {
        selectedNationality.value = foundNationality.nationality;
    } else {
        selectedNationality.value = null; // Reset if not found
    }
})


const selectedDate = ref(props.user.dob);

const clearJoinDate = () => {
    selectedDate.value = null;
}

const selectedGender = ref(props.user.gender);
const selectGender = (type) => {
    selectedGender.value = type.value;
}

const form = useForm({
    user_id: props.user.id,
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
    dob: '',
    dial_code: props.user.dial_code,
    phone: props.user.phone,
    gender: '',
    address: props.user.address_1,
    country: '',
    nationality: '',
    identification_number: props.user.identification_number,
    action: '',
    remarks: ''
});

const emit = defineEmits(['update:visible'])

const submitForm = (action) => {
    form.action = action;
    form.dob = selectedDate.value;
    form.dial_code = selectedDialCode.value.value ? selectedDialCode.value.value : selectedDialCode.value;
    form.country = selectedCountry.value.id;
    form.nationality = selectedNationality.value;
    form.gender = selectedGender.value;
    form.post(route('member.verify_member'), {
        onSuccess: () => {
            emit('update:visible', false);
        }
    })
}
</script>

<template>
    <form action="">
        <div class="flex flex-col gap-5 self-stretch divide-y dark:divide-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                <!-- Name -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="name"
                        :value="$t('public.name')"
                    />
                    <InputText
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        placeholder="Enter name"
                        :invalid="!!form.errors.name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="email"
                        :value="$t('public.email')"
                    />
                    <InputText
                        id="email"
                        type="text"
                        class="block w-full"
                        v-model="form.email"
                        placeholder="Enter email"
                        disabled
                        :invalid="!!form.errors.email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Username -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="username"
                        :value="$t('public.username')"
                    />
                    <InputText
                        id="username"
                        type="text"
                        class="block w-full"
                        v-model="form.username"
                        placeholder="Enter username"
                        :invalid="!!form.errors.username"
                    />
                    <InputError :message="form.errors.username" />
                </div>

                <!-- Phone number -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="phone"
                        :value="$t('public.phone_number')"
                    />
                    <div class="flex gap-3">
                        <Select
                            v-model="selectedDialCode"
                            :options="CountryLists"
                            optionLabel="label"
                            :placeholder="$t('public.select_country')"
                            class="w-36"
                            filter
                            :filter-fields="['label', 'value', 'code']"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value.value">
                                    <div>{{ slotProps.value.value }}</div>
                                </div>
                                <div v-else-if="slotProps.value">
                                    <div>{{ slotProps.value }}</div>
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex gap-2">
                                    <img :src="slotProps.option.imgUrl" width="24" alt="img">
                                    <div>
                                        {{ slotProps.option.label }}
                                    </div>
                                    <div>
                                        ({{ slotProps.option.value }})
                                    </div>
                                </div>
                            </template>
                        </Select>
                        <InputText
                            id="phone_number"
                            type="text"
                            class="block w-full"
                            v-model="form.phone"
                            placeholder="Enter phone number"
                            :invalid="!!form.errors.phone"
                        />
                    </div>
                    <InputError :message="form.errors.phone" />
                </div>

                <!-- Country -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="country"
                        :value="$t('public.country')"
                    />
                    <Select
                        v-model="selectedCountry"
                        :options="countries"
                        optionLabel="name"
                        :placeholder="$t('public.select_country')"
                        class="w-full"
                        filter
                        :filter-fields="['name']"
                        :loading="loadingCountries"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center">
                                {{ slotProps.value.name }}
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div>{{ slotProps.option.name }}</div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.country" />
                </div>

                <!-- Nationality -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="nationality"
                        :value="$t('public.nationality')"
                    />
                    <Select
                        v-model="selectedNationality"
                        :options="countries"
                        optionLabel="name"
                        :placeholder="$t('public.select_nationality')"
                        class="w-full"
                        filter
                        :filter-fields="['name']"
                        :loading="loadingCountries"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value.nationality">
                                <div>{{ slotProps.value.nationality }}</div>
                            </div>
                            <div v-else-if="slotProps.value">
                                <div>{{ slotProps.value }}</div>
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div>{{ slotProps.option.nationality }}</div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.nationality" />
                </div>

                <!-- DOB -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="dob"
                        :value="$t('public.date_of_birth')"
                    />
                    <div class="relative w-full">
                        <DatePicker
                            v-model="selectedDate"
                            dateFormat="yy-mm-dd"
                            class="w-full"
                            placeholder="yyyy-mm-dd"
                        />
                        <div
                            v-if="selectedDate"
                            class="absolute top-2/4 -mt-2 right-2 text-gray-400 select-none cursor-pointer bg-transparent"
                            @click="clearJoinDate"
                        >
                            <XIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <InputError :message="form.errors.dob" />
                </div>

                <!-- Gender -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="gender"
                        :value="$t('public.gender')"
                    />
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 items-start gap-3 self-stretch"
                    >
                        <div
                            v-for="gender in genders"
                            @click="selectGender(gender)"
                            class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                            :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedGender === gender.value,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedGender !== gender.value,
                                }"
                        >
                            <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedGender === gender.value,
                                        'text-gray-950 dark:text-white': selectedGender !== gender.value
                                    }"
                                >
                                    {{ $t(`public.${gender.value}`) }}
                                </span>
                                <IconCircleCheckFilled v-if="selectedGender === gender.value" size="20" stroke-width="1.25" color="#2970FF" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="address"
                        :value="$t('public.address')"
                    />
                    <InputText
                        id="address"
                        type="text"
                        class="block w-full"
                        v-model="form.address"
                        placeholder="Enter address"
                        :invalid="!!form.errors.address"
                    />
                    <InputError :message="form.errors.address" />
                </div>

                <!-- Identification No -->
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="identification_number"
                        :value="$t('public.ic_passport')"
                    />
                    <InputText
                        id="identification_number"
                        type="text"
                        class="block w-full"
                        v-model="form.identification_number"
                        placeholder="Enter identification_number"
                        :invalid="!!form.errors.identification_number"
                    />
                    <InputError :message="form.errors.identification_number" />
                </div>
            </div>

            <!-- KYC Files -->
            <div class="pt-3 grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                <div class="flex flex-col gap-1 items-center self-stretch">
                    <span class="font-bold text-sm text-surface-950 dark:text-white w-full text-left">{{ $t('public.proof_of_identity') }} ({{ $t('public.front') }})</span>
                    <div v-for="item in user.media">
                        <Image
                            v-if="item.collection_name === 'front_identity'"
                            :src="item.original_url"
                            alt="front_identity"
                            image-class="object-cover rounded w-full h-[250px]"
                            preview
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-1 items-center self-stretch">
                    <span class="font-bold text-sm text-surface-950 dark:text-white w-full text-left">{{ $t('public.proof_of_identity') }} ({{ $t('public.back') }})</span>
                    <div v-for="item in user.media">
                        <Image
                            v-if="item.collection_name === 'back_identity'"
                            :src="item.original_url"
                            alt="back_identity"
                            image-class="object-cover rounded w-full h-[250px]"
                            preview
                        />
                    </div>
                </div>

                <div
                    class="md:col-span-2 flex flex-col gap-1 items-start self-stretch w-full"
                >
                    <InputLabel for="remarks">{{ $t('public.remarks') }}</InputLabel>
                    <Textarea
                        id="remarks"
                        type="text"
                        class="flex flex-1 self-stretch w-full"
                        v-model="form.remarks"
                        placeholder="Enter remarks"
                        :invalid="!!form.errors.remarks"
                        rows="5"
                        cols="30"
                    />
                    <InputError :message="form.errors.remarks"/>
                </div>
            </div>
        </div>
        <div class="pt-5 flex items-center gap-5 justify-end self-stretch w-full">
            <Button
                type="button"
                severity="danger"
                class="w-full md:w-auto px-4"
                :disabled="form.processing"
                @click="submitForm('reject')"
            >
                {{ $t('public.reject') }}
            </Button>

            <Button
                type="button"
                severity="success"
                :disabled="form.processing"
                class="w-full md:w-auto px-4"
                @click="submitForm('approve')"
            >
                {{ $t('public.approve') }}
            </Button>
        </div>
    </form>
</template>
