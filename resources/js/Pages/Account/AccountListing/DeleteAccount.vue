<script setup>
import {transactionFormat} from "@/Composables/index.js";
import {ref} from "vue";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Textarea from "primevue/textarea";
import {useForm} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";

const props = defineProps({
    meta: Object
});

const {formatAmount} = transactionFormat();
const emit = defineEmits(["update:visible"]);

const account = ref();
const isLoading = ref(false);

const getResults = async () => {
    isLoading.value = true;

    try {
        let response;
        response = await axios.get(`/account/getAccountByMetaLogin?meta_login=${props.meta.meta_login}`);
        account.value = response.data
    } catch (error) {
        console.error('Error updating data:', error);
    } finally {
        isLoading.value = false;
    }
};

getResults();

const form = useForm({
    id: props.meta.id,
    trade_user: props.meta.trading_user.id,
    meta_login: props.meta.meta_login,
    remarks: '',
})

const submitForm = () => {
    form.delete(route('account.deleteAccount'), {
        onSuccess: () => {
            closeDialog()
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
                        #{{ meta.meta_login }} - {{ $t('public.balance') }}
                    </div>
                    <div class="w-full text-gray-950 dark:text-white text-center text-xl font-semibold">
                        {{ isLoading ? $t('public.loading') : '$ ' + formatAmount(account.balance ?? 0) }}
                    </div>
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="remarks"
                        :value="$t('public.remarks')"
                        :invalid="!!form.errors.remarks"
                    />
                    <Textarea
                        id="remarks"
                        class="w-full h-20"
                        v-model="form.remarks"
                        :placeholder="$t('public.enter_remarks')"
                        :invalid="!!form.errors.remarks"
                        rows="5"
                        cols="30"
                    />
                    <InputError :message="form.errors.remarks" />
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
