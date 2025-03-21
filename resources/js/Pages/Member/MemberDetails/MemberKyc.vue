<script setup>
import Card from "primevue/card";
import Tag from "primevue/tag";
import InputLabel from "@/Components/Label.vue"
import Image from "primevue/image";

const props = defineProps({
    member: Object,
    frontIdentity: String,
    backIdentity: String,
});

const getSeverity = (status) => {
    switch (status) {
        case 'Unverified':
            return 'danger';

        case 'Rejected':
            return 'danger';

        case 'Verified':
            return 'success';

        case 'Pending':
            return 'info';

    }
}
</script>

<template>
    <Card>
        <template #content>
            <div class="flex flex-col gap-5 items-center self-stretch">
                <div class="flex items-center justify-between gap-5 self-stretch w-full">
                    <div class="flex flex-col items-start self-stretch">
                        <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ $t('public.ic_passport') }}</span>
                        <div class="dark:text-gray-400">
                            {{ member.identification_number ?? '-' }}
                        </div>
                    </div>
                    <Tag
                        :value="member.kyc_approval"
                        :severity="getSeverity(member.kyc_approval)"
                    />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel for="front_identity">Proof of Front Identity</InputLabel>
                        <div
                            class="flex flex-col gap-3 items-center self-stretch px-5 py-4 rounded-md border-2 border-dashed transition-colors duration-150 bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600"
                        >
                            <Image
                                v-if="frontIdentity"
                                role="presentation"
                                alt="frontIdentity"
                                :src="frontIdentity"
                                preview
                                imageClass="w-full object-contain h-32"
                            />
                            <div
                                v-else
                                class="h-32 text-sm font-semibold"
                            >
                                No Front Identification File Submitted
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel for="back_identity">Proof of Back Identity</InputLabel>
                        <div
                            class="flex flex-col gap-3 items-center self-stretch px-5 py-4 rounded-md border-2 border-dashed transition-colors duration-150 bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600"
                        >
                            <Image
                                v-if="backIdentity"
                                role="presentation"
                                alt="backIdentity"
                                :src="backIdentity"
                                preview
                                imageClass="w-full object-contain h-32"
                            />
                            <div
                                v-else
                                class="h-32 text-sm font-semibold"
                            >
                                No Back Identification File Submitted
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
