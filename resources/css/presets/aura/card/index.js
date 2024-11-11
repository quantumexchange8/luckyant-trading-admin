export default {
    root: {
        class: [
            //Flex
            'flex flex-col',

            //Shape
            'rounded-lg',
            'shadow-toast',

            //Color
            'bg-surface-0 dark:bg-surface-900',
            'text-gray-950 dark:text-surface-0'
        ]
    },
    body: {
        class: [
            //Flex
            'flex flex-col',
            'gap-4',

            'p-5'
        ]
    },
    caption: {
        class: [
            //Flex
            'flex flex-col',
            'gap-2'
        ]
    },
    title: {
        class: 'text-xl font-semibold mb-0'
    },
    subtitle: {
        class: [
            //Font
            'font-normal',

            //Spacing
            'mb-0',

            //Color
            'text-surface-600 dark:text-surface-0/60'
        ]
    },
    content: {
        class: 'p-0'
    },
    footer: {
        class: 'p-0'
    }
};
