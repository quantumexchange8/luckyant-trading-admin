<script setup>
import Button from "primevue/button";
import {IconCirclePlus} from "@tabler/icons-vue";
import {ref} from "vue";
import Dialog from "primevue/dialog";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputText from "primevue/inputtext";
import InputLabel from "@/Components/Label.vue";
import TipTapEditor from "@/Components/TipTapEditor.vue";
import MultiSelect from "primevue/multiselect";

const visible = ref(false);

const openDialog = () => {
    visible.value = true;
    getLeaders();
}

const form = useForm({
    title: '',
    content: '',
    recipient: null,
});

const leaders = ref();
const loadingLeaders = ref(false);
const selectedLeaders = ref([]);

const getLeaders = async () => {
    loadingLeaders.value = true;
    try {
        const response = await axios.get('/getLeaders');
        leaders.value = response.data;
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

const submitForm = () => {
    form.recipient = selectedLeaders.value;
    form.post(route('application.addApplication'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            selectedLeaders.value = [];
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
        size="small"
        class="flex gap-2"
        @click="openDialog"
    >
        <IconCirclePlus size="20" stroke-width="1.5" />
        {{ $t('public.new_application') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.new_application')"
        class="dialog-xs md:dialog-lg"
    >
        <form class="flex flex-col gap-8 items-center self-stretch w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch w-full">
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="title"
                        :invalid="!!form.errors.title"
                    >
                        {{ $t('public.title') }}
                    </InputLabel>
                    <InputText
                        id="title"
                        type="text"
                        class="block w-full"
                        v-model="form.title"
                        :placeholder="$t('public.enter_title')"
                        :invalid="!!form.errors.title"
                    />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="recipient"
                        :invalid="!!form.errors.recipient"
                    >
                        {{ $t('public.recipient') }}
                    </InputLabel>
                    <MultiSelect
                        v-model="selectedLeaders"
                        :options="leaders"
                        optionLabel="name"
                        filter
                        :filter-fields="['name', 'email']"
                        placeholder="Select leaders"
                        :maxSelectedLabels="3"
                        class="w-full"
                        :invalid="!!form.errors.recipient"
                    />
                    <InputError :message="form.errors.recipient" />
                </div>
                <div class="flex flex-col md:col-span-2 gap-1 items-start self-stretch">
                    <InputLabel
                        for="content"
                        :invalid="!!form.errors.content"
                    >
                        {{ $t('public.content') }}
                    </InputLabel>
                    <TipTapEditor
                        v-model="form.content"
                    />
                    <InputError :message="form.errors.content" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 self-stretch">
                <Button
                    type="button"
                    severity="secondary"
                    class="w-full md:w-auto"
                    :label="$t('public.cancel')"
                    @click="closeDialog"
                    :disabled="form.processing"
                />
                <Button
                    type="submit"
                    class="w-full md:w-auto"
                    :label="$t('public.confirm')"
                    @click="submitForm"
                    :disabled="form.processing"
                />
            </div>
        </form>
    </Dialog>
</template>
