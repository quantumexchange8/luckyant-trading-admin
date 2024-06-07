<script setup>
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {CheckCircleBrokenIcon} from "@/Components/Icons/outline.jsx";
import Combobox from "@/Components/Combobox.vue";
import {computed, ref, watch, watchEffect} from "vue";

const props = defineProps({
    masterConfigurations: Object,
    settlementPeriodSel: Array,
})

const selectedLeaders = ref([]);

const form = useForm({
    master_id: props.masterConfigurations.id,
    leader_ids: null,
})

const masterLeaders = computed(() => {
    console.log(props.masterConfigurations.master_leaders)
    return props.masterConfigurations.master_leaders.map(leader => ({
        value: leader.leader_id,
    }));
});

const submit = () => {
    form.leader_ids = selectedLeaders.value
    form.post(route('master.addVisibleToLeaders'))
}

function loadUsers(query, setOptions) {
    fetch('/member/getAllLeaders?query=' + query)
        .then(response => response.json())
        .then(results => {
            const options = results.map(user => ({
                value: user.id,
                label: user.name,
                img: user.profile_photo
            }));
            setOptions(options);

            // Ensure selectedLeaders includes any new options that match
            selectedLeaders.value = selectedLeaders.value.map(selected => {
                const matchedOption = options.find(option => option.value === selected.value);
                return matchedOption ? matchedOption : selected;
            });
        });
}

// Update selectedLeaders when masterLeaders changes
watch(masterLeaders, (newVal) => {
    selectedLeaders.value = newVal;
}, { immediate: true });
//
// watchEffect(() => {
//     if (usePage().props.title !== null) {
//         masterLeaders();
//     }
// });
</script>

<template>
    <div
        v-if="masterConfigurations"
        class="flex flex-col items-start gap-5 bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
        <div class="flex items-center gap-3">
            <div class="text-lg font-semibold">
                Visible to Leaders
            </div>
        </div>
        <div class="space-y-2 p-5 rounded-xl bg-gray-100 dark:bg-gray-800 w-full">
            <div class="text-sm font-semibold dark:text-gray-200">
                Leaders
            </div>
            <div
                class="flex items-center gap-4"
            >
                <div
                    v-for="leader in selectedLeaders"
                    class="flex gap-1 items-center text-sm text-gray-600 dark:text-gray-400"
                >
                    <CheckCircleBrokenIcon class="w-5 h-5 text-success-500" />
                    {{ leader.label }}
                </div>
            </div>
        </div>
        <form class="w-full">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-4 col-span-3">
                    <Combobox
                        :load-options="loadUsers"
                        v-model="selectedLeaders"
                        placeholder="Leader"
                        multiple
                        image
                        :error="form.errors.leader_ids"
                        class="w-full"
                    />
                    <InputError :message="form.errors.leader_ids" />
                </div>
            </div>

            <div class="pt-5 flex justify-end">
                <Button
                    class="flex justify-center"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{ $t('public.save') }}
                </Button>
            </div>
        </form>
    </div>
</template>
