<script setup>
import { computed, ref } from "vue";
import { IconUpload, IconX } from "@tabler/icons-vue";

const props = defineProps({
    modelValue: { type: Object, default: null },
    label: { type: String, default: "" },
    accept: { type: String, default: "" },
    hint: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    variant: { type: String, default: "inline" },
    mediaType: { type: String, default: "image" },
    previewUrl: { type: String, default: null },
    required: { type: Boolean, default: false },
    error: { type: String, default: null },
});

const emit = defineEmits(["update:modelValue"]);

const inputRef = ref(null);

const displayName = computed(
    () =>
        props.modelValue?.name ||
        props.placeholder ||
        (props.previewUrl ? "Ganti file" : "Klik untuk upload"),
);

const displayPreview = computed(
    () => props.previewUrl || (props.modelValue ? URL.createObjectURL(props.modelValue) : null),
);

function onSelect(e) {
    emit("update:modelValue", e.target.files[0] || null);
    e.target.value = "";
}

function remove() {
    emit("update:modelValue", null);
    if (inputRef.value) inputRef.value.value = "";
}
</script>

<template>
    <div>
        <label
            v-if="label"
            class="text-label-md font-medium text-primary block mb-1.5"
        >
            {{ label }}
            <span v-if="required" class="text-error-red">*</span>
        </label>

        <!-- INLINE: baris upload kompak -->
        <template v-if="variant === 'inline'">
            <audio
                v-if="mediaType === 'audio' && displayPreview"
                :src="displayPreview"
                controls
                class="w-full max-w-md h-9 mb-2"
            ></audio>
            <img
                v-else-if="mediaType === 'image' && displayPreview"
                :src="displayPreview"
                class="max-h-32 rounded-lg border border-outline-variant/30 object-contain mb-2"
                alt=""
            />
            <label
                class="flex items-center gap-3 border-2 border-dashed border-outline-variant rounded-xl px-4 py-3 cursor-pointer hover:border-secondary transition-colors"
            >
                <IconUpload :size="18" class="text-text-muted" />
                <span class="text-label-md text-text-body">{{ displayName }}</span>
                <input
                    ref="inputRef"
                    type="file"
                    :accept="accept"
                    class="hidden"
                    @change="onSelect"
                />
            </label>
        </template>

        <!-- DROPZONE: kotak upload besar -->
        <template v-else>
            <div
                class="border-2 border-dashed border-outline-variant rounded-2xl p-6 text-center hover:border-secondary transition-colors cursor-pointer"
                :class="{ 'border-error-red': error }"
                @click="inputRef?.click()"
            >
                <IconUpload class="mx-auto text-text-muted mb-2" :size="24" stroke="1.5" />
                <p class="text-label-md text-text-body font-medium">{{ displayName }}</p>
                <p v-if="hint" class="text-xs text-text-muted mt-1">{{ hint }}</p>
            </div>
            <input
                ref="inputRef"
                type="file"
                :accept="accept"
                class="hidden"
                @change="onSelect"
            />

            <div v-if="displayPreview" class="relative mt-3 bg-surface-container-low rounded-xl p-3">
                <audio
                    v-if="mediaType === 'audio'"
                    :src="displayPreview"
                    controls
                    class="w-full h-10"
                    preload="metadata"
                ></audio>
                <img
                    v-else
                    :src="displayPreview"
                    class="max-h-40 rounded-lg object-contain mx-auto"
                    alt=""
                />
                <button
                    type="button"
                    @click="remove"
                    class="absolute -top-2 -right-2 w-6 h-6 bg-error-red text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors shadow-sm"
                    :title="mediaType === 'audio' ? 'Hapus audio' : 'Hapus gambar'"
                >
                    <IconX :size="14" />
                </button>
            </div>
        </template>

        <p v-if="error" class="text-error-red text-xs mt-1">{{ error }}</p>
    </div>
</template>
