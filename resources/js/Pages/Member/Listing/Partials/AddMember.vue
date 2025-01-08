<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import {ref, watch} from "vue";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import InputText from "primevue/inputtext";
import DatePicker from "primevue/datepicker";
import Select from "primevue/select";
import Password from "primevue/password";
import {useForm} from "@inertiajs/vue3";
import CountryLists from "../../../../../../public/data/countries.json";
import {XIcon} from "@/Components/Icons/outline.jsx";
import {IconCircleCheckFilled} from "@tabler/icons-vue";
import {useLangObserver} from "@/Composables/localeObserver.js";

const visible = ref(false);
const {locale} = useLangObserver();

const openDialog = () => {
    visible.value = true;
    getUsers();
    getCountries();
    getRanks();
}

const form = useForm({
    name: '',
    username: '',
    email: '',
    country: null,
    nationality: null,
    dial_code: null,
    phone: '',
    dob: '',
    gender: '',
    address: '',
    identification_number: '',
    upline_id: '',
    rank: '',
    password: '',
});

const countries = ref([]);
const selectedCountry = ref();
const selectedNationality = ref();
const selectedPhoneCode = ref();
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

const clearJoinDate = () => {
    form.dob = '';
}

const genders = ref(['male', 'female']);

const selectedGender = ref('');
const selectGender = (type) => {
    selectedGender.value = type;
}

const users = ref([]);
const selectedUpline = ref();
const loadingUsers = ref(false);

const getUsers = async () => {
    loadingUsers.value = true;
    try {
        const response = await axios.get('/getUsers?user_type=all');
        users.value = response.data;
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loadingUsers.value = false;
    }
};

const ranks = ref();
const selectedRank = ref();
const loadingRanks = ref(false);

const getRanks = async () => {
    loadingRanks.value = true;
    try {
        const response = await axios.get('/getRanks');
        ranks.value = response.data;
        selectedRank.value = ranks.value[0];
    } catch (error) {
        console.error('Error fetching ranks:', error);
    } finally {
        loadingRanks.value = false;
    }
};

watch(selectedCountry, (newCountry) => {
    selectedNationality.value = newCountry;
})

const submitForm = () => {
    form.dial_code = selectedPhoneCode.value?.value;
    form.nationality = selectedNationality.value?.nationality;
    form.country = selectedCountry.value?.id;
    form.gender = selectedGender.value;
    form.upline_id = selectedUpline.value?.id;
    form.rank = selectedRank.value?.id;
    form.post(route('member.addMember'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            selectedPhoneCode.value = null;
            selectedGender.value = null;
            selectedCountry.value = null;
            selectedNationality.value = null;
            selectedUpline.value = null;
            selectedRank.value = null;
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        size="small"
        label="Add member"
        class="w-full md:max-w-32"
        @click="openDialog"
    />

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.add_member')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="flex flex-col items-center gap-8 self-stretch">

                <!-- Basic Information -->
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="text-gray-950 dark:text-white font-semibold text-sm self-stretch">
                        {{ $t('public.basic_information') }}
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
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
                                    v-model="selectedPhoneCode"
                                    :options="CountryLists"
                                    optionLabel="label"
                                    :placeholder="$t('public.code')"
                                    class="!w-28"
                                    filter
                                    :filter-fields="['label', 'value', 'code']"
                                    :invalid="!!form.errors.dial_code || !!form.errors.phone"
                                >
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value">
                                            <div>{{ slotProps.value.value }}</div>
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
                                    placeholder="12345678"
                                    :invalid="!!form.errors.phone"
                                />
                            </div>
                            <InputError :message="form.errors.dial_code || form.errors.phone" />
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
                                :invalid="!!form.errors.country"
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
                                optionLabel="nationality"
                                :placeholder="$t('public.select_nationality')"
                                class="w-full"
                                filter
                                :filter-fields="['nationality']"
                                :loading="loadingCountries"
                                :invalid="!!form.errors.nationality"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        {{ slotProps.value.nationality }}
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
                                    v-model="form.dob"
                                    dateFormat="yy-mm-dd"
                                    class="w-full"
                                    placeholder="yyyy-mm-dd"
                                    :invalid="!!form.errors.dob"
                                />
                                <div
                                    v-if="form.dob"
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
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedGender === gender,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedGender !== gender,
                                }"
                                >
                                    <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedGender === gender,
                                        'text-gray-950 dark:text-white': selectedGender !== gender
                                    }"
                                >
                                    {{ $t(`public.${gender}`) }}
                                </span>
                                        <IconCircleCheckFilled v-if="selectedGender === gender" size="20" stroke-width="1.25" color="#2970FF" />
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.gender" />
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
                                :placeholder="$t('public.enter_identification_number')"
                                :invalid="!!form.errors.identification_number"
                            />
                            <InputError :message="form.errors.identification_number" />
                        </div>
                    </div>
                </div>

                <!-- Member Assignment -->
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="text-gray-950 dark:text-white font-semibold text-sm self-stretch">
                        {{ $t('public.member_assignment') }}
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                        <!-- Upline -->
                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="upline"
                                :value="$t('public.upline')"
                            />
                            <Select
                                v-model="selectedUpline"
                                :options="users"
                                filter
                                :filter-fields="['name', 'username', 'email']"
                                optionLabel="name"
                                :placeholder="$t('public.select_upline')"
                                class="w-full"
                                :loading="loadingUsers"
                                :invalid="!!form.errors.upline_id"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <div>{{ slotProps.value.name }}</div>
                                    </div>
                                    <span v-else>{{ slotProps.placeholder }}</span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-1 w-full truncate">
                                        <span>{{ slotProps.option.name }}</span>
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.upline_id" />
                        </div>

                        <!-- Upline -->
                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="upline"
                                :value="$t('public.rank')"
                            />
                            <Select
                                v-model="selectedRank"
                                :options="ranks"
                                optionLabel="name"
                                :placeholder="$t('public.select_rank')"
                                class="w-full"
                                filter
                                :filter-fields="['name']"
                                :loading="loadingRanks"
                                :invalid="!!form.errors.rank"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        {{ slotProps.value.name[locale] }}
                                    </div>
                                    <span v-else>{{ slotProps.placeholder }}</span>
                                </template>
                                <template #option="slotProps">
                                    <div>{{ slotProps.option.name[locale] }}</div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.rank" />
                        </div>
                    </div>
                </div>

                <!-- Credentials -->
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="text-gray-950 dark:text-white font-semibold text-sm self-stretch">
                        {{ $t('public.credentials') }}
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                        <!-- Password -->
                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="password"
                                :value="$t('public.password')"
                                :invalid="!!form.errors.password"
                            />
                            <Password
                                v-model="form.password"
                                toggleMask
                                :invalid="!!form.errors.password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-5 flex items-center gap-5 justify-end self-stretch w-full">
                <Button
                    type="button"
                    severity="secondary"
                    class="w-full md:w-auto px-4"
                    :disabled="form.processing"
                    @click="closeDialog"
                >
                    {{ $t('public.cancel') }}
                </Button>

                <Button
                    type="button"
                    :disabled="form.processing"
                    class="w-full md:w-auto px-4"
                    @click="submitForm"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
