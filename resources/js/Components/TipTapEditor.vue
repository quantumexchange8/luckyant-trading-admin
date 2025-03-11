<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Placeholder from '@tiptap/extension-placeholder'
import {
    IconBold,
    IconItalic,
    IconUnderline,
    IconLetterPSmall,
    IconH1,
    IconH2,
    IconList,
    IconListNumbers,
    IconArrowBackUp,
    IconArrowForwardUp
} from "@tabler/icons-vue";

const props = defineProps({
    modelValue: String,
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    onUpdate: ({editor}) => {
        emit('update:modelValue', editor.getHTML())
    },
    editorProps: {
        attributes: {
            class: 'rounded dark:bg-gray-950 border border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600 focus:border-primary focus:outline-none focus:ring-0 focus:border-primary-500 dark:focus:border-primary-300 py-2.5 px-3 h-80 overflow-y-auto dark:text-white prose dark:prose-invert min-w-full',
        },
    },
    extensions: [
        StarterKit,
        Underline,
        Placeholder.configure({
            emptyEditorClass: 'cursor-text before:content-[attr(data-placeholder)] before:absolute before:top-[10px] before:left-3 before:text-mauve-11 before:opacity-50 before-pointer-events-none',
            placeholder: 'Enter content...',
        }),
    ],
})

</script>

<template>
    <section
        v-if="editor"
        class="flex items-center flex-wrap gap-5 rounded dark:bg-gray-950 border border-gray-300 dark:border-gray-600 p-1 w-full"
    >
        <div class="flex items-center flex-wrap gap-1">
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :disabled="!editor.can().chain().focus().toggleBold().run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('bold') }
            ]"
            >
                <IconBold size="20" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :disabled="!editor.can().chain().focus().toggleItalic().run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('italic') }
            ]"
            >
                <IconItalic size="20" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('underline') }
            ]"
            >
                <IconUnderline size="20" />
            </button>
        </div>

        <div class="flex items-center flex-wrap gap-1">
            <button
                type="button"
                @click="editor.chain().focus().setParagraph().run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('paragraph') }
            ]"
            >
                <IconLetterPSmall size="20" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('heading', { level: 1 }) }
            ]"
            >
                <IconH1 size="20" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('heading', { level: 2 }) }
            ]"
            >
                <IconH2 size="20" />
            </button>
        </div>

        <div class="flex items-center flex-wrap gap-1">
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('bulletList') }
            ]"
            >
                <IconList size="20" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="[
                'rounded p-1 dark:text-white',
                { 'bg-gray-200 dark:bg-gray-700': editor.isActive('orderedList') }
            ]"
            >
                <IconListNumbers size="20" />
            </button>
        </div>

        <div class="flex items-center flex-wrap gap-1">
            <button
                type="button"
                class="dark:text-white disabled:text-gray-400 dark:disabled:text-gray-600 rounded p-1"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().chain().focus().undo().run()">
                <IconArrowBackUp size="20" />
            </button>
            <button
                type="button"
                class="dark:text-white disabled:text-gray-400 dark:disabled:text-gray-600 rounded p-1"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().chain().focus().redo().run()">
                <IconArrowForwardUp size="20" />
            </button>
        </div>
    </section>
    <EditorContent :editor="editor" class="w-full" />
</template>
