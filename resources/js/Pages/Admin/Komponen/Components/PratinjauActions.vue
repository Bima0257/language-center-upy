<script setup>
import PreviewCard from "@/Pages/Admin/Komponen/Components/KartuPratinjau.vue";
import GrupPratinjau from "@/Pages/Admin/Komponen/Components/GrupPratinjau.vue";

defineProps({
    group: { type: Object, required: true },
    componentMeta: { type: Object, required: true },
    isVisible: { type: Function, required: true },
    toggleValue: { type: Boolean, required: true },
    activeTab: { type: String, required: true },
    demoTabs: { type: Array, default: () => [] },
    notifyButtonAction: { type: Function, required: true },
});

const emit = defineEmits(["update:toggle-value", "update:active-tab"]);
</script>

<template>
    <GrupPratinjau :group="group">
        <PreviewCard
            v-if="isVisible('BaseButton')"
            name="BaseButton"
            scope="Global"
            :description="componentMeta.BaseButton.description"
            :path="componentMeta.BaseButton.path"
        >
            <div class="flex flex-wrap items-center gap-2">
                <BaseButton size="sm" @click="notifyButtonAction">Primary</BaseButton>
                <BaseButton size="sm" variant="secondary">Secondary</BaseButton>
                <BaseButton size="sm" variant="danger">Danger</BaseButton>
                <BaseButton size="sm" variant="ghost">Ghost</BaseButton>
                <BaseButton size="sm" disabled>Disabled</BaseButton>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseToggle')"
            name="BaseToggle"
            scope="Global"
            :description="componentMeta.BaseToggle.description"
            :path="componentMeta.BaseToggle.path"
        >
            <div class="space-y-3">
                <BaseToggle
                    :model-value="toggleValue"
                    label="Notifikasi email"
                    @update:model-value="emit('update:toggle-value', $event)"
                />
                <BaseToggle :model-value="false" label="State nonaktif" disabled />
                <p class="text-label-md text-text-muted">
                    Nilai aktif:
                    <span class="font-semibold text-primary">{{ toggleValue ? "true" : "false" }}</span>
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseTabs')"
            name="BaseTabs"
            scope="Global"
            :description="componentMeta.BaseTabs.description"
            :path="componentMeta.BaseTabs.path"
        >
            <div class="space-y-4">
                <BaseTabs
                    :model-value="activeTab"
                    :items="demoTabs"
                    @update:model-value="emit('update:active-tab', $event)"
                />
                <p class="text-body-md text-text-body">
                    Tab aktif: <span class="font-semibold text-primary">{{ activeTab }}</span>
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseTabsChrome')"
            name="BaseTabsChrome"
            scope="Global"
            :description="componentMeta.BaseTabsChrome.description"
            :path="componentMeta.BaseTabsChrome.path"
        >
            <div class="overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-white">
                <BaseTabsChrome
                    :model-value="activeTab"
                    :items="demoTabs"
                    aria-label="Contoh tab bergaya browser"
                    @update:model-value="emit('update:active-tab', $event)"
                />
                <div class="px-5 py-6 text-body-md text-text-body">
                    Konten tab aktif: <span class="font-semibold text-primary">{{ activeTab }}</span>
                </div>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseAccordion')"
            name="BaseAccordion"
            scope="Global"
            :description="componentMeta.BaseAccordion.description"
            :path="componentMeta.BaseAccordion.path"
        >
            <BaseAccordion title="Bagaimana komponen ini bekerja?" :default-open="true">
                <p class="text-body-md text-text-body">
                    Konten accordion dirender melalui slotUjian default slotUjian dan dapat memuat elemen apa pun.
                </p>
            </BaseAccordion>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseTooltip')"
            name="BaseTooltip"
            scope="Global"
            :description="componentMeta.BaseTooltip.description"
            :path="componentMeta.BaseTooltip.path"
        >
            <div class="flex flex-wrap gap-3">
                <BaseTooltip text="Tooltip di atas" position="top">
                    <BaseButton size="sm" variant="secondary">Hover top</BaseButton>
                </BaseTooltip>
                <BaseTooltip text="Tooltip di kanan" position="right">
                    <BaseButton size="sm" variant="secondary">Hover right</BaseButton>
                </BaseTooltip>
            </div>
        </PreviewCard>
    </GrupPratinjau>
</template>
