<script setup>
import { computed } from 'vue';
import { IconX, IconMap } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    groups: { type: Array, default: () => [] },
    answers: { type: Object, default: () => ({}) },
    currentGlobalNumber: { type: Number, default: 0 },
});

const emit = defineEmits(['navigateGlobal', 'close']);

const jumlahSoal = computed(() =>
    props.groups.reduce((sum, g) => sum + g.jumlahSoal, 0),
);

const answeredCount = computed(() => {
    let count = 0;
    for (const group of props.groups) {
        for (const soal of group.soal) {
            if (props.answers[soal.id] !== undefined) count++;
        }
    }
    return count;
});

function isAnswered(soal) {
    return props.answers[soal.id] !== undefined;
}

function isCurrent(number) {
    return number === props.currentGlobalNumber;
}

function partLabel(group) {
    const original = group.partSoal?.nama?.trim() || 'Part';

    for (const prefix of [group.section?.nama, group.section?.kode]) {
        if (!prefix) continue;

        const escaped = prefix.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const stripped = original
            .replace(new RegExp(`^${escaped}(?:\\s*[-–—:·]\\s*|\\s+)`, 'i'), '')
            .trim();

        if (stripped && stripped !== original) {
            return stripped;
        }
    }

    return original;
}

const sectionGroups = computed(() => {
    const sections = [];
    const sectionMap = new Map();

    for (const group of props.groups) {
        const sectionKey = group.section?.kode || group.section?.nama || `section-${sections.length}`;
        const questionNumbers = group.soal
            .map((soal) => Number(soal.nomor_global))
            .filter((number) => Number.isFinite(number));

        if (!sectionMap.has(sectionKey)) {
            const section = {
                key: sectionKey,
                nama: group.section?.nama || 'Section',
                groups: [],
                jumlahSoal: 0,
                answered: 0,
                nomorMulai: questionNumbers.length ? Math.min(...questionNumbers) : group.nomorMulai,
                nomorSelesai: questionNumbers.length
                    ? Math.max(...questionNumbers)
                    : group.nomorMulai + group.jumlahSoal - 1,
            };

            sectionMap.set(sectionKey, section);
            sections.push(section);
        }

        const section = sectionMap.get(sectionKey);

        section.groups.push(group);
        section.jumlahSoal += group.soal.length;
        section.answered += group.soal.filter(isAnswered).length;

        if (questionNumbers.length) {
            section.nomorMulai = Math.min(section.nomorMulai, ...questionNumbers);
            section.nomorSelesai = Math.max(section.nomorSelesai, ...questionNumbers);
        }
    }

    return sections;
});
</script>

<template>
    <BaseModal
        :show="show"
        max-width="2xl"
        :closeable="false"
        scrollable
        aria-labelledby="soal-grid-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="flex items-center justify-between px-6 py-4 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center">
                            <IconMap :size="20" class="text-secondary" />
                        </div>
                        <div>
                            <h3 id="soal-grid-modal-title" class="text-title-lg font-semibold text-text-heading">Peta Soal</h3>
                            <p class="text-label-md text-text-muted">
                                {{ answeredCount }} dari {{ jumlahSoal }} soal terjawab
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        aria-label="Tutup peta soal"
                        @click="emit('close')"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-text-muted hover:text-text-heading hover:bg-surface-container-low transition-colors font-semibold"
                    >
                        <IconX :size="20" />
                    </button>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <div class="space-y-4 px-6 py-5">
                <section
                    v-for="section in sectionGroups"
                    :key="section.key"
                    class="space-y-4 rounded-2xl border border-outline-variant/40 bg-surface-container-low/60 p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-outline-variant/40 pb-2.5">
                        <div class="min-w-0">
                            <h4 class="truncate text-label-md font-bold uppercase tracking-wider text-text-heading">
                                {{ section.nama }}
                            </h4>
                            <p class="text-xs text-text-muted">
                                Soal {{ section.nomorMulai }}–{{ section.nomorSelesai }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-surface-container-high px-2.5 py-1 text-[11px] font-semibold text-text-muted">
                            {{ section.answered }}/{{ section.jumlahSoal }} terjawab
                        </span>
                    </div>

                    <div
                        v-for="(group, groupIndex) in section.groups"
                        :key="`${section.key}-${groupIndex}`"
                        class="space-y-2"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="min-w-0 truncate text-label-md font-semibold text-text-body">
                                {{ partLabel(group) }}
                            </span>
                            <span class="shrink-0 text-xs text-text-muted font-medium">
                                Soal {{ group.nomorMulai }}–{{ group.nomorMulai + group.jumlahSoal - 1 }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="soal in group.soal"
                                :key="soal.id"
                                type="button"
                                :aria-label="`Buka soal ${soal.nomor_global}`"
                                @click="emit('navigateGlobal', soal.nomor_global)"
                                class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-150 cursor-pointer"
                                :class="[
                                    isCurrent(soal.nomor_global)
                                        ? 'bg-secondary text-on-secondary shadow-xs ring-2 ring-secondary/30'
                                        : isAnswered(soal)
                                            ? 'bg-success-container text-on-success-container border border-success hover:bg-success hover:text-on-success'
                                            : 'bg-surface-container-low text-text-body border border-outline-variant hover:bg-surface-container-highest'
                                ]"
                            >
                                {{ soal.nomor_global }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-6 py-3 bg-surface-container-low/50 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4 text-label-md">
                        <span class="flex items-center gap-1.5 text-text-muted">
                            <span class="w-3 h-3 rounded-full bg-secondary inline-block"></span>
                            Saat ini
                        </span>
                        <span class="flex items-center gap-1.5 text-text-muted">
                            <span class="w-3 h-3 rounded-full bg-success inline-block"></span>
                            Dijawab
                        </span>
                        <span class="flex items-center gap-1.5 text-text-muted">
                            <span class="w-3 h-3 rounded-full bg-surface-container-highest border border-outline-variant inline-block"></span>
                            Belum
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-5 py-2 rounded-full text-label-md font-semibold border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                    >
                        Tutup
                    </button>
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
