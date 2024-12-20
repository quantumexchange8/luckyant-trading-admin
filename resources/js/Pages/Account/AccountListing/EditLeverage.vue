<script setup>
import InputLabel from "@/Components/Label.vue";
import {useForm} from "@inertiajs/vue3";
import Select from "primevue/select";
import {ref} from "vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    account: Object,
});

const form = useForm({
    meta_login: props.account.meta_login,
    margin_leverage: props.account.margin_leverage,
});

const emit = defineEmits(['update:visible']);
const loadingLeverages = ref(false);
const leverages = ref();

const getLeverages = async () => {
    loadingLeverages.value = true;
    try {
        const response = await axios.get('/getLeveragesByAccountType?account_type_id=' + props.account.account_type.id);
        leverages.value = response.data;
    } catch (error) {
        console.error('Error fetching leverages:', error);
    } finally {
        loadingLeverages.value = false;
    }
};

getLeverages();

const submitForm = () => {
    form.post(route('account.edit_leverage'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
    <form>
        <div class="flex flex-col items-center gap-8 self-stretch md:gap-10">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div
                    class="flex flex-col justify-center items-center py-4 px-8 gap-2 self-stretch bg-gray-200 dark:bg-gray-950">
                    <div class="w-full text-gray-500 text-center text-sm font-medium">
                        #{{ account.meta_login }} - {{ $t('public.leverage') }}
                    </div>
                    <div class="w-full text-gray-950 dark:text-white text-center text-xl font-semibold">
                        1:{{ account.margin_leverage }}
                    </div>
                </div>

                <!-- Leverage -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="margin_leverage"
                        :value="$t('public.leverage')"
                    />
                    <Select
                        input-id="margin_leverage"
                        v-model="form.margin_leverage"
                        :options="leverages"
                        :placeholder="$t('public.select_leverage')"
                        class="w-full"
                        :loading="loadingLeverages"
                        :invalid="!!form.errors.margin_leverage"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value.leverage">
                                <div>{{ slotProps.value.leverage.display }}</div>
                            </div>
                            <div v-else-if="slotProps.value">
                                <div>{{ slotProps.value }}</div>
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            {{ slotProps.option.leverage.display }}
                        </template>
                    </Select>
                    <InputError :message="form.errors.margin_leverage" />
                </div>
            </div>
            <div class="flex gap-3 w-full justify-end">
                <Button
                    type="button"
                    variant="transparent"
                    class="w-full md:w-auto justify-center"
                    @click="closeDialog"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary"
                    :disabled="form.processing"
                    class="w-full md:w-auto justify-center"
                    @click.prevent="submitForm"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </form>
</template>
