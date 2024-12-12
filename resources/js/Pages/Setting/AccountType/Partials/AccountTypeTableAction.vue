<script setup>
import Button from "@/Components/Button.vue";
import Dialog from "primevue/dialog";
import {ref} from "vue";
import {Edit} from "@/Components/Icons/outline.jsx";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import InputLabel from "@/Components/Label.vue";
import InputText from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    accountType: Object
})

const visible = ref(false);
const leaders = ref();
const selectedLeaders = ref();
const loadingLeaders = ref(false);

const openDialog = () => {
    visible.value = true;
    getLeaders();
}

const getLeaders = async () => {
    loadingLeaders.value = true;
    try {
        const response = await axios.get('/setting/getLeadersSel');
        leaders.value = response.data;
        const selectedLeaderUserIds = props.accountType.visible_leaders.map(leader => leader.user_id);
        selectedLeaders.value = leaders.value.filter(leader => selectedLeaderUserIds.includes(leader.id));
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

const form = useForm({
    account_type_id: props.accountType.id,
    maximum_account_number: props.accountType.maximum_account_number,
    leaders: '',
})

const submitForm = () => {
    form.leaders = selectedLeaders.value;
    form.post(route('setting.updateAccountType'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            selectedLeaders.value = null
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        type="button"
        class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
        variant="gray"
        pill
        @click="openDialog"
    >
        <Edit aria-hidden="true" class="w-5 h-5 absolute" />
        <span class="sr-only">Edit</span>
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        header="Edit Account Type"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="mb-4">
                Account Type: {{ accountType.name }}
            </div>
            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-gray-950 dark:text-white w-full text-left">{{ $t('Settings') }}</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="maximum_account_number"
                            value="Max Accounts"
                        />
                        <InputText
                            id="maximum_account_number"
                            type="number"
                            class="block w-full"
                            v-model="form.maximum_account_number"
                            placeholder="Enter max accounts"
                            :invalid="form.errors.maximum_account_number"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.maximum_account_number" />
                    </div>
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="leaders"
                            value="Visible To"
                        />
                        <MultiSelect
                            v-model="selectedLeaders"
                            :options="leaders"
                            optionLabel="name"
                            filter
                            :filter-fields="['name', 'email']"
                            placeholder="Select leaders"
                            :maxSelectedLabels="3"
                            class="w-full"
                            :invalid="!!form.errors.leaders"
                            :loading="loadingLeaders"
                        />
                        <InputError :message="form.errors.leaders" />
                    </div>
                </div>
            </div>

            <div class="pt-5 px-2 flex justify-end gap-5">
                <Button
                    type="button"
                    variant="secondary"
                    class="justify-center w-full md:w-auto"
                    @click="closeDialog"
                    :disabled="form.processing"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    class="justify-center w-full md:w-auto"
                    @click.prevent="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
