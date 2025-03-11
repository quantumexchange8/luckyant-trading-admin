<script setup>
import ConfirmDialog from 'primevue/confirmdialog';
import Button from 'primevue/button';
import {
    IconCircleCheck,
    IconExclamationCircle,
    IconInfoCircle
} from "@tabler/icons-vue"
</script>

<template>
    <ConfirmDialog group="headless-primary">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="py-6 px-4 md:px-6 flex flex-col items-center gap-5 self-stretch bg-white dark:bg-gray-900 rounded-xl w-[320px] md:w-[500px]">
                <div class="flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900 w-[72px] h-[72px]">
                    <div class="flex items-center justify-center rounded-full bg-primary-200 dark:bg-primary-700 w-14 h-14 text-primary-600 dark:text-primary-300">
                        <IconInfoCircle size="36" stroke-width="1.5" />
                    </div>
                </div>
                <div class="flex flex-col gap-1 items-center self-stretch text-center">
                    <span class="text-gray-950 dark:text-white text-sm md:text-base font-semibold">{{ message.header }}</span>
                    <span class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">{{ message.message }}</span>
                    <div class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">
                        {{ message.text }}
                        <span class="text-gray-950 dark:text-white font-semibold">{{ message.dynamicText }}</span>
                        {{ message.suffix }}
                    </div>
                </div>
                <div class="flex items-center gap-4 md:gap-5 self-stretch">
                    <Button
                        type="button"
                        @click="rejectCallback"
                        class="w-full"
                        severity="secondary"
                    >
                        {{ message.cancelButton }}
                    </Button>
                    <Button
                        type="button"
                        @click="acceptCallback"
                        class="w-full text-nowrap"
                    >
                        {{ message.acceptButton }}
                    </Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>

    <ConfirmDialog group="headless-success">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="py-6 px-4 md:px-6 flex flex-col items-center gap-5 self-stretch bg-white dark:bg-gray-900 rounded-xl w-[320px] md:w-[500px]">
                <div class="flex items-center justify-center rounded-full bg-success-100 dark:bg-success-900/40 w-[72px] h-[72px]">
                    <div class="flex items-center justify-center rounded-full bg-success-200 dark:bg-success-700 w-14 h-14 text-success-600 dark:text-success-300">
                        <IconCircleCheck size="36" stroke-width="1.5" />
                    </div>
                </div>
                <div class="flex flex-col gap-1 items-center self-stretch text-center">
                    <span class="text-gray-950 dark:text-white text-sm md:text-base font-semibold">{{ message.header }}</span>
                    <span class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">{{ message.message }}</span>
                    <div class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">
                        {{ message.text }}
                        <span class="text-gray-950 dark:text-white font-semibold">{{ message.dynamicText }}</span>
                        {{ message.suffix }}
                    </div>
                </div>
                <div class="flex items-center gap-4 md:gap-5 self-stretch">
                    <Button
                        type="button"
                        @click="rejectCallback"
                        class="w-full"
                        severity="secondary"
                    >
                        {{ message.cancelButton }}
                    </Button>
                    <Button
                        type="button"
                        severity="success"
                        @click="acceptCallback"
                        class="w-full text-nowrap"
                    >
                        {{ message.acceptButton }}
                    </Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>

    <ConfirmDialog group="headless-error">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="py-6 px-4 md:px-6 flex flex-col items-center gap-5 self-stretch bg-white dark:bg-gray-800/60 rounded-xl w-[320px] md:w-[500px]">
                <div class="flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40 w-[72px] h-[72px]">
                    <div class="flex items-center justify-center rounded-full bg-red-200 dark:bg-red-700 w-14 h-14 text-red-600 dark:text-red-200">
                        <IconExclamationCircle size="36" stroke-width="1.5" />
                    </div>
                </div>
                <div class="flex flex-col gap-1 items-center self-stretch text-center">
                    <span class="text-gray-950 dark:text-white text-sm md:text-base font-semibold">{{ message.header }}</span>
                    <span class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">{{ message.message }}</span>
                    <div class="text-gray-700 dark:text-gray-400 text-xs md:text-sm">
                        {{ message.text }}
                        <span class="text-gray-950 dark:text-white font-semibold">{{ message.dynamicText }}</span>
                        {{ message.suffix }}
                    </div>
                </div>
                <div class="flex items-center gap-4 md:gap-5 self-stretch">
                    <Button
                        type="button"
                        @click="rejectCallback"
                        class="w-full"
                        severity="secondary"
                    >
                        {{ message.cancelButton }}
                    </Button>
                    <Button
                        type="button"
                        @click="acceptCallback"
                        severity="danger"
                        class="w-full text-nowrap"
                    >
                        {{ message.acceptButton }}
                    </Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
