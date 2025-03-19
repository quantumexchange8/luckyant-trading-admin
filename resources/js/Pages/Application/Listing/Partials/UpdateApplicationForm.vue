<script setup>
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";
import TipTapEditor from "@/Components/TipTapEditor.vue";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    application: Object
});

const form = useForm({
    form_id: props.application.id,
    title: props.application.title,
    content: props.application.content,
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

        const selectedLeaderUserIds = props.application.leaders.map(leader => leader.id);
        selectedLeaders.value = leaders.value.filter(leader => selectedLeaderUserIds.includes(leader.id));
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

getLeaders();

const emit = defineEmits(['update:visible']);

const submitForm = () => {
    form.recipient = selectedLeaders.value;
    form.post(route('application.updateApplication'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            selectedLeaders.value = [];
        }
    })
}

const closeDialog = () => {
    emit('update:visible', false);
}
</script>

<template>
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
</template>
