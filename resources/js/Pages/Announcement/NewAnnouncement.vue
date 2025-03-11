<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {useForm} from "@inertiajs/vue3";
import Card from "primevue/card";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputError from "@/Components/InputError.vue";
import TipTapEditor from "@/Components/TipTapEditor.vue";
import {
    IconPhotoPlus,
    IconAlertSquareRounded,
    IconFileCheck
} from "@tabler/icons-vue";
import {ref} from "vue";
import Tag from "primevue/tag";
import toast from "@/Composables/toast.js";
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";
import PostPreview from "@/Pages/Announcement/Partials/PostPreview.vue";

const form = useForm({
    receiver: null,
    subject: '',
    details: '',
    url: '',
    thumbnail: '',
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

getLeaders();

const thumbnailFile = ref(null);
const isDragging = ref(false);

const dragOver = () => {
    isDragging.value = true;
};

const dragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (event) => {
    isDragging.value = false;
    form.errors.thumbnail = null;
    const droppedFiles = event.dataTransfer.files;
    if (droppedFiles.length > 0) {
        validateFile(droppedFiles[0]);
    }
};

const handleThumbnailFileSelect = (event) => {
    form.errors.thumbnail = null;
    const selectedFile = event.target.files[0];
    validateFile(selectedFile);
};

const validateFile = (fileInput) => {
    if (fileInput.type.startsWith("image/")) {
        thumbnailFile.value = fileInput;
        form.thumbnail = thumbnailFile.value;
    } else {
        toast.add({
            title: 'Error',
            message: 'The file type is not accepted',
            type: 'error',
        });
    }
};

const submitForm = () => {
    form.receiver = selectedLeaders.value;
    form.post(route('addAnnouncement'))
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.new_announcement')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <h2 class="text-2xl font-semibold leading-tight">
                    {{ $t('public.new_announcement') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-5 lg:items-start self-stretch">
            <div class="flex flex-col gap-5 items-center self-stretch w-full lg:w-2/3">
                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col gap-5 items-center self-stretch w-full">
                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <InputLabel
                                    for="subject"
                                    :invalid="!!form.errors.subject"
                                >
                                    {{ $t('public.subject') }}
                                </InputLabel>
                                <InputText
                                    id="subject"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.subject"
                                    :placeholder="$t('public.enter_subject')"
                                    :invalid="!!form.errors.subject"
                                />
                                <InputError :message="form.errors.subject" />
                            </div>
                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <InputLabel
                                    for="details"
                                    :invalid="!!form.errors.details"
                                >
                                    {{ $t('public.content') }}
                                </InputLabel>
                                <TipTapEditor
                                    v-model="form.details"
                                />
                                <InputError :message="form.errors.details" />
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col gap-3 items-center self-stretch">
                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.redirect') }}</span>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $t('public.redirect_post_caption') }}
                                </div>
                            </div>

                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <InputText
                                    id="url"
                                    type="text"
                                    class="block w-full"
                                    v-model="form.url"
                                    :placeholder="$t('public.optional')"
                                    :invalid="!!form.errors.url"
                                />
                                <InputError :message="form.errors.url" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            <div class="flex flex-col gap-5 items-center self-stretch w-full lg:w-1/3">
                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col gap-3 items-center self-stretch">
                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.thumbnail') }}</span>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $t('public.thumbnail_caption') }}
                                </div>
                            </div>

                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <div
                                    :class="[
                                        'flex flex-col gap-3 items-center self-stretch px-5 py-8 rounded-md border-2 border-dashed transition-colors duration-150',
                                        {
                                            'border-blue-500 dark:text-blue-400 bg-blue-200/50 dark:bg-blue-800/10': isDragging,
                                            'bg-gray-50 dark:bg-gray-950 border-gray-300 dark:border-gray-600': !isDragging,
                                        }
                                    ]"
                                    @dragover.prevent="dragOver"
                                    @dragleave.prevent="dragLeave"
                                    @drop.prevent="handleDrop"
                                >
                                    <div
                                        v-if="form.errors.thumbnail"
                                        class="rounded-lg w-12 h-12 shrink-0 grow-0 border border-red-300 dark:border-red-600 flex items-center justify-center text-red-500 dark:text-red-400"
                                    >
                                        <IconAlertSquareRounded size="28" stroke-width="1.5" />
                                    </div>
                                    <div
                                        v-else-if="thumbnailFile"
                                        class="rounded-lg w-12 h-12 shrink-0 grow-0 border border-green-300 dark:border-green-600 flex items-center justify-center text-green-500 dark:text-green-400"
                                    >
                                        <IconFileCheck size="28" stroke-width="1.5" />
                                    </div>
                                    <div
                                        v-else
                                        :class="[
                                            'rounded-lg w-12 h-12 shrink-0 grow-0 border border-gray-300 dark:border-gray-600 flex items-center justify-center',
                                            {
                                                'text-blue-500 dark:text-blue-400': isDragging,
                                                'text-gray-600 dark:text-gray-400': !isDragging,
                                            }
                                        ]"
                                    >
                                        <IconPhotoPlus size="28" stroke-width="1.5" />
                                    </div>
                                    <div
                                        v-if="thumbnailFile"
                                        class="flex flex-col items-center justify-center self-stretch"
                                    >
                                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ thumbnailFile.name }}</span>
                                        <label
                                            for="fileInputFront"
                                            class="text-xs text-blue-500 cursor-pointer underline select-none hover:text-blue-600"
                                        >
                                            {{ $t('public.replace_file') }}
                                        </label>
                                    </div>
                                    <div v-else class="flex flex-col items-center text-gray-500 dark:text-gray-400 text-xs text-center">
                                        {{ $t('public.drag_and_drop') }} <label for="fileInputFront" class="text-blue-500 cursor-pointer underline select-none hover:text-blue-600">{{ $t('public.choose_file') }}</label>
                                    </div>
                                    <input type="file" id="fileInputFront" class="hidden" @change="handleThumbnailFileSelect" accept="image/*" />
                                    <InputError :message="form.errors.thumbnail" class="text-center" />
                                    <div class="flex items-center gap-2 self-stretch justify-center w-full">
                                        <Tag severity="secondary">
                                            <span class="dark:text-gray-500">PNG</span>
                                        </Tag>
                                        <Tag severity="secondary">
                                            <span class="dark:text-gray-500">JPG</span>
                                        </Tag>
                                        <Tag severity="secondary">
                                            <span class="dark:text-gray-500">JPEG</span>
                                        </Tag>
                                    </div>
                                </div>
                                <div class="text-xs text-right w-full text-gray-500 dark:text-gray-400">
                                    {{ $t('public.max_size') }}: 8MB
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col gap-3 items-center self-stretch">
                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.recipient') }}</span>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $t('public.recipient_caption') }}
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <MultiSelect
                                    v-model="selectedLeaders"
                                    :options="leaders"
                                    optionLabel="name"
                                    filter
                                    :filter-fields="['name', 'email']"
                                    placeholder="Select leaders"
                                    :maxSelectedLabels="3"
                                    class="w-full"
                                    :invalid="!!form.errors.receiver"
                                />
                                <InputError :message="form.errors.receiver" />
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col gap-3 items-center self-stretch">
                            <div class="flex flex-col gap-1 items-start self-stretch">
                                <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.final_check') }}</span>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $t('public.final_check_caption') }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3 self-stretch">
                                <PostPreview
                                    :form="form"
                                    :thumbnailFile="thumbnailFile"
                                />
                                <Button
                                    type="submit"
                                    size="small"
                                    class="w-full"
                                    :label="$t('public.publish')"
                                    @click="submitForm"
                                    :disabled="form.processing"
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
