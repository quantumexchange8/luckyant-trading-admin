<script setup>
import Card from "primevue/card";
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import {ref} from "vue";
import Select from "primevue/select";
import {useLangObserver} from "@/Composables/localeObserver.js";
import {IconCircleCheckFilled} from "@tabler/icons-vue";
import Button from "primevue/button";

const props = defineProps({
    member: Object,
});

const form = useForm({
    user_id: props.member.id,
    setting_rank_id: null,
    display_rank_id: null,
    rank_up_status: null,
});

const selectedSettingRank = ref();
const selectedDisplayRank = ref();
const ranks = ref([]);
const loadingRanks = ref(false);
const {locale} = useLangObserver();

const getRanks = async () => {
    loadingRanks.value = true;
    try{
        const response = await axios.get('/getRanks');
        ranks.value = response.data;
        selectedSettingRank.value = ranks.value.find(rank => rank.id === props.member.setting_rank_id) || null;
        selectedDisplayRank.value = ranks.value.find(rank => rank.id === props.member.display_rank_id) || null;
    } catch(error){
        console.error('Error fetching ranks:', error);
    } finally {
        loadingRanks.value = false;
    }
}

getRanks();

const rankStatuses = [
    'auto',
    'manual',
]

const selectedRankUp = ref(props.member.rank_up_status);
const selectRankUp = (type) => {
    selectedRankUp.value = type;
}

const submitForm = () => {
    form.setting_rank_id = selectedSettingRank.value.id;
    form.display_rank_id = selectedSettingRank.value.id;
    form.rank_up_status = selectedRankUp.value;

    form.put(route('member.updateMemberRank'));
}
</script>

<template>
    <Card>
        <template #content>
            <div class="flex flex-col gap-5 items-center self-stretch">
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.rank') }}</span>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="setting_rank_id"
                            :invalid="!!form.errors.setting_rank_id"
                        >
                            {{ $t('public.setting_rank') }}
                        </InputLabel>
                        <Select
                            v-model="selectedSettingRank"
                            :options="ranks"
                            optionLabel="name"
                            placeholder="Choose a rank"
                            class="w-full"
                            :invalid="!!form.errors.setting_rank_id"
                            :loading="loadingRanks"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ slotProps.value.name[locale] }}</div>
                                </div>
                                <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center gap-1">
                                    <div>{{ slotProps.option.name[locale] }}</div>
                                </div>
                            </template>
                        </Select>
                        <InputError :message="form.errors.setting_rank_id" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="display_rank_id"
                            :invalid="!!form.errors.display_rank_id"
                        >
                            {{ $t('public.display_rank') }}
                        </InputLabel>
                        <Select
                            v-model="selectedSettingRank"
                            :options="ranks"
                            optionLabel="name"
                            placeholder="Choose a rank"
                            class="w-full"
                            :invalid="!!form.errors.display_rank_id"
                            :loading="loadingRanks"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ slotProps.value.name[locale] }}</div>
                                </div>
                                <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center gap-1">
                                    <div>{{ slotProps.option.name[locale] }}</div>
                                </div>
                            </template>
                        </Select>
                        <InputError :message="form.errors.display_rank_id" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="rank_up_status"
                            :invalid="!!form.errors.rank_up_status"
                        >
                            {{ $t('public.rank_up') }}
                        </InputLabel>
                        <div class="flex items-start gap-3 self-stretch w-full overflow-x-auto">
                            <div
                                v-for="rankUp in rankStatuses"
                                @click="selectRankUp(rankUp)"
                                class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                                :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedRankUp === rankUp,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedRankUp !== rankUp,
                                }"
                            >
                                <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500 uppercase"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedRankUp === rankUp,
                                        'text-gray-950 dark:text-white': selectedRankUp !== rankUp
                                    }"
                                >
                                    {{ rankUp }}
                                </span>
                                    <IconCircleCheckFilled v-if="selectedRankUp === rankUp" size="20" stroke-width="1.25" color="#2970FF" />
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.rank_up_status" />
                    </div>
                </div>

                <div class="flex justify-end w-full pt-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        @click.prevent="submitForm"
                        class="px-10 w-full md:w-auto"
                        size="small"
                    >
                        <span>Save</span>
                    </Button>
                </div>
            </div>
        </template>
    </Card>
</template>
