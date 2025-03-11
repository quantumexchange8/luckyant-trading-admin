<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "primevue/button";
import {IconCirclePlus} from "@tabler/icons-vue";
import AnnouncementListing from "@/Pages/Announcement/AnnouncementListing.vue";

defineProps({
    announcementsCount: Number,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.announcement')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-xl font-semibold leading-tight">
                <h2 class="text-2xl font-semibold leading-tight">
                    {{ $t('public.announcement') }}
                </h2>

                <Button
                    as="a"
                    :href="route('announcement.create')"
                    type="button"
                    size="small"
                    class="flex gap-2"
                >
                    <IconCirclePlus size="20" stroke-width="1.5" />
                    {{ $t('public.new_announcement') }}
                </Button>
            </div>
        </template>

        <AnnouncementListing
            :announcementsCount="announcementsCount"
        />
    </AuthenticatedLayout>

</template>
