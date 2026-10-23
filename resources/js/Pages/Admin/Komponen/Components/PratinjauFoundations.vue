<script setup>
import { IconFileDescription } from "@tabler/icons-vue";
import EmptyState from "@/Components/Shared/EmptyState.vue";
import StatusBadge from "@/Components/Shared/StatusBadge.vue";
import PreviewCard from "@/Pages/Admin/Komponen/components/KartuPratinjau.vue";
import GrupPratinjau from "@/Pages/Admin/Komponen/components/GrupPratinjau.vue";

defineProps({
    group: { type: Object, required: true },
    componentMeta: { type: Object, required: true },
    isVisible: { type: Function, required: true },
});
</script>

<template>
    <GrupPratinjau :group="group">
        <PreviewCard
            v-if="isVisible('BaseCard')"
            name="BaseCard"
            scope="Global"
            :description="componentMeta.BaseCard.description"
            :path="componentMeta.BaseCard.path"
        >
            <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-low p-4">
                <p class="text-title-lg font-semibold text-text-heading">Card preview</p>
                <p class="mt-1 text-body-md text-text-body">
                    Slot bebas dapat berisi ringkasan, form, atau data.
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseBadge')"
            name="BaseBadge"
            scope="Global"
            :description="componentMeta.BaseBadge.description"
            :path="componentMeta.BaseBadge.path"
        >
            <div class="flex flex-wrap gap-2">
                <BaseBadge variant="neutral">Neutral</BaseBadge>
                <BaseBadge variant="primary">Primary</BaseBadge>
                <BaseBadge variant="secondary">Secondary</BaseBadge>
                <BaseBadge variant="success">Success</BaseBadge>
                <BaseBadge variant="warning">Warning</BaseBadge>
                <BaseBadge variant="danger">Danger</BaseBadge>
                <BaseBadge variant="pastel">Pastel</BaseBadge>
                <BaseBadge variant="pastel-peach">Peach</BaseBadge>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseAvatar')"
            name="BaseAvatar"
            scope="Global"
            :description="componentMeta.BaseAvatar.description"
            :path="componentMeta.BaseAvatar.path"
        >
            <div class="flex flex-wrap items-center gap-5">
                <BaseAvatar name="Ayu Pratama" :size="56" />
                <BaseAvatar name="UPY" variant="neutral" :size="48" />
                <BaseAvatar
                    src="/assets/image/logo-dark.png"
                    name="UPY Language Center"
                    :size="64"
                    class="border-2 border-outline-variant"
                />
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('EmptyState')"
            name="EmptyState"
            scope="Reusable"
            :description="componentMeta.EmptyState.description"
            :path="componentMeta.EmptyState.path"
        >
            <EmptyState
                :icon="IconFileDescription"
                title="Belum ada materi"
                description="Tambahkan materi pertama untuk mulai mengisi daftar."
                class="!p-6"
            >
                <BaseButton size="sm" class="mt-4">Tambah Materi</BaseButton>
            </EmptyState>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('StatusBadge')"
            name="StatusBadge"
            scope="Reusable"
            :description="componentMeta.StatusBadge.description"
            :path="componentMeta.StatusBadge.path"
        >
            <div class="flex flex-wrap gap-2">
                <StatusBadge status="draft" />
                <StatusBadge status="approved" />
                <StatusBadge status="rejected" />
                <StatusBadge status="custom" :labels="{ custom: 'Kustom' }" />
            </div>
        </PreviewCard>
    </GrupPratinjau>
</template>
