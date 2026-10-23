<script setup>
import { Head, router } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import DropDown from '@/Components/Shared/DropDown.vue'
import Pagination from '@/Components/Shared/Pagination.vue'
import KartuPeriodeUjian from '@/Pages/Admin/PeriodeUjian/Components/KartuPeriodeUjian.vue'
import { IconPlus } from '@tabler/icons-vue'
import { computed, ref } from 'vue'
import { useConfirm } from '@/Composables/useConfirm'

const confirm = useConfirm()

defineProps({
    periodeUjian: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    ujian: { type: Array, default: () => [] },
})

const ujianTerpilihId = ref('')

const canCreate = computed(() => !!ujianTerpilihId.value)

function createSchedule() {
    if (!ujianTerpilihId.value) return
    router.get(route('admin.periode-ujian.tambah', ujianTerpilihId.value))
}

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'
}

async function destroy(periodeUjian) {
    if (periodeUjian.slot_ujian_count > 0) return
    if (!await confirm.confirm(`Hapus periode "${periodeUjian.judul}"?`)) return
    router.delete(route('admin.periode-ujian.hapus', periodeUjian.id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Penjadwalan Ujian" />
    <TataLetakDasbor title="Penjadwalan Ujian" subtitle="Kelola periode dan slotUjian jadwal ujian">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
            <div class="w-80">
                <DropDown
                    v-model="ujianTerpilihId"
                    :options="ujian"
                    label="Pilih Ujian"
                    placeholder="Pilih ujian untuk membuat periode"
                    option-label="judul"
                    option-value="id"
                />
            </div>
            <BaseButton @click="createSchedule" :disabled="!canCreate">
                <IconPlus :size="18" /> Buat Periode Ujian
            </BaseButton>
        </div>

        <div v-if="periodeUjian.data.length === 0"
             class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <h2 class="text-title-lg font-semibold text-text-heading mb-2">Belum Ada Periode Ujian</h2>
            <p class="text-text-body text-body-md">Pilih ujian di atas, lalu klik "Buat Periode Ujian".</p>
        </div>

        <div v-else class="space-y-3">
            <KartuPeriodeUjian
                v-for="periodeUjian in periodeUjian.data"
                :key="periodeUjian.id"
                :periode-ujian="periodeUjian"
                :format-date="formatDate"
                @delete="destroy"
            />
        </div>

        <Pagination :links="periodeUjian.links" :total="periodeUjian.meta?.total ?? 0" :per-page="periodeUjian.meta?.per_page ?? 0" />
    </TataLetakDasbor>
</template>
