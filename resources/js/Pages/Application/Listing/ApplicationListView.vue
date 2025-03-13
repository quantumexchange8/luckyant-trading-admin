<script setup>
import NoData from "@/Components/NoData.vue";

const props = defineProps({
    applicationsCount: Number,
});
</script>

<template>
    <div class="flex flex-col items-center gap-5">
        <!-- Filter -->


        <!-- No Data -->
        <div
            v-if="applicationsCount === 0"
            class="flex justify-center items-center w-full"
        >
            <NoData />
        </div>

        <!-- Content -->
        <div v-else class="w-full">

        </div>
    </div>
</template>
