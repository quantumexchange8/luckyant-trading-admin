<script setup>
import Skeleton from "primevue/skeleton";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    user: Object,
});

const isLoading = ref(false);
const extraBonuses = ref([]);
const {formatAmount} = transactionFormat();
const emit = defineEmits(['update:visible']);

const getExtraBonus = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/member/getExtraBonus?user_id=${props.user.id}`);
        extraBonuses.value = response.data;
    } catch (error) {
        console.error('Error fetching leverages:', error);
    } finally {
        isLoading.value = false;
    }
}

getExtraBonus();

const form = useForm({
    account_type_id: 1,
    user_id: props.user.id,
    extra_bonus: null,
});

const accountTypes = ref([]);
const selectedAccountType = ref(1);
const loadingAccountTypes = ref(false);

const getAccountTypes = async () => {
    loadingAccountTypes.value = true;
    try {
        const response = await axios.get('/getAccountTypes');
        accountTypes.value = response.data;
    } catch (error) {
        console.error('Error fetching account types:', error);
    } finally {
        loadingAccountTypes.value = false;
    }
};

getAccountTypes();

const submitForm = () => {
    form.account_type_id = selectedAccountType.value;
    form.post(route('member.updateExtraBonus'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    });
}

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <form>
        <div class="flex flex-col gap-5 items-center self-stretch">
            <div class="flex items-center gap-3 self-stretch bg-gray-200 dark:bg-gray-800 p-4">
<!--                <div class="w-8 h-8 rounded-full overflow-hidden grow-0 shrink-0">-->
<!--                    <template v-if="user.profile_photo">-->
<!--                        <img :src="user.profile_photo" alt="profile_photo">-->
<!--                    </template>-->
<!--                </div>-->
                <div class="flex flex-col items-start">
                    <div class="text-sm font-medium">
                        {{ user.name }} <span class="font-normal text-gray-400">@{{ user.username }}</span>
                    </div>
                    <div class="text-gray-500 text-xs">
                        {{ user.email }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center gap-3 self-stretch">
                <!-- Table Header -->
                <div
                    class="flex justify-between items-center py-2 self-stretch border-b border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                        {{ $t('public.account_type') }}
                    </div>
                    <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                        {{ $t('public.extra_bonus') }} ($)
                    </div>
                </div>

                <!-- Loading state -->
                <div v-if="isLoading" class="flex flex-col items-center self-stretch max-h-[200px] overflow-y-auto">
                    <div
                        class="flex justify-between gap-3 my-1 items-center self-stretch"
                    >
                        <!-- Days Input -->
                        <div class="flex flex-col items-start gap-1 w-full">
                            <Skeleton width="5rem" class="my-2" />
                        </div>

                        <!-- Percentage Input -->
                        <div class="flex flex-col items-start gap-1 w-full">
                            <Skeleton width="5rem" class="my-2" />
                        </div>
                    </div>
                </div>

                <div v-else class="w-full">
                    <!-- Empty state -->
                    <div
                        v-if="extraBonuses.length === 0"
                        class="flex flex-col items-center self-stretch max-h-[200px] overflow-y-auto"
                    >
                        <div
                            class="flex justify-between gap-3 my-1 items-center self-stretch"
                        >
                            <div class="flex flex-col items-start gap-1 w-full">
                                -
                            </div>

                            <div class="flex flex-col items-start gap-1 w-full">
                                -
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center self-stretch max-h-[200px] overflow-y-auto">
                        <div
                            v-for="bonus in extraBonuses"
                            class="flex justify-between gap-3 my-1 items-center self-stretch"
                        >
                            <div class="flex flex-col items-start gap-1 w-full">
                                {{ bonus ? bonus.account_type.name : '-' }}
                            </div>

                            <div class="flex flex-col items-start gap-1 w-full">
                                {{ formatAmount(bonus.extra_bonus) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 self-stretch">
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="account_type"
                        :value="$t('public.account_type')"
                        :invalid="!!form.errors.account_type_id"
                    />
                    <Select
                        v-model="selectedAccountType"
                        :options="accountTypes"
                        filter
                        optionLabel="name"
                        optionValue="id"
                        :placeholder="$t('public.select_account_type')"
                        class="w-full"
                        :loading="loadingAccountTypes"
                        :invalid="!!form.errors.account_type_id"
                    >
                        <template #option="slotProps">
                            <div class="flex gap-1 items-center">
                                <div>{{ slotProps.option.name }}</div>
                                <div class="text-gray-500">{{ $t(`public.${slotProps.option.slug}`) }}</div>
                            </div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.account_type_id" />
                </div>

                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="extra_bonus"
                        :value="$t('public.extra_bonus')"
                        :invalid="!!form.errors.extra_bonus"
                    />
                    <InputNumber
                        v-model="form.extra_bonus"
                        inputId="extra_bonus"
                        class="w-full"
                        :min="0"
                        fluid
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        placeholder="$0.00"
                        :invalid="!!form.errors.extra_bonus"
                    />
                    <InputError :message="form.errors.extra_bonus" />
                </div>
            </div>

            <div class="flex gap-3 justify-end self-stretch pt-2 w-full">
                <Button
                    type="button"
                    severity="secondary"
                    text
                    @click="closeDialog"
                    :disabled="form.processing"
                    class="w-full md:w-auto px-4"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    @click="submitForm"
                    :disabled="form.processing || loadingAccountTypes"
                    class="w-full md:w-auto px-4"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </form>
</template>
