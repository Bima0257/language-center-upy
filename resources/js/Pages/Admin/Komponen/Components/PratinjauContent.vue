<script setup>
import RichTextEditor from "@/Components/Shared/RichTextEditor.vue";
import RichTextViewer from "@/Components/Shared/RichTextViewer.vue";
import PreviewCard from "@/Pages/Admin/Komponen/Components/KartuPratinjau.vue";
import GrupPratinjau from "@/Pages/Admin/Komponen/Components/GrupPratinjau.vue";

defineProps({
    group: { type: Object, required: true },
    componentMeta: { type: Object, required: true },
    isVisible: { type: Function, required: true },
    mediaLoading: { type: Boolean, required: true },
    richText: { type: String, required: true },
    toggleMediaLoading: { type: Function, required: true },
});

const emit = defineEmits(["update:rich-text"]);
</script>

<template>
    <GrupPratinjau :group="group">
        <PreviewCard
            v-if="isVisible('BaseSkeleton')"
            name="BaseSkeleton"
            scope="Global"
            :description="componentMeta.BaseSkeleton.description"
            :path="componentMeta.BaseSkeleton.path"
        >
            <div class="space-y-3">
                <BaseSkeleton height="1.25rem" width="72%" />
                <BaseSkeleton height="0.9rem" width="100%" />
                <div class="flex items-center gap-3">
                    <BaseSkeleton height="2.75rem" circle />
                    <BaseSkeleton height="0.9rem" width="45%" />
                </div>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseMediaLoader')"
            name="BaseMediaLoader"
            scope="Global"
            :description="componentMeta.BaseMediaLoader.description"
            :path="componentMeta.BaseMediaLoader.path"
        >
            <div class="space-y-3">
                <BaseMediaLoader :loading="mediaLoading" media-type="image">
                    <img
                        src="/assets/image/default-materiSoal.png"
                        alt="Contoh media"
                        class="h-36 w-full rounded-xl object-cover"
                    />
                </BaseMediaLoader>
                <BaseButton size="sm" variant="secondary" @click="toggleMediaLoading">
                    {{ mediaLoading ? "Tampilkan media" : "Tampilkan loading" }}
                </BaseButton>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('RichTextEditor')"
            name="RichTextEditor"
            scope="Reusable"
            :description="componentMeta.RichTextEditor.description"
            :path="componentMeta.RichTextEditor.path"
        >
            <RichTextEditor
                :model-value="richText"
                min-height="140px"
                @update:model-value="emit('update:rich-text', $event)"
            />
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('RichTextViewer')"
            name="RichTextViewer"
            scope="Reusable"
            :description="componentMeta.RichTextViewer.description"
            :path="componentMeta.RichTextViewer.path"
        >
            <RichTextViewer :content="richText" />
        </PreviewCard>
    </GrupPratinjau>
</template>
