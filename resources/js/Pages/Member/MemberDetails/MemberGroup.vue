<script setup>
import Card from "primevue/card";
import Tag from "primevue/tag";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import {IconCircleCheckFilled} from "@tabler/icons-vue";
import Button from "primevue/button";

const props = defineProps({
    member: Object,
    firstLeader: Object,
});

const form = useForm({
    user_id: props.member.id,
    upline_id: null,
    is_public: null,
});

const selectedUpline = ref();
const uplines = ref([]);
const loadingUsers = ref(false);

const getUsers = async () => {
    loadingUsers.value = true;
    try{
        const response = await axios.get('/getUsers');
        uplines.value = response.data;
        selectedUpline.value = uplines.value.find(upline => upline.id === props.member.upline_id) || null;
    } catch(error) {
        console.error('Error fetching uplines:', error);
    } finally {
        loadingUsers.value = false;
    }
}

getUsers();

const groupStatuses = [
    {label: 'public', value: 1},
    {label: 'private', value: 0},
]

const selectedGroupStatus = ref(props.member.is_public);
const selectGroupStatus = (type) => {
    selectedGroupStatus.value = type.value;
}

const submitForm = () => {
    form.upline_id = selectedUpline.value.id;
    form.is_public = selectedGroupStatus.value;

    form.put(route('member.updateMemberGroup'));
}
</script>

<template>
    <Card>
        <template #content>
            <div class="flex flex-col gap-5 items-center self-stretch">
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.group') }}</span>

                    <div class="flex flex-col gap-2 text-sm items-start self-stretch bg-gray-100 dark:bg-gray-800 p-3">
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.leader') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ firstLeader?.email ?? '-' }}
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.referrer') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ member.upline?.email ?? '-' }}
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.status') }}
                            </div>
                            <Tag
                                :severity="member.is_public ? 'success' : 'info'"
                                :value="member.is_public ? $t('public.public') : $t('public.private')"
                            />
                        </div>
                    </div>

                    <form class="w-full flex flex-col gap-3 items-center self-stretch">
                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="upline_id"
                                :invalid="!!form.errors.upline_id"
                            >
                                {{ $t('public.referrer') }}
                            </InputLabel>

                            <Select
                                v-model="selectedUpline"
                                :options="uplines"
                                optionLabel="email"
                                placeholder="Choose a referrer"
                                class="w-full"
                                :invalid="!!form.errors.upline_id"
                                filter
                                :filterFields="['name', 'email', 'username']"
                                :loading="loadingUsers"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center">
                                        <div>{{ slotProps.value.email }}</div>
                                    </div>
                                    <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-1">
                                        <div>{{ slotProps.option.email }}</div>
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.upline_id" />
                        </div>

                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="status"
                                :invalid="!!form.errors.upline_id"
                            >
                                {{ $t('public.status') }}
                            </InputLabel>
                            <div class="flex items-start gap-3 self-stretch w-full overflow-x-auto">
                                <div
                                    v-for="groupStatus in groupStatuses"
                                    @click="selectGroupStatus(groupStatus)"
                                    class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                                    :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedGroupStatus === groupStatus.value,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedGroupStatus !== groupStatus.value,
                                }"
                                >
                                    <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500 uppercase"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedGroupStatus === groupStatus.value,
                                        'text-gray-950 dark:text-white': selectedGroupStatus !== groupStatus.value
                                    }"
                                >
                                    {{ groupStatus.label }}
                                </span>
                                        <IconCircleCheckFilled v-if="selectedGroupStatus === groupStatus.value" size="20" stroke-width="1.25" color="#2970FF" />
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.upline_id" />
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
                    </form>
                </div>
            </div>
        </template>
    </Card>
</template>
