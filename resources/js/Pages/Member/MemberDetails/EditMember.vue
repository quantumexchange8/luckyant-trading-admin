<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Card from "primevue/card";
import {useForm} from "@inertiajs/vue3";
import Button from "primevue/button";
import DatePicker from "primevue/datepicker";
import AvatarInput from "@/Pages/Member/MemberDetails/Partials/AvatarInput.vue";
import {onMounted, ref} from "vue";
import CountryLists from "../../../../../public/data/countries.json";
import {
    IconCircleCheckFilled
} from "@tabler/icons-vue";
import dayjs from "dayjs";

const props = defineProps({
    member: Object,
});

const form = useForm({
    user_id: props.member.id,
    name: props.member.name,
    username: props.member.username,
    email: props.member.email,
    dial_code: props.member.dial_code,
    phone: props.member.phone,
    dob: props.member.dob,
    gender: props.member.gender,
    country: '',
    nationality: '',
    address: props.member.address,
    identification_number: props.member.identification_number,
    profile_photo: null,
});

const selectedCountry = ref();
const selectedNationality = ref();
const countries = ref([]);
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try{
        const response = await axios.get('/getCountries');
        countries.value = response.data;
        selectedCountry.value = countries.value.find(country => country.id === props.member.country) || null;
        selectedNationality.value = countries.value.find(country => country.nationality === props.member.nationality) || null;
    } catch(error){
        console.error('Error fetching selectedCountry:', error);
    } finally {
        loadingCountries.value = false;
    }
}

const genders = [
    'male',
    'female',
]

const selectedGender = ref(form.gender);
const selectGender = (type) => {
    selectedGender.value = type;
}

onMounted(() => {
    getCountries();
});

const selectedDob = ref(form.dob);

const submitForm = () => {
    form.gender = selectedGender.value;
    form.country = selectedCountry.value?.id;
    form.nationality = selectedNationality.value?.nationality;
    form.dob = dayjs(selectedDob.value).format("YYYY-MM-DD");
    form.post(route('member.edit_member'))
}

const openInNewTab = (url) => {
    window.open(url, '_blank');
}
</script>

<template>
    <Card>
        <template #content>
            <form class="flex flex-col gap-3 items-center self-stretch w-full">
                <div class="flex flex-col gap-3 md:flex-row justify-between md:items-center self-stretch">
                    <div class="flex items-center gap-4">
                        <div>
                            <AvatarInput class="w-16 h-16 rounded-full" v-model="form.profile_photo" :default-src="member.profile_photo_url ? member.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" />
                        </div>
                        <div class="flex flex-col">
                            <div class="font-semibold dark:text-white">
                                {{ member.name }}
                            </div>
                            <div class="text-gray-500">
                                {{ member.email }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <Button
                            type="button"
                            severity="success"
                            size="small"
                            class="items-center gap-2 w-full md:w-auto"
                            :label="$t('public.access_portal')"
                            @click.prevent="openInNewTab(route('member.impersonate', member.id))"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 w-full">
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="name"
                            :invalid="!!form.errors.name"
                        >
                            {{ $t('public.name') }}
                        </InputLabel>
                        <InputText
                            id="name"
                            type="text"
                            class="block w-full"
                            v-model="form.name"
                            :placeholder="$t('public.enter_name')"
                            :invalid="!!form.errors.name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="username"
                            :invalid="!!form.errors.username"
                        >
                            {{ $t('public.username') }}
                        </InputLabel>
                        <InputText
                            id="username"
                            type="text"
                            class="block w-full"
                            v-model="form.username"
                            :placeholder="$t('public.enter_username')"
                            :invalid="!!form.errors.username"
                        />
                        <InputError :message="form.errors.username" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="email"
                            :invalid="!!form.errors.email"
                        >
                            {{ $t('public.email') }}
                        </InputLabel>
                        <InputText
                            id="email"
                            type="text"
                            class="block w-full"
                            v-model="form.email"
                            :placeholder="$t('public.enter_email')"
                            :invalid="!!form.errors.email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="phone"
                            :invalid="!!form.errors.phone"
                        >
                            {{ $t('public.phone_number') }}
                        </InputLabel>
                        <div class="flex gap-2 items-center self-stretch relative">
                            <Select
                                v-model="form.dial_code"
                                :options="CountryLists"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Phone Code"
                                class="w-[120px]"
                                :invalid="!!form.errors.dial_code"
                                filter
                                :filterFields="['label', 'value', 'code']"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <div>{{ slotProps.value }}</div>
                                    </div>
                                    <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-1">
                                        <img
                                            :src="slotProps.option.imgUrl"
                                            :alt="slotProps.option.iso2"
                                            width="18"
                                            height="12"
                                        />
                                        <div>{{ slotProps.option.value }}</div>
                                        <div class="max-w-[200px] uppercase truncate text-gray-500">({{ slotProps.option.code }})</div>
                                    </div>
                                </template>
                            </Select>

                            <InputText
                                id="phone"
                                type="text"
                                class="block w-full"
                                v-model="form.phone"
                                placeholder="Phone Number"
                                :invalid="!!form.errors.phone"
                            />
                        </div>
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="dob"
                            :invalid="!!form.errors.dob"
                        >
                            {{ $t('public.date_of_birth') }}
                        </InputLabel>
                        <DatePicker
                            v-model="selectedDob"
                            class="w-full"
                            dateFormat="yy-mm-dd"
                        />
                        <InputError :message="form.errors.dob" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="gender"
                            :invalid="!!form.errors.gender"
                        >
                            {{ $t('public.gender') }}
                        </InputLabel>
                        <div class="flex items-start gap-3 self-stretch w-full overflow-x-auto">
                            <div
                                v-for="gender in genders"
                                @click="selectGender(gender)"
                                class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                                :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedGender === gender,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedGender !== gender,
                                }"
                            >
                                <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500 uppercase"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedGender === gender,
                                        'text-gray-950 dark:text-white': selectedGender !== gender
                                    }"
                                >
                                    {{ gender }}
                                </span>
                                    <IconCircleCheckFilled v-if="selectedGender === gender" size="20" stroke-width="1.25" color="#2970FF" />
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.gender" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="country"
                            :invalid="!!form.errors.country"
                        >
                            {{ $t('public.country') }}
                        </InputLabel>
                        <Select
                            v-model="selectedCountry"
                            :options="countries"
                            optionLabel="name"
                            placeholder="Choose a country"
                            class="w-full"
                            :invalid="!!form.errors.country"
                            filter
                            :filterFields="['name', 'iso2']"
                            :loading="loadingCountries"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ slotProps.value.name }}</div>
                                </div>
                                <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center gap-1">
                                    <img
                                        v-if="slotProps.option.iso2"
                                        :src="`https://flagcdn.com/w40/${slotProps.option.iso2.toLowerCase()}.png`"
                                        :alt="slotProps.option.iso2"
                                        width="18"
                                        height="12"
                                    />
                                    <div>{{ slotProps.option.name }}</div>
                                    <div class="max-w-[200px] uppercase truncate text-gray-500">({{ slotProps.option.iso2 }})</div>
                                </div>
                            </template>
                        </Select>
                        <InputError :message="form.errors.country" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="nationality"
                            :invalid="!!form.errors.nationality"
                        >
                            {{ $t('public.nationality') }}
                        </InputLabel>

                        <Select
                            v-model="selectedNationality"
                            :options="countries"
                            optionLabel="name"
                            placeholder="Choose a country"
                            class="w-full"
                            :invalid="!!form.errors.nationality"
                            filter
                            :filterFields="['name', 'iso2']"
                            :loading="loadingCountries"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ slotProps.value.nationality }}</div>
                                </div>
                                <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center gap-1">
                                    <img
                                        v-if="slotProps.option.iso2"
                                        :src="`https://flagcdn.com/w40/${slotProps.option.iso2.toLowerCase()}.png`"
                                        :alt="slotProps.option.iso2"
                                        width="18"
                                        height="12"
                                    />
                                    <div>{{ slotProps.option.name }}</div>
                                    <div class="max-w-[200px] uppercase truncate text-gray-500">({{ slotProps.option.iso2 }})</div>
                                </div>
                            </template>
                        </Select>
                        <InputError :message="form.errors.nationality" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="address"
                            :invalid="!!form.errors.address"
                        >
                            {{ $t('public.address') }}
                        </InputLabel>
                        <InputText
                            id="address"
                            type="text"
                            class="block w-full"
                            v-model="form.address"
                            :placeholder="$t('public.address')"
                            :invalid="!!form.errors.address"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="identification_number"
                            :invalid="!!form.errors.identification_number"
                        >
                            {{ $t('public.ic_passport') }}
                        </InputLabel>
                        <InputText
                            id="identification_number"
                            type="text"
                            class="block w-full"
                            v-model="form.identification_number"
                            :placeholder="$t('public.enter_identification_number')"
                            :invalid="!!form.errors.identification_number"
                        />
                        <InputError :message="form.errors.identification_number" />
                    </div>
                </div>

                <div class="flex justify-end w-full pt-5">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        @click.prevent="submitForm"
                        class="px-10 w-full md:w-auto"
                        size="small"
                    >
                        <span>Save</span>
                    </Button>
                </div>
            </form>
        </template>
    </Card>
</template>
