<template>
    <div
        class="relative text-gray-500 focus-within:text-primary-500 dark:focus-within:text-primary-400"
    >
        <div
            aria-hidden="true"
            class="absolute inset-y-0 flex items-center px-4 pointer-events-none "
        >
            <slot name="icon" />
        </div>

        <slot />
    </div>
</template>
