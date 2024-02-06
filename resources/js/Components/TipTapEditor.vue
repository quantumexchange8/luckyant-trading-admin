<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Placeholder from '@tiptap/extension-placeholder'
import BoldIcon from 'vue-material-design-icons/FormatBold.vue';
import ItalicIcon from 'vue-material-design-icons/FormatItalic.vue';
import UnderlineIcon from 'vue-material-design-icons/FormatUnderline.vue';
import CodeIcon from 'vue-material-design-icons/CodeTags.vue';
import ParaIcon from 'vue-material-design-icons/FormatParagraph.vue';
import Header1Icon from 'vue-material-design-icons/FormatHeader1.vue';
import Header2Icon from 'vue-material-design-icons/FormatHeader2.vue';
import BulletIcon from 'vue-material-design-icons/FormatListBulleted.vue';
import NumberListIcon from 'vue-material-design-icons/FormatListNumbered.vue';
import UndoIcon from 'vue-material-design-icons/Undo.vue';
import RedoIcon from 'vue-material-design-icons/Redo.vue';

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
            class: 'rounded-lg dark:bg-gray-600 border border-transparent focus:border-pink-700 focus:ring focus:ring-pink-500 focus:ring-offset-0 focus:ring-offset-white focus:outline-none focus-visible:ring py-2 px-3.5 min-h-[120px] max-h-[240px] overflow-y-auto dark:text-gray-300 prose leading-3',
        },
    },
    extensions: [
        StarterKit,
        Underline,
        Placeholder.configure({
            emptyEditorClass: 'cursor-text before:content-[attr(data-placeholder)] before:absolute before:top-2 before:left-4 before:text-mauve-11 before:opacity-50 before-pointer-events-none',
            placeholder: 'Enter details …',
        }),
    ],
})

</script>

<template>
    <section
        v-if="editor"
        class="flex items-center flex-wrap gap-x-4 rounded-lg dark:bg-gray-600 border border-gray-400 dark:border-gray-600 p-3"
    >
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleBold().run()"
            :disabled="!editor.can().chain().focus().toggleBold().run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('bold') }"
        >
            <BoldIcon title="Bold" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleItalic().run()"
            :disabled="!editor.can().chain().focus().toggleItalic().run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('italic') }">
            <ItalicIcon title="Italic" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleUnderline().run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('underline') }">
            <UnderlineIcon title="Underline" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleCode().run()"
            :disabled="!editor.can().chain().focus().toggleCode().run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('code') }">
            <CodeIcon title="Code" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().setParagraph().run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('paragraph') }">
            <ParaIcon title="Paragraph" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('heading', { level: 1 }) }">
            <Header1Icon title="Header 1" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('heading', { level: 2 }) }">
            <Header2Icon title="Header 2" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleBulletList().run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('bulletList') }">
            <BulletIcon title="Bullet List" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="rounded p-1"
            @click="editor.chain().focus().toggleOrderedList().run()"
            :class="{ 'dark:bg-gray-400': editor.isActive('orderedList') }">
            <NumberListIcon title="Number List" class="dark:text-white" />
        </button>
        <button
            type="button"
            class="dark:text-white dark:disabled:text-gray-500 rounded p-1"
            @click="editor.chain().focus().undo().run()"
            :disabled="!editor.can().chain().focus().undo().run()">
            <UndoIcon title="Undo" />
        </button>
        <button
            type="button"
            class="dark:text-white dark:disabled:text-gray-500 rounded p-1"
            @click="editor.chain().focus().redo().run()"
            :disabled="!editor.can().chain().focus().redo().run()">
            <RedoIcon title="Redo" />
        </button>
    </section>
    <EditorContent :editor="editor" />
</template>
