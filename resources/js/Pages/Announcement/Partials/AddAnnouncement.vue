<script setup>
import {ref, watch} from "vue";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import {useForm} from "@inertiajs/vue3";
import Combobox from "@/Components/Combobox.vue";
import TipTapEditor from "@/Components/TipTapEditor.vue";
import {
    RadioGroup,
    RadioGroupDescription,
    RadioGroupLabel,
    RadioGroupOption
} from '@headlessui/vue'
import {XIcon} from "@/Components/Icons/outline.jsx"

const addAnnouncementModal = ref(false);

const openAddAnnouncementModal = () => {
    addAnnouncementModal.value = true
}

const closeModal = () => {
    addAnnouncementModal.value = false
}

const previewSubject = ref('');
const previewDetails = ref('');
const previewImage = ref([]);
const selectedLogo = ref(null);
const selectedLogoName = ref(null);

const positions = [
    {
        name: 'Login Popup',
        value: 'login'
    },
    {
        name: 'Notification',
        value: 'notification'
    }
]

const selectPosition = ref(positions[0]);

const form = useForm({
    receiver_type: 'everyone',
    receiver: null,
    subject: '',
    details: '',
    image: '',
    positions: '',
})

watch(form, (watchFormSubject) => {
    previewSubject.value = watchFormSubject.subject;
    previewDetails.value = watchFormSubject.details;
});

const selectedReceivers = ref();

function loadUsers(query, setOptions) {
    fetch('/member/getAllUsers?query=' + query)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(user => {
                    return {
                        value: user.id,
                        label: user.name,
                        img: user.profile_photo
                    }
                })
            )
        });
}

const onPlanLogoChanges = (event) => {
    const planLogoInput = event.target;
    const file = planLogoInput.files[0];

    if(file) {
        //Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedLogo.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedLogoName.value = file.name;
        form.image = event.target.files[0];
    } else {
        selectedLogo.value = null;
    }
}

const removePlanLogo = () => {
    selectedLogo.value = null;
}

const submit = () => {
    form.receiver = selectedReceivers.value;
    form.positions = selectPosition.value.value
    form.post(route('addAnnouncement'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            selectedReceivers.value = [];
        },
    })
}

</script>

<template>
    <Button
        type="button"
        variant="primary"
        @click="openAddAnnouncementModal"
    >
        Add New Announcement
    </Button>

    <Modal :show="addAnnouncementModal" title="Add new announcement" max-width="6xl" @close="closeModal">
        <div class="grid grid-rows-2 md:grid-rows-1 md:grid-cols-2 gap-5 w-full">
            <form
                @submit.prevent="submit"
                class="flex flex-col gap-5"
            >
                <div class="flex flex-col gap-4">
                    <div class="text-base text-gray-800 dark:text-white w-52">
                        Type
                    </div>
                    <div class="w-full">
                        <RadioGroup v-model="selectPosition" class="flex gap-1 md:gap-4 flex-col md:flex-row">
                            <div class="flex flex-row w-full gap-4">
                                <RadioGroupOption
                                    as="template"
                                    v-for="plan in positions"
                                    :key="plan.name"
                                    :value="plan"
                                    v-slot="{ active, checked }"
                                    class="w-full"
                                >
                                    <div
                                        :class="[
                                    checked ? 'bg-gray-100 dark:bg-gray-600 border-primary-400 dark:text-white border-2 dark:border-white' : 'border border-gray-300 dark:border-transparent dark:bg-gray-700',
                                ]"
                                        class="relative flex cursor-pointer rounded-lg px-5 py-2.5 focus:outline-none"
                                    >
                                        <div class="flex w-full items-center justify-center">
                                            <div class="flex items-center">
                                                <div class="text-sm">
                                                    <RadioGroupLabel
                                                        as="p"
                                                        :class="checked ? 'text-gray-700 dark:text-white' : 'text-gray-700 dark:text-white'"
                                                        class="font-medium"
                                                    >
                                                        {{ plan.name }}
                                                    </RadioGroupLabel>
                                                    <RadioGroupDescription
                                                        as="span"
                                                        :class="checked ? 'dark:text-white' : 'dark:text-white'"
                                                        class="inline"
                                                    >
                                                    </RadioGroupDescription>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </RadioGroupOption>
                            </div>
                        </RadioGroup>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="subject" value="Subject" />
                    <Input
                        id="subject"
                        type="text"
                        placeholder="Enter subject"
                        class="block w-full"
                        v-model="form.subject"
                        :invalid="form.errors.subject"
                    />
                    <InputError :message="form.errors.subject" class="mt-2" />
                </div>
                <div class="space-y-2">
                    <Label class="text-sm dark:text-white" for="detail" value="Details" />
                    <TipTapEditor
                        v-model="form.details"
                    />
                    <InputError :message="form.errors.details" class="mt-2" />
                </div>
                <div class="space-y-2">
                    <Label class="dark:text-white" for="image" value="Attachment" />
                    <div v-if="selectedLogo == null" class="flex gap-3 w-full">
                        <input
                            ref="planLogoInput"
                            id="image"
                            type="file"
                            class="hidden"
                            accept="image/*"
                            @change="onPlanLogoChanges"
                        />
                        <Button
                            type="button"
                            variant="gray"
                            @click="$refs.planLogoInput.click()"
                            class="justify-center gap-2"
                        >
                            <span>Browse</span>
                        </Button>
                    </div>
                    <div
                        v-if="selectedLogo"
                        class="relative w-full py-2 pl-4 flex justify-between rounded-lg border focus:ring-1 focus:outline-none"
                    >
                        <div class="inline-flex items-center gap-3">
                            <img :src="selectedLogo" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                <div class="text-gray-light-900 dark:text-white">
                                    {{ selectedLogoName }}
                                </div>
                        </div>
                        <Button
                            type="button"
                            variant="transparent"
                            pill
                            @click="removePlanLogo"
                        >
                            <XIcon />
                        </Button>
                    </div>
                </div>
                <div class="flex pt-8 gap-3 justify-end border-t dark:border-gray-700">
                    <Button variant="secondary" class="px-4 py-2 justify-center" @click="closeModal">
                        <span class="text-sm font-semibold">Cancel</span>
                    </Button>
                    <Button variant="primary" class="px-4 py-2 justify-center" :disabled="form.processing">
                        <span class="text-sm font-semibold">Confirm</span>
                    </Button>
                </div>
            </form>
            <div>
                <h3 class="font-semibold dark:text-white text-base pb-3 border-b dark:border-gray-700">Preview</h3>
                <div
                    v-if="previewSubject === '' && previewDetails === '' && form.image === ''"
                    class="flex flex-col items-center justify-center mt-12"
                >
                    <!-- <img src="/assets/no_data.png" class="w-80" alt="no preview"> -->
                    <div class="dark:text-gray-400 mt-4">No preview</div>
                </div>
                <div v-else class="pt-8">
                    <div v-if="form.image !== ''" class="py-5 flex justify-center w-full">
                        <img class="rounded-lg h-56 w-full object-cover" :src="selectedLogo" alt="">
                    </div>
                    <h3 class="font-semibold text-sm dark:text-white">{{ previewSubject }}</h3>
                    <div class="mt-5 dark:text-gray-400 prose leading-3 text-xs" v-html="previewDetails"></div>
                </div>
            </div>
        </div>
    </Modal>
</template>
