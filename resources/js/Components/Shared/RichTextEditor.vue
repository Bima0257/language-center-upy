<script setup>
import { ref, onBeforeUnmount, watch } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import {
    IconBold,
    IconItalic,
    IconUnderline,
    IconStrikethrough,
    IconHeading,
    IconList,
    IconListNumbers,
    IconQuote,
    IconAlignLeft,
    IconAlignCenter,
    IconAlignRight,
    IconAlignJustified,
    IconLink,
    IconArrowBackUp,
    IconArrowForwardUp,
} from '@tabler/icons-vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Tulis konten di sini...' },
    minHeight: { type: String, default: '160px' },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
        }),
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Link.configure({ openOnClick: false, autolink: true }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value || '', { emitUpdate: false });
    }
});

function isActive(name, attrs) {
    return editor.value?.isActive(name, attrs) ?? false;
}

function setLink() {
    const prevUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Masukkan URL:', prevUrl || 'https://');
    if (url === null) return;
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div class="border border-outline-variant rounded-2xl overflow-hidden bg-surface-container-lowest">
        <div v-if="editor" class="flex flex-wrap items-center gap-1 px-2 py-1.5 border-b border-outline-variant/50 bg-surface-container-low">
            <button type="button" @click="editor.chain().focus().toggleBold().run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('bold') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Bold"><IconBold :size="16" /></button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('italic') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Italic"><IconItalic :size="16" /></button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('underline') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Underline"><IconUnderline :size="16" /></button>
            <button type="button" @click="editor.chain().focus().toggleStrike().run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('strike') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Strikethrough"><IconStrikethrough :size="16" /></button>

            <span class="w-px h-5 bg-outline-variant mx-1"></span>

            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('heading', { level: 1 }) ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Heading 1"><IconHeading :size="16" /></button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('heading', { level: 2 }) ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Heading 2"><span class="text-xs font-bold">H2</span></button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('heading', { level: 3 }) ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Heading 3"><span class="text-xs font-bold">H3</span></button>

            <span class="w-px h-5 bg-outline-variant mx-1"></span>

            <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('bulletList') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Bullet List"><IconList :size="16" /></button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('orderedList') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Numbered List"><IconListNumbers :size="16" /></button>
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('blockquote') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Quote"><IconQuote :size="16" /></button>

            <span class="w-px h-5 bg-outline-variant mx-1"></span>

            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive({ textAlign: 'left' }) ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Align Left"><IconAlignLeft :size="16" /></button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive({ textAlign: 'center' }) ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Align Center"><IconAlignCenter :size="16" /></button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive({ textAlign: 'right' }) ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Align Right"><IconAlignRight :size="16" /></button>
            <button type="button" @click="editor.chain().focus().setTextAlign('justify').run()"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive({ textAlign: 'justify' }) ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Justify"><IconAlignJustified :size="16" /></button>

            <span class="w-px h-5 bg-outline-variant mx-1"></span>

            <button type="button" @click="setLink"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="isActive('link') ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-highest'"
                    title="Link"><IconLink :size="16" /></button>

            <span class="w-px h-5 bg-outline-variant mx-1"></span>

            <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()"
                    class="p-1.5 rounded-lg transition-colors text-text-body hover:bg-surface-container-highest disabled:opacity-30"
                    title="Undo"><IconArrowBackUp :size="16" /></button>
            <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()"
                    class="p-1.5 rounded-lg transition-colors text-text-body hover:bg-surface-container-highest disabled:opacity-30"
                    title="Redo"><IconArrowForwardUp :size="16" /></button>
        </div>
        <div class="px-4 py-3 rich-text-content text-text-body text-body-md" :style="{ minHeight }">
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>
