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
const leverages = ref();
const selectedLeverages = ref();
const loadingLeverages = ref(false);

const openDialog = () => {
    visible.value = true;
    getLeaders();
    getLeverages();
}

const getLeverages = async () => {
    loadingLeverages.value = true;
    try {
        const response = await axios.get('/getLeverages');
        leverages.value = response.data;
        const selectedLeverageIds = props.accountType.leverages.map(leverage => leverage.setting_leverage_id);
        selectedLeverages.value = leverages.value.filter(leverage => selectedLeverageIds.includes(leverage.id));
    } catch (error) {
        console.error('Error fetching leverages:', error);
    } finally {
        loadingLeverages.value = false;
    }
};

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
    leverages: '',
    leaders: '',
})

const submitForm = () => {
    form.leverages = selectedLeverages.value;
    form.leaders = selectedLeaders.value;
    form.post(route('setting.updateAccountType'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            selectedLeverages.value = null
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
                            for="leverages"
                            :value="$t('public.leverage')"
                        />
                        <MultiSelect
                            v-model="selectedLeverages"
                            :options="leverages"
                            optionLabel="display"
                            filter
                            :filter-fields="['display', 'value']"
                            placeholder="Select leverages"
                            :maxSelectedLabels="3"
                            class="w-full"
                            :invalid="!!form.errors.leverages"
                            :loading="loadingLeverages"
                        />
                        <InputError :message="form.errors.leverages" />
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
