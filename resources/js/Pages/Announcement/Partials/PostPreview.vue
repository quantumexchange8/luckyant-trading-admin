<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import {ref, watch} from "vue";
import Image from "primevue/image";

const props = defineProps({
    form: Object,
    thumbnailFile: [Object, File]
});

const visible = ref(false);
const previewForm = ref(props.form);
const thumbnail = ref(null);

watch([() => props.form, () => props.thumbnailFile], () => {
    previewForm.value = props.form;
    if (props.thumbnailFile) {
        thumbnail.value = URL.createObjectURL(props.thumbnailFile);
    }
});

const openDialog = () => {
    visible.value = true;
};
</script>

<template>
    <Button
        type="button"
        severity="secondary"
        size="small"
        class="w-full"
        :label="$t('public.preview')"
        @click="openDialog"
    />

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.preview')"
        class="dialog-xs md:dialog-lg"
    >
        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <div
                v-if="thumbnail"
                class="w-full"
            >
                <Image
                    :src="thumbnail"
                    alt="Image"
                    imageClass="w-full object-contain rounded"
                />
            </div>
            <div
                v-else-if="thumbnailFile"
                class="w-full"
            >
                <Image
                    :src="thumbnailFile.original_url"
                    alt="Image"
                    imageClass="w-full object-contain rounded"
                />
            </div>

            <!-- Subject -->
            <div class="text-base md:text-xl font-semibold w-full text-gray-950 dark:text-white">
                {{ previewForm.subject }}
            </div>

            <!-- Content -->
            <div
                class="prose dark:prose-invert w-full text-left"
                v-html="previewForm.details"
            ></div>
        </div>
    </Dialog>
</template>
