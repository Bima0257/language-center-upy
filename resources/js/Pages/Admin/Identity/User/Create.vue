<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import BaseCheckboxGroup from '@/Components/Base/BaseCheckboxGroup.vue';
import BaseFieldLabel from '@/Components/Base/BaseFieldLabel.vue';
import BaseInput from '@/Components/Base/BaseInput.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';
import BaseToggle from '@/Components/Base/BaseToggle.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import {
    jenisPesertaIdentitasLabel,
    jenisPesertaLabel,
    jenisPesertaOptions,
    labelIdentitasPeserta,
} from '@/constants/jenisPeserta';
import { IconEdit, IconPlus, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    programStudis: { type: Array, default: () => [] },
});

const rows = ref([]);
const showModal = ref(false);
const editingIndex = ref(null);
const localError = ref('');
const bulkForm = useForm({ users: [] });

const roleOptions = computed(() => props.roles.map(role => ({ value: role.id, label: role.name })));
const roleNames = computed(() => Object.fromEntries(props.roles.map(role => [role.id, role.name])));
const isPesertaRow = computed(() => rowForm.value.role_ids.some(id => roleNames.value[id] === 'peserta'));
const rowJenisPeserta = computed(() => rowForm.value.jenis_peserta || 'mahasiswa');
const rowMemerlukanAkademik = computed(() => isPesertaRow.value && ['mahasiswa', 'dosen'].includes(rowJenisPeserta.value));
const rowMemerlukanInstitusi = computed(() => isPesertaRow.value && rowJenisPeserta.value === 'umum');
const rowLabelIdentitas = computed(() => labelIdentitasPeserta(rowJenisPeserta.value));

const rowForm = ref(emptyRow());

function emptyRow() {
    return {
        name: '',
        username: '',
        email: '',
        password: '',
        status: true,
        role_ids: [],
        jenis_peserta: 'mahasiswa',
        nomor_identitas: '',
        institusi: '',
        program_studi_id: null,
        tahun_mulai: '',
    };
}

function openCreate() {
    localError.value = '';
    editingIndex.value = null;
    rowForm.value = emptyRow();
    showModal.value = true;
}

function openEdit(index) {
    localError.value = '';
    editingIndex.value = index;
    rowForm.value = { ...rows.value[index], role_ids: [...rows.value[index].role_ids] };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function commitRow() {
    localError.value = '';

    if (!rowForm.value.name || !rowForm.value.username || !rowForm.value.email) {
        localError.value = 'Nama, username, dan email wajib diisi.';
        return;
    }

    if (rowForm.value.username.includes(' ')) {
        localError.value = 'Username tidak boleh mengandung spasi.';
        return;
    }

    if (!rowForm.value.role_ids.length) {
        localError.value = 'Minimal satu role harus dipilih.';
        return;
    }

    const duplicate = rows.value.some((row, index) =>
        index !== editingIndex.value
        && (row.username === rowForm.value.username || row.email === rowForm.value.email),
    );

    if (duplicate) {
        localError.value = 'Username atau email sudah ada di daftar.';
        return;
    }

    const jenisPeserta = rowForm.value.jenis_peserta || 'mahasiswa';
    const isPeserta = rowForm.value.role_ids.some(id => roleNames.value[id] === 'peserta');

    if (isPeserta && ['mahasiswa', 'dosen'].includes(jenisPeserta) && !rowForm.value.program_studi_id) {
        localError.value = 'Program studi wajib dipilih untuk peserta mahasiswa atau dosen.';
        return;
    }

    if (isPeserta && jenisPeserta === 'umum' && !rowForm.value.institusi) {
        localError.value = 'Institusi wajib diisi untuk peserta umum.';
        return;
    }

    if (editingIndex.value !== null) {
        rows.value.splice(editingIndex.value, 1, { ...rowForm.value });
    } else {
        rows.value.push({ ...rowForm.value });
    }

    closeModal();
}

function removeRow(index) {
    rows.value.splice(index, 1);
}

function submit() {
    if (!rows.value.length) {
        localError.value = 'Tambahkan minimal satu pengguna.';
        return;
    }

    bulkForm.users = rows.value;
    bulkForm.post(route('admin.identity.users.bulk.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Tambah Pengguna Massal" />
    <TataLetakDasbor
        title="Tambah Pengguna Massal"
        subtitle="Tambahkan hingga 10 pengguna sekaligus"
        :back-href="route('admin.identity.users.index', { tab: 'admin' })"
    >
        <BaseCard :padding="false">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/40 px-5 py-4">
                <div>
                    <h2 class="text-title-lg font-semibold text-text-heading">Daftar Pengguna ({{ rows.length }}/10)</h2>
                    <p class="text-body-md text-text-muted">Data disimpan setelah tombol simpan ditekan.</p>
                </div>
                <BaseButton size="sm" :disabled="rows.length >= 10" @click="openCreate">
                    <IconPlus :size="16" />
                    Tambah Baris
                </BaseButton>
            </div>

            <div v-if="rows.length === 0" class="px-6 py-16 text-center text-body-md text-text-muted">
                Belum ada pengguna. Tambahkan baris terlebih dahulu.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="bg-surface-container-low text-label-md font-semibold uppercase tracking-wider text-text-muted">
                        <tr>
                            <th class="px-4 py-3">No.</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        <tr v-for="(row, index) in rows" :key="`${row.username}-${index}`">
                            <td class="px-4 py-3 text-body-md text-text-body">{{ index + 1 }}</td>
                            <td class="px-4 py-3">
                                <p class="text-body-md font-medium text-text-heading">{{ row.name }}</p>
                                <p v-if="row.role_ids.some(id => roleNames[id] === 'peserta')" class="text-label-md text-text-muted">
                                    {{ jenisPesertaLabel[row.jenis_peserta] }}
                                    · {{ jenisPesertaIdentitasLabel[row.jenis_peserta] }}: {{ row.nomor_identitas || '-' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-body-md text-text-body">{{ row.username }}</td>
                            <td class="px-4 py-3 text-body-md text-text-body">{{ row.email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <BaseBadge v-for="roleId in row.role_ids" :key="roleId" variant="secondary">
                                        {{ roleNames[roleId] || roleId }}
                                    </BaseBadge>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <BaseBadge :variant="row.status ? 'success' : 'neutral'">
                                    {{ row.status ? 'Aktif' : 'Nonaktif' }}
                                </BaseBadge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <BaseButton variant="secondary" icon-only title="Ubah" aria-label="Ubah" @click="openEdit(index)">
                                        <IconEdit :size="16" />
                                    </BaseButton>
                                    <BaseButton variant="danger" icon-only title="Hapus" aria-label="Hapus" @click="removeRow(index)">
                                        <IconTrash :size="16" />
                                    </BaseButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-outline-variant/40 px-5 py-4">
                <p class="text-body-md text-text-muted">Maksimal 10 pengguna per pengiriman.</p>
                <div class="flex gap-2">
                    <BaseButton variant="secondary" size="sm" :href="route('admin.identity.users.index', { tab: 'admin' })">
                        Batal
                    </BaseButton>
                    <BaseButton size="sm" :disabled="bulkForm.processing || rows.length === 0" @click="submit">
                        {{ bulkForm.processing ? 'Menyimpan...' : `Simpan ${rows.length} Data` }}
                    </BaseButton>
                </div>
            </div>
        </BaseCard>

        <div v-if="localError" class="mt-3 rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">
            {{ localError }}
        </div>
        <div v-if="bulkForm.errors.users" class="mt-3 rounded-xl bg-error-container px-4 py-3 text-body-md text-on-error-container">
            {{ bulkForm.errors.users }}
        </div>

        <BaseModal :show="showModal" max-width="2xl" @close="closeModal">
            <BaseModalHeader>
                <div class="px-6 py-4 text-title-lg font-semibold text-text-heading">
                    {{ editingIndex === null ? "Tambah Pengguna" : "Ubah Pengguna" }}
                </div>
            </BaseModalHeader>
            <div class="space-y-4 p-6">
                <div>
                    <BaseFieldLabel required>Nama</BaseFieldLabel>
                    <BaseInput v-model="rowForm.name" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <BaseFieldLabel required>Username</BaseFieldLabel>
                        <BaseInput v-model="rowForm.username" />
                    </div>
                    <div>
                        <BaseFieldLabel required>Email</BaseFieldLabel>
                        <BaseInput v-model="rowForm.email" type="email" />
                    </div>
                </div>
                <div>
                    <BaseFieldLabel required>Password</BaseFieldLabel>
                    <BaseInput v-model="rowForm.password" type="password" />
                </div>
                <div>
                    <BaseFieldLabel required>Role</BaseFieldLabel>
                    <BaseCheckboxGroup v-model="rowForm.role_ids" :options="roleOptions" inline />
                </div>
                <template v-if="isPesertaRow">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <BaseFieldLabel required>Jenis Peserta</BaseFieldLabel>
                            <DropDown
                                v-model="rowForm.jenis_peserta"
                                :options="jenisPesertaOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Pilih jenis peserta"
                            />
                        </div>
                        <div>
                            <BaseFieldLabel required>{{ rowLabelIdentitas }}</BaseFieldLabel>
                            <BaseInput v-model="rowForm.nomor_identitas" />
                        </div>
                    </div>
                    <div v-if="rowMemerlukanInstitusi">
                        <BaseFieldLabel required>Institusi</BaseFieldLabel>
                        <BaseInput v-model="rowForm.institusi" placeholder="Nama institusi/instansi" />
                    </div>
                    <div v-if="rowMemerlukanAkademik">
                        <BaseFieldLabel required>Program Studi</BaseFieldLabel>
                        <DropDown
                            v-model="rowForm.program_studi_id"
                            :options="props.programStudis"
                            option-label="label"
                            option-value="value"
                            placeholder="Pilih program studi"
                            clearable
                        />
                    </div>
                    <div>
                        <BaseFieldLabel>Tahun Mulai</BaseFieldLabel>
                        <BaseInput v-model="rowForm.tahun_mulai" type="number" />
                    </div>
                </template>
                <BaseToggle v-model="rowForm.status" label="Status aktif" />
            </div>
            <BaseModalFooter>
                <div class="flex justify-end gap-2 border-t border-outline-variant/40 px-6 py-4">
                    <BaseButton variant="secondary" size="sm" @click="closeModal">Batal</BaseButton>
                    <BaseButton size="sm" @click="commitRow">Tambahkan</BaseButton>
                </div>
            </BaseModalFooter>
        </BaseModal>
    </TataLetakDasbor>
</template>
