export default {
    root: ({ props, context, parent, instance }) => ({
        class: [
            'relative',

            // Alignments
            'items-center inline-flex text-center align-bottom justify-center',
            { 'flex-col': (props.iconPos === 'top' || props.iconPos === 'bottom') && props.label },

            // Sizes & Spacing
            'leading-2',
            {
                'p-2.5': props.size === null,
                'text-sm py-1.5 px-3': props.size === 'small',
                'text-xl py-3 px-4': props.size === 'large'
            },
            { 'gap-2': props.label !== null },
            {
                'w-10 px-0 py-2': props.label == null && props.icon !== null
            },
            {
                'w-10 px-0 gap-0': instance.hasIcon && !props.label && !props.badge,
                'rounded-[50%] h-10 [&>[data-pc-section=label]]:w-0 [&>[data-pc-section=label]]:invisible': instance.hasIcon && !props.label && !props.badge && props.rounded
            },

            // Shapes
            { 'shadow-lg': props.raised },
            { 'rounded-md': !props.rounded, 'rounded-full grow-0 shrink-0': props.rounded },
            { 'rounded-none first:rounded-l-md last:rounded-r-md': parent.instance.$name == 'InputGroup' },

            // Link Button
            { 'text-primary-600 bg-transparent border-transparent': props.link },

            // Plain Button
            { 'text-white bg-gray-500 border border-gray-500': props.plain && !props.outlined && !props.text },
            // Plain Text Button
            { 'text-gray-500': props.plain && props.text },
            // Plain Outlined Button
            { 'text-gray-500 border border-gray-500': props.plain && props.outlined },

            // Text Button
            { 'bg-transparent border-transparent': props.text && !props.plain },

            // Outlined Button
            { 'bg-transparent border': props.outlined && !props.plain },

            // --- Severity Buttons ---

            // Primary Button
            {
                'text-white dark:text-primary-950': !props.link && props.severity === null && !props.text && !props.outlined && !props.plain,
                'bg-primary-500 hover:bg-primary-600': !props.link && props.severity === null && !props.text && !props.outlined && !props.plain,
                'border border-primary-500': !props.link && props.severity === null && !props.text && !props.outlined && !props.plain
            },
            // Primary Text Button
            { 'text-white': props.text && props.severity === null && !props.plain },
            // Primary Outlined Button
            { 'text-white border border-primary': props.outlined && props.severity === null && !props.plain },

            // Secondary Button
            {
                'text-gray-900 dark:text-white': props.severity === 'secondary' && !props.text && !props.outlined && !props.plain,
                'bg-gray-200 dark:bg-gray-700': props.severity === 'secondary' && !props.text && !props.outlined && !props.plain,
                'border border-gray-100 dark:border-gray-700': props.severity === 'secondary' && !props.text && !props.outlined && !props.plain
            },
            // Secondary Text Button
            { 'text-gray-500 dark:text-gray-300': props.text && props.severity === 'secondary' && !props.plain },
            // Secondary Outlined Button
            { 'text-gray-500 dark:text-gray-300 border border-gray-500 hover:bg-gray-300/10': props.outlined && props.severity === 'secondary' && !props.plain },

            // Success Button
            {
                'text-white dark:text-green-900': props.severity === 'success' && !props.text && !props.outlined && !props.plain,
                'bg-green-500 dark:bg-green-400': props.severity === 'success' && !props.text && !props.outlined && !props.plain,
                'border border-green-500 dark:border-green-400': props.severity === 'success' && !props.text && !props.outlined && !props.plain
            },
            // Success Text Button
            { 'text-green-500 dark:text-green-400': props.text && props.severity === 'success' && !props.plain },
            // Success Outlined Button
            { 'text-green-500 border border-green-500 hover:bg-green-300/10': props.outlined && props.severity === 'success' && !props.plain },

            // Info Button
            {
                'text-white dark:text-gray-900': props.severity === 'info' && !props.text && !props.outlined && !props.plain,
                'bg-blue-500 dark:bg-blue-400': props.severity === 'info' && !props.text && !props.outlined && !props.plain,
                'border border-blue-500 dark:border-blue-400': props.severity === 'info' && !props.text && !props.outlined && !props.plain
            },
            // Info Text Button
            { 'text-blue-500 dark:text-blue-400': props.text && props.severity === 'info' && !props.plain },
            // Info Outlined Button
            { 'text-blue-500 border border-blue-500 hover:bg-blue-300/10 ': props.outlined && props.severity === 'info' && !props.plain },

            // Warning Button
            {
                'text-white dark:text-gray-900': props.severity === 'warn' && !props.text && !props.outlined && !props.plain,
                'bg-orange-500 dark:bg-orange-400': props.severity === 'warn' && !props.text && !props.outlined && !props.plain,
                'border border-orange-500 dark:border-orange-400': props.severity === 'warn' && !props.text && !props.outlined && !props.plain
            },
            // Warning Text Button
            { 'text-orange-500 dark:text-orange-400': props.text && props.severity === 'warn' && !props.plain },
            // Warning Outlined Button
            { 'text-orange-500 border border-orange-500 hover:bg-orange-300/10': props.outlined && props.severity === 'warn' && !props.plain },

            // Help Button
            {
                'text-white dark:text-gray-900': props.severity === 'help' && !props.text && !props.outlined && !props.plain,
                'bg-purple-500 dark:bg-purple-400': props.severity === 'help' && !props.text && !props.outlined && !props.plain,
                'border border-purple-500 dark:border-purple-400': props.severity === 'help' && !props.text && !props.outlined && !props.plain
            },
            // Help Text Button
            { 'text-purple-500 dark:text-purple-400': props.text && props.severity === 'help' && !props.plain },
            // Help Outlined Button
            { 'text-purple-500 border border-purple-500 hover:bg-purple-300/10': props.outlined && props.severity === 'help' && !props.plain },

            // Danger Button
            {
                'text-white': props.severity === 'danger' && !props.text && !props.outlined && !props.plain,
                'bg-error-500 dark:bg-error-600': props.severity === 'danger' && !props.text && !props.outlined && !props.plain,
                'border border-error-500 dark:border-error-400': props.severity === 'danger' && !props.text && !props.outlined && !props.plain
            },
            // Danger Text Button
            { 'text-error-500 dark:text-error-400': props.text && props.severity === 'danger' && !props.plain },
            // Danger Outlined Button
            { 'text-error-500 border border-error-500 hover:bg-error-300/10': props.outlined && props.severity === 'danger' && !props.plain },

            // Contrast Button
            {
                'text-white dark:text-gray-900': props.severity === 'contrast' && !props.text && !props.outlined && !props.plain,
                'bg-gray-900 dark:bg-white': props.severity === 'contrast' && !props.text && !props.outlined && !props.plain,
                'border border-gray-900 dark:border-white': props.severity === 'contrast' && !props.text && !props.outlined && !props.plain
            },
            // Contrast Text Button
            { 'text-gray-900 dark:text-white': props.text && props.severity === 'contrast' && !props.plain },
            // Contrast Outlined Button
            { 'text-gray-900 dark:text-white border border-gray-900 dark:border-white': props.outlined && props.severity === 'contrast' && !props.plain },

            // --- Severity Button States ---
            'focus:outline-none focus:outline-offset-0 focus:ring-1',

            // Link
            { 'focus:ring-primary': props.link },

            // Plain
            { 'hover:bg-gray-600 hover:border-gray-600': props.plain && !props.outlined && !props.text },
            // Text & Outlined Button
            { 'hover:bg-gray-300/10': props.plain && (props.text || props.outlined) },

            // Primary
            { 'hover:bg-primary-emphasis hover:border-primary-emphasis': !props.link && props.severity === null && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-primary': props.severity === null },
            // Text & Outlined Button
            { 'hover:bg-primary-300/10': (props.text || props.outlined) && props.severity === null && !props.plain },

            // Secondary
            { 'hover:bg-gray-200 dark:hover:bg-gray-600 hover:border-gray-200 dark:hover:border-gray-600': props.severity === 'secondary' && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-gray-500 dark:focus:ring-gray-400': props.severity === 'secondary' },
            // Text & Outlined Button
            { 'hover:bg-gray-300/10': (props.text || props.outlined) && props.severity === 'secondary' && !props.plain },

            // Success
            { 'hover:bg-green-600 dark:hover:bg-green-300 hover:border-green-600 dark:hover:border-green-300': props.severity === 'success' && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-green-500 dark:focus:ring-green-400': props.severity === 'success' },
            // Text & Outlined Button
            { 'hover:bg-green-300/10': (props.text || props.outlined) && props.severity === 'success' && !props.plain },

            // Info
            { 'hover:bg-blue-600 dark:hover:bg-blue-300 hover:border-blue-600 dark:hover:border-blue-300': props.severity === 'info' && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-blue-500 dark:focus:ring-blue-400': props.severity === 'info' },
            // Text & Outlined Button
            { 'hover:bg-blue-300/10': (props.text || props.outlined) && props.severity === 'info' && !props.plain },

            // Warning
            { 'hover:bg-orange-600 dark:hover:bg-orange-300 hover:border-orange-600 dark:hover:border-orange-300': props.severity === 'warn' && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-orange-500 dark:focus:ring-orange-400': props.severity === 'warn' },
            // Text & Outlined Button
            { 'hover:bg-orange-300/10': (props.text || props.outlined) && props.severity === 'warn' && !props.plain },

            // Help
            { 'hover:bg-purple-600 dark:hover:bg-purple-300 hover:border-purple-600 dark:hover:border-purple-300': props.severity === 'help' && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-purple-500 dark:focus:ring-purple-400': props.severity === 'help' },
            // Text & Outlined Button
            { 'hover:bg-purple-300/10': (props.text || props.outlined) && props.severity === 'help' && !props.plain },

            // Danger
            { 'hover:bg-error-600 dark:hover:bg-error-300 hover:border-error-600 dark:hover:border-error-300': props.severity === 'danger' && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-error-500 dark:focus:ring-error-400': props.severity === 'danger' },
            // Text & Outlined Button
            { 'hover:bg-error-300/10': (props.text || props.outlined) && props.severity === 'danger' && !props.plain },

            // Contrast
            { 'hover:bg-gray-800 dark:hover:bg-gray-100 hover:border-gray-800 dark:hover:border-gray-100': props.severity === 'contrast' && !props.text && !props.outlined && !props.plain },
            { 'focus:ring-gray-500 dark:focus:ring-gray-400': props.severity === 'contrast' },
            // Text & Outlined Button
            { 'hover:bg-gray-900/10 dark:hover:bg-[rgba(255,255,255,0.03)]': (props.text || props.outlined) && props.severity === 'contrast' && !props.plain },

            // Disabled
            { 'opacity-60 pointer-events-none cursor-default': context.disabled },

            // Transitions
            'transition duration-200 ease-in-out',

            // Misc
            'cursor-pointer overflow-hidden select-none',

            // Badge
            '[&>[data-pc-name=badge]]:min-w-4 [&>[data-pc-name=badge]]:h-4 [&>[data-pc-name=badge]]:leading-4'
        ]
    }),
    label: ({ props }) => ({
        class: [
            'duration-200',
            'font-medium',
            {
                'hover:underline': props.link
            },
            { 'flex-1': props.label !== null, 'invisible w-0': props.label == null }
        ]
    }),
    icon: ({ props }) => ({
        class: [
            'text-base leading-4',
            'mx-0',
            {
                'mr-2': props.iconPos == 'left' && props.label != null,
                'ml-2 order-1': props.iconPos == 'right' && props.label != null,
                'order-2': props.iconPos == 'bottom' && props.label != null
            }
        ]
    }),
    loadingIcon: ({ props }) => ({
        class: [
            'h-4 w-4',
            'mx-0',
            {
                'mr-2': props.iconPos == 'left' && props.label != null,
                'ml-2 order-1': props.iconPos == 'right' && props.label != null,
                'mb-2': props.iconPos == 'top' && props.label != null,
                'mt-2': props.iconPos == 'bottom' && props.label != null
            },
            'animate-spin'
        ]
    }),
    badge: ({ props }) => ({
        class: [{ 'ml-2 w-4 h-4 leading-none flex items-center justify-center': props.badge }]
    })
};
