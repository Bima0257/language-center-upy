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
</script>

<template>
    <BaseModal :show="show" max-width="2xl" :closeable="false">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="soal-grid-modal-title"
            class="max-h-[calc(100dvh-3rem)] min-h-0 flex flex-col overflow-hidden"
        >
            <!-- HEADER -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant shrink-0">
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
                    class="w-9 h-9 rounded-full flex items-center justify-center text-text-muted hover:text-text-heading hover:bg-surface-container-low transition-colors"
                >
                    <IconX :size="20" />
                </button>
            </div>

            <!-- GROUPS -->
            <div class="modal-scrollbar min-h-0 flex-1 px-6 py-5 overflow-y-auto overscroll-contain space-y-5">
                <div v-for="(group, gi) in groups" :key="gi">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-label-md font-bold uppercase tracking-wider text-text-heading">
                            {{ group.keterampilan.nama }} · {{ group.bagianSoal.nama }}
                        </span>
                        <span class="text-[11px] text-text-muted font-medium">
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
                            class="w-9 h-9 rounded-md flex items-center justify-center text-[12px] font-bold transition-all duration-150 cursor-pointer"
                            :class="[
                                isCurrent(soal.nomor_global)
                                    ? 'bg-secondary text-on-secondary shadow-sm ring-2 ring-secondary/30'
                                    : isAnswered(soal)
                                        ? 'bg-success-container text-on-success-container border border-success hover:bg-success hover:text-on-success'
                                        : 'bg-surface-container-low text-text-body border border-outline-variant hover:bg-surface-container-highest'
                            ]"
                        >
                            {{ soal.nomor_global }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-low/50 flex items-center justify-between shrink-0">
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
        </div>
    </BaseModal>
</template>
