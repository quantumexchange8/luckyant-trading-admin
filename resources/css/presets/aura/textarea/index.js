export default {
    root: ({ context, props, parent }) => ({
        class: [
            // Font
            'leading-none text-sm',

            // Spacing
            'm-0',
            'py-3 px-4',

            // Shape
            'rounded-md',

            // Colors
            'text-gray-800 dark:text-white/80',
            'placeholder:text-gray-400 dark:placeholder:text-gray-500',
            { 'bg-white dark:bg-gray-950': !context.disabled },
            'border',
            { 'border-gray-300 dark:border-gray-600': !props.invalid },

            // Invalid State
            { 'border-red-500 dark:border-red-400': props.invalid },

            // States
            {
                'hover:border-gray-400 dark:hover:border-gray-600': !context.disabled && !props.invalid,
                'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-400 focus:z-10': !context.disabled,
                'bg-gray-200 dark:bg-gray-700 select-none pointer-events-none cursor-default': context.disabled
            },

            // Filled State *for FloatLabel
            { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null && props.modelValue?.length !== 0 },

            // Misc
            'appearance-none',
            'shadow-input',
            'transition-colors duration-200'
        ]
    })
};
