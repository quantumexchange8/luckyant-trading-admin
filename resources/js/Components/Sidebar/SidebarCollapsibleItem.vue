<script setup>
import { Link } from '@inertiajs/vue3'
import {sidebarState} from "@/Composables/index.js";

const props = defineProps({
    href: String,
    title: String,
    active: {
        type: Boolean,
        default: false,
    },
    external: {
        type: Boolean,
        default: false,
    },
    pendingCounts: Number
})

const { external } = props

const Tag = external ? 'a' : Link
</script>

<template>
    <li
        :class="[
            'relative leading-8 m-0 pl-6',
            'before:block before:w-4 before:h-0 before:absolute before:left-0 before:top-4 before:border-t-2 before:border-t-gray-200 before:-mt-0.5',
            'last:before:bg-white last:before:h-auto last:before:top-4 last:before:bottom-0',
            'dark:last:before:bg-gray-950 dark:before:border-t-gray-600',
        ]"
    >
        <component
            :is="Tag"
            :href="href"
            v-bind="$attrs"
            :class="[
                'transition-colors hover:text-gray-900 dark:hover:text-gray-100',
                {
                    'text-gray-900 dark:text-gray-200': active,
                    'text-gray-500 dark:text-gray-400': !active,
                },
            ]"
        >
            <div class="flex items-center gap-2">
                <span
                    class="text-base font-medium"
                    v-show="sidebarState.isOpen || sidebarState.isHovered"
                >
                    {{ title }}
                </span>
                <span v-if="pendingCounts > 0" class="text-xs flex items-center rounded-md px-2 h-5 bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-white">
                    {{ pendingCounts }}
                </span>
            </div>
        </component>
    </li>
</template>
