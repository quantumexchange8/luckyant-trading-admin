<script setup>
import { Link } from '@inertiajs/vue3'
import { sidebarState } from '@/Composables'
import { EmptyCircleIcon, InfoCircleIcon } from '@/Components/Icons/outline'
import Tag from "primevue/tag";

const props = defineProps({
    href: {
        type: String,
        required: false,
    },
    active: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    external: {
        type: Boolean,
        default: false,
    },
    pendingCounts: Number,
})

const ExternalTag = !props.external ? Link : 'a'
</script>

<template>
    <component
        :is="ExternalTag"
        v-if="href"
        :href="href"
        :class="[
            'p-2 flex items-center gap-2 rounded-md transition-colors',
            {
                'text-gray-400 dark:text-white hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-dark-eval-2':
                    !active,
                'text-white bg-primary-500 shadow-lg hover:bg-primary-600':
                    active,
            },
        ]"
    >
        <slot name="icon">
            <EmptyCircleIcon aria-hidden="true" class="flex-shrink-0 w-6 h-6" />
        </slot>

        <span
            class="text-base font-medium"
            v-show="sidebarState.isOpen || sidebarState.isHovered"
        >
            {{ title }}
        </span>
    </component>
    <button
        v-else
        type="button"
        :class="[
            'p-2 w-full flex items-center gap-2 rounded-md transition-colors',
            {
                'text-gray-400 dark:text-white hover:text-gray-500 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-dark-eval-2':
                    !active,
                'text-white bg-primary-500 shadow-lg hover:bg-primary-600':
                    active,
            },
        ]"
    >
        <slot name="icon">
            <EmptyCircleIcon aria-hidden="true" class="flex-shrink-0 w-6 h-6" />
        </slot>

        <div class="flex items-center gap-2">
            <span
                class="text-base font-medium"
                v-show="sidebarState.isOpen || sidebarState.isHovered"
            >
                {{ title }}
            </span>
            <Tag severity="info" v-if="pendingCounts > 0 && (sidebarState.isOpen || sidebarState.isHovered)" value="Pending"/>
        </div>
        <slot name="arrow" />
    </button>
</template>
