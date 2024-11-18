<script setup>

import Card from "primevue/card";
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-3 md:gap-5 w-full">
        <div class="grid sm:grid-cols-2 lg:grid-cols-1 gap-3 md:gap-5 lg:min-w-80 xl:min-w-[400px]">
            <Card>
                <template #content>
                </template>
            </Card>

            <Card>
                <template #content>
                </template>
            </Card>
        </div>

        <Card class="w-full">
            <template #content>
            </template>
        </Card>
    </div>
</template>
