<script setup>
import {reactive, ref} from "vue";
import Checkbox from "primevue/checkbox"
import Button from "primevue/button"
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import {
    IconX
} from "@tabler/icons-vue"

const props = defineProps({
    master: Object,
});

const locales = ref([
    'en',
    'cn'
]);

const form = useForm({
    master_id: props.master.id,
    tnc_pdf: {},
    tree_pdf: {},
});

const emit = defineEmits(["update:visible"]);
const fileInputs = reactive({});
const selectedSlip = reactive({}); // Store selected files dynamically

// Trigger file input for a specific key
const triggerFileInput = (inputKey) => {
    const input = fileInputs[inputKey];
    if (input) {
        input.click();
    } else {
        console.error(`File input with key ${inputKey} not found.`);
    }
};

// Handle file change
const handleFileChange = (locale, type, event) => {
    const file = event.target.files[0];
    if (file) {
        const key = `${locale}_${type}`;
        selectedSlip[key] = file; // Store file metadata
        form[type][locale] = file; // Update form
    }
};

// Remove selected file
const removeSlip = (locale, type) => {
    const key = `${locale}_${type}`;
    delete selectedSlip[key]; // Remove file from selectedSlip
    delete form[type][locale]; // Remove file from form
};

const filterPammMedia = (media, locale) => {
    const targetCollection = `${locale}_tnc_pdf`;
    return media.filter((item) => item.collection_name === targetCollection);
};

const filterTreeMedia = (media, locale) => {
    const targetCollection = `${locale}_tree_pdf`;
    return media.filter((item) => item.collection_name === targetCollection);
};

const submitForm = () => {
    form.post(route('master.updateTncFile'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    })
}

const closeDialog = () => {
    emit("update:visible", false);
}
</script>

<template>
    <form class="flex flex-col gap-6 items-start self-stretch">
        <!-- Dynamically generated input fields for each selected locale -->
        <div class="flex flex-col gap-3 items-center self-stretch">
            <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.upload_pdf') }}</span>
            <div class="grid md:grid-cols-2 gap-3 w-full">
                <!-- Upload Slip -->
                <div
                    v-for="locale in locales"
                    :key="'file-upload-' + locale"
                    class="flex flex-col items-start gap-3 self-stretch"
                >
                    <!-- TNC Files -->
                    <div class="flex flex-col gap-1 items-start">
                        <InputLabel
                            :for="`${locale}_tnc_pdf`"
                            :value="`PAMM TNC (${$t(`public.${locale}`)})`"
                        />
                        <input
                            :ref="el => (fileInputs[`${locale}_tnc_pdf`] = el)"
                            :id="`${locale}_tnc_pdf`"
                            type="file"
                            class="hidden"
                            accept="application/pdf"
                            @change="(event) => handleFileChange(locale, 'tnc_pdf', event)"
                        />
                        <Button
                            type="button"
                            :label="$t('public.browse')"
                            severity="contrast"
                            size="small"
                            @click="triggerFileInput(`${locale}_tnc_pdf`)"
                        >
                            {{ $t('public.browse') }}
                        </Button>
                        <InputError :message="form.errors[`tnc_pdf.${locale}`]" />

                        <!-- Display selected file for TNC -->
                        <div
                            v-if="selectedSlip && selectedSlip[`${locale}_tnc_pdf`]"
                            class="relative w-full py-1 pl-4 flex items-center justify-between rounded-lg bg-primary-50 dark:bg-surface-900"
                        >
                            <div class="text-sm text-surface-900 dark:text-white">
                                {{ selectedSlip[`${locale}_tnc_pdf`].name }}
                            </div>
                            <Button
                                type="button"
                                severity="danger"
                                text
                                rounded
                                aria-label="Remove"
                                size="small"
                                @click="() => removeSlip(locale, 'tnc_pdf')"
                            >
                                <template #icon>
                                    <IconX size="16" />
                                </template>
                            </Button>
                        </div>

                        <div
                            v-for="mediaItem in filterPammMedia(master.media, locale)"
                            :key="mediaItem.id"
                            class="flex gap-1 items-center text-xs text-gray-500"
                        >
                            {{ $t('public.current_tnc') }} -
                            <a :href="mediaItem.original_url" target="_blank" class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-primary-700 dark:text-primary-600 dark:hover:text-primary-400">{{ $t('public.view') }}</a>
                        </div>
                    </div>

                    <!-- Tree Files -->
                    <div class="flex flex-col gap-1 items-start">
                        <InputLabel
                            :for="`${locale}_tree_pdf`"
                            :value="`Tree TNC (${$t(`public.${locale}`)})`"
                        />
                        <input
                            :ref="el => (fileInputs[`${locale}_tree_pdf`] = el)"
                            :id="`${locale}_tree_pdf`"
                            type="file"
                            class="hidden"
                            accept="application/pdf"
                            @change="(event) => handleFileChange(locale, 'tree_pdf', event)"
                        />
                        <Button
                            type="button"
                            :label="$t('public.browse')"
                            severity="contrast"
                            size="small"
                            @click="triggerFileInput(`${locale}_tree_pdf`)"
                        >
                            {{ $t('public.browse') }}
                        </Button>

                        <!-- Display selected file for Tree -->
                        <div
                            v-if="selectedSlip && selectedSlip[`${locale}_tree_pdf`]"
                            class="relative w-full py-1 pl-4 flex items-center justify-between rounded-lg bg-primary-50 dark:bg-surface-900"
                        >
                            <div class="text-sm text-surface-900 dark:text-white">
                                {{ selectedSlip[`${locale}_tree_pdf`].name }}
                            </div>
                            <Button
                                type="button"
                                severity="danger"
                                text
                                rounded
                                aria-label="Remove"
                                size="small"
                                @click="() => removeSlip(locale, 'tree_pdf')"
                            >
                                <template #icon>
                                    <IconX size="16" />
                                </template>
                            </Button>
                        </div>

                        <div
                            v-for="mediaItem in filterTreeMedia(master.media, locale)"
                            :key="mediaItem.id"
                            class="flex gap-1 items-center text-xs text-gray-500"
                        >
                            {{ $t('public.current_tnc') }} -
                            <a :href="mediaItem.original_url" target="_blank" class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-primary-700 dark:text-primary-600 dark:hover:text-primary-400">{{ $t('public.view') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex gap-5 justify-end items-center self-stretch w-full">
            <Button
                type="button"
                class="px-4 w-full md:w-auto"
                :label="$t('public.cancel')"
                severity="secondary"
                variant="outlined"
                @click="closeDialog"
            />

            <Button
                type="submit"
                class="px-4 w-full md:w-auto"
                :label="$t('public.confirm')"
                @click.prevent="submitForm"
            />
        </div>
    </form>
</template>
