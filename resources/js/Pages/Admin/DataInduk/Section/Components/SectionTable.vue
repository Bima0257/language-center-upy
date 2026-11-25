<script setup>
import { computed, h, ref, watch } from 'vue';
import { IconEdit, IconEye, IconTrash } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseTambahData from '@/Components/Base/BaseTambahData.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';

const props = defineProps({
    section: { type: Array, default: () => [] },
    reordering: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
    canCreate: { type: Boolean, default: false },
    canReorder: { type: Boolean, default: true },
});

const emit = defineEmits(['create', 'reorder', 'edit', 'delete', 'detail']);
const isEditingOrder = ref(false);

const localSections = ref(props.section.slice());

watch(
    () => props.section,
    (sections) => {
        localSections.value = sections.slice();
    },
    { deep: true, immediate: true },
);

watch(
    () => props.canReorder,
    (canReorder) => {
        if (!canReorder) isEditingOrder.value = false;
    },
    { immediate: true },
);

const bisaDrag = computed(
    () => props.canManage
        && isEditingOrder.value
        && !props.reordering
        && props.canReorder
        && localSections.value.length > 1,
);

const ukuranHalaman = computed(() => Math.max(1, localSections.value.length));

const columns = [
    {
        accessorKey: 'kode',
        header: 'Kode',
        enableSorting: false,
        meta: { cellClass: 'font-medium text-text-heading' },
    },
    {
        accessorKey: 'nama',
        header: 'Nama',
        enableSorting: false,
        meta: { cellClass: 'font-medium text-text-heading' },
    },
    {
        accessorKey: 'durasi_menit',
        header: 'Durasi',
        enableSorting: false,
        cell: info => `${info.getValue() ?? 0} menit`,
    },
    {
        accessorKey: 'deskripsi',
        header: 'Deskripsi',
        enableSorting: false,
        cell: info => descriptionPreview(info.getValue()),
    },
    {
        accessorKey: 'aktif',
        header: 'Status',
        enableSorting: false,
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() ? 'success' : 'neutral' },
            () => (info.getValue() ? 'Aktif' : 'Nonaktif'),
        ),
    },
    {
        id: 'actions',
        header: 'Aksi',
        enableSorting: false,
        meta: { align: 'center' },
        cell: info => {
            const element = info.row.original;
            const terkunci = Boolean(element.dipakai_ujian_aktif);
            const actions = [
                h(
                    BaseButton,
                    {
                        variant: 'secondary',
                        iconOnly: true,
                        title: 'Lihat detail',
                        'aria-label': 'Lihat detail',
                        onClick: () => emit('detail', element),
                    },
                    () => h(IconEye, { size: 16 }),
                ),
            ];

            if (props.canManage) {
                actions.push(
                    h(
                        BaseButton,
                        {
                            variant: 'secondary',
                            iconOnly: true,
                            disabled: terkunci,
                            title: terkunci ? 'Tidak bisa diubah karena dipakai ujian aktif' : 'Ubah',
                            'aria-label': terkunci ? 'Tidak bisa diubah karena dipakai ujian aktif' : 'Ubah',
                            onClick: () => emit('edit', element),
                        },
                        () => h(IconEdit, { size: 16 }),
                    ),
                    h(
                        BaseButton,
                        {
                            variant: 'danger',
                            iconOnly: true,
                            disabled: terkunci,
                            title: terkunci ? 'Tidak bisa dihapus karena dipakai ujian aktif' : 'Hapus',
                            'aria-label': terkunci ? 'Tidak bisa dihapus karena dipakai ujian aktif' : 'Hapus',
                            onClick: () => emit('delete', element),
                        },
                        () => h(IconTrash, { size: 16 }),
                    ),
                );
            }

            return h('div', { class: 'flex items-center justify-center gap-1' }, actions);
        },
    },
];

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

function descriptionPreview(html) {
    const text = stripHtml(html);
    return text ? (text.length > 60 ? text.substring(0, 60) + '…' : text) : '-';
}

function onRowDrop({ from, to }) {
    if (!bisaDrag.value) return;

    const list = localSections.value.slice();

    if (from < 0 || to < 0 || from >= list.length || to >= list.length) return;

    const [moved] = list.splice(from, 1);
    list.splice(to, 0, moved);
    localSections.value = list;

    emit('reorder', { oldIndex: from, newIndex: to });
}
</script>

<template>
    <AppDataTable
        table-key="admin-section-program-tes"
        :data="localSections"
        :columns="columns"
        :draggable="bisaDrag"
        title="Daftar Section"
        :searchable="false"
        :show-pagination="false"
        :show-page-size="false"
        :column-toggle="false"
        :persist-state="false"
        :page-size="ukuranHalaman"
        :page-size-options="[ukuranHalaman]"
        empty-message="Belum ada data section."
        @row-drop="onRowDrop"
    >
        <template #actions>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <span v-if="props.canManage && props.section.length" class="text-label-md text-text-muted">
                    {{ isEditingOrder ? 'Seret baris untuk mengubah urutan' : 'Aktifkan Edit urutan untuk mengatur posisi' }}
                </span>
                <BaseButton
                    v-if="props.canManage && props.section.length"
                    variant="secondary"
                    size="sm"
                    :disabled="props.reordering || !props.canReorder"
                    :title="props.canReorder ? undefined : 'Tidak bisa diubah karena program tes dipakai ujian aktif'"
                    @click="isEditingOrder = !isEditingOrder"
                >
                    <IconEdit :size="16" />
                    {{ isEditingOrder ? 'Selesai' : 'Edit urutan' }}
                </BaseButton>
                <BaseTambahData v-if="props.canCreate" @click="emit('create')" />
            </div>
        </template>
    </AppDataTable>
</template>
