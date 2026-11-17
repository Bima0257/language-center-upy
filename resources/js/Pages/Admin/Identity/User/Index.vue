<script setup>
import { computed, h, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import AppTabs from '@/Components/Ui/AppTabs.vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCheckboxGroup from '@/Components/Base/BaseCheckboxGroup.vue';
import BaseFieldLabel from '@/Components/Base/BaseFieldLabel.vue';
import BaseInput from '@/Components/Base/BaseInput.vue';
import BaseInputError from '@/Components/Base/BaseInputError.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';
import BaseToggle from '@/Components/Base/BaseToggle.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { useConfirm } from '@/Composables/useConfirm';
import {
    jenisPesertaIdentitasLabel,
    jenisPesertaLabel,
    jenisPesertaOptions,
    jenisPesertaVariant,
    labelIdentitasPeserta,
} from '@/constants/jenisPeserta';
import { IconEdit, IconPlus, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    users: { type: Object, required: true },
    userCounts: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
    programStudis: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const activeTab = computed(() => props.filters?.tab || 'admin');

const tabs = computed(() => [
    { value: 'admin', label: `Admin (${props.userCounts?.admin ?? 0})` },
    { value: 'peserta', label: `Peserta (${props.userCounts?.peserta ?? 0})` },
    { value: 'custom', label: `Custom (${props.userCounts?.custom ?? 0})` },
]);

const tableFilters = computed(() => [
    {
        key: 'f_role_names',
        label: 'Role',
        allOption: true,
        options: props.roles.map(role => ({ value: role.name, label: role.name })),
    },
    {
        key: 'f_status',
        label: 'Status',
        allOption: true,
        options: [
            { value: 'true', label: 'Aktif' },
            { value: 'false', label: 'Nonaktif' },
        ],
    },
    {
        key: 'f_jenis_peserta',
        label: 'Jenis Peserta',
        allOption: true,
        options: jenisPesertaOptions,
    },
    {
        key: 'f_program_studi_id',
        label: 'Program Studi',
        allOption: true,
        options: props.programStudis,
    },
]);

const showForm = ref(false);
const editingUser = ref(null);
const showRoles = ref(false);
const selectedRoles = ref([]);

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    status: true,
    role_ids: [],
    jenis_peserta: 'mahasiswa',
    nomor_identitas: '',
    institusi: '',
    program_studi_id: null,
    tahun_mulai: '',
});

const roleOptions = computed(() => props.roles.map(role => ({ value: role.id, label: role.name })));
const roleNames = computed(() => Object.fromEntries(props.roles.map(role => [role.id, role.name])));
const isPesertaRole = computed(() => form.role_ids.some(id => roleNames.value[id] === 'peserta'));
const formJenisPeserta = computed(() => form.jenis_peserta || 'mahasiswa');
const memerlukanAkademik = computed(() => isPesertaRole.value && ['mahasiswa', 'dosen'].includes(formJenisPeserta.value));
const memerlukanInstitusi = computed(() => isPesertaRole.value && formJenisPeserta.value === 'umum');
const labelIdentitas = computed(() => labelIdentitasPeserta(formJenisPeserta.value));

function switchTab(tab) {
    router.get(route('admin.identity.users.index'), {
        tab,
        per_page: props.filters?.per_page || 10,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function openCreate() {
    router.visit(route('admin.identity.users.create'));
}

function openEdit(user) {
    editingUser.value = user;
    form.clearErrors();
    form.name = user.name || '';
    form.username = user.username || '';
    form.email = user.email || '';
    form.password = '';
    form.password_confirmation = '';
    form.status = Boolean(user.status);
    form.role_ids = (user.roles || []).map(role => role.id);
    form.jenis_peserta = user.jenis_peserta || 'mahasiswa';
    form.nomor_identitas = user.nomor_identitas || '';
    form.institusi = user.institusi || '';
    form.program_studi_id = user.program_studi_id || null;
    form.tahun_mulai = user.tahun_mulai || '';
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingUser.value = null;
    form.clearErrors();
}

function submitForm() {
    form.put(route('admin.identity.users.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: closeForm,
    });
}

function openRoleModal(user) {
    selectedRoles.value = user.roles || [];
    showRoles.value = true;
}

async function destroyUser(user) {
    if (!await confirm.confirm(`Hapus pengguna "${user.name}"?`)) return;

    router.delete(route('admin.identity.users.destroy', user.id), { preserveScroll: true });
}

const columns = [
    {
        accessorKey: 'name',
        header: 'Nama',
        cell: info => h('div', [
            h('p', { class: 'font-semibold text-text-heading' }, info.getValue()),
            h('p', { class: 'text-label-md text-text-muted' }, `@${info.row.original.username || '-'}`),
        ]),
    },
    {
        accessorKey: 'email',
        header: 'Email',
        cell: info => info.getValue() || '-',
    },
    {
        id: 'identitas',
        header: 'Identitas Peserta',
        enableSorting: false,
        cell: info => {
            const user = info.row.original;
            const jenis = user.jenis_peserta;
            const isPeserta = (user.role_names || []).includes('peserta');

            if (!isPeserta || !jenis) return '-';

            const labelIdentitas = jenisPesertaIdentitasLabel[jenis] || 'Identitas';

            return h('div', { class: 'flex flex-wrap items-center gap-1' }, [
                h(BaseBadge, { variant: jenisPesertaVariant[jenis] || 'neutral' }, () => jenisPesertaLabel[jenis] || jenis),
                h('span', { class: 'text-label-md text-text-muted' }, `${labelIdentitas}: ${user.nomor_identitas || '-'}`),
            ]);
        },
    },
    {
        accessorKey: 'roles',
        header: 'Role',
        enableSorting: false,
        cell: info => {
            const roles = info.row.original.roles || [];
            const visible = roles.slice(0, 2);

            return h('div', { class: 'flex flex-wrap items-center gap-1' }, [
                ...visible.map(role => h(BaseBadge, { variant: 'secondary' }, () => role.name)),
                roles.length > 2
                    ? h(
                        BaseButton,
                        {
                            size: 'xs',
                            variant: 'ghost',
                            onClick: () => openRoleModal(info.row.original),
                        },
                        () => `+${roles.length - 2} lainnya`,
                    )
                    : null,
            ]);
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        meta: { align: 'center' },
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() ? 'success' : 'neutral' },
            () => (info.getValue() ? 'Aktif' : 'Nonaktif'),
        ),
    },
    {
        accessorKey: 'last_login',
        header: 'Terakhir Login',
        cell: info => info.getValue() || '-',
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h('div', { class: 'flex items-center justify-center gap-1' }, [
            h(
                BaseButton,
                {
                    variant: 'secondary',
                    iconOnly: true,
                    title: 'Ubah',
                    'aria-label': 'Ubah',
                    onClick: () => openEdit(info.row.original),
                },
                () => h(IconEdit, { size: 16 }),
            ),
            h(
                BaseButton,
                {
                    variant: 'danger',
                    iconOnly: true,
                    title: 'Hapus',
                    'aria-label': 'Hapus',
                    onClick: () => destroyUser(info.row.original),
                },
                () => h(IconTrash, { size: 16 }),
            ),
        ]),
    },
];
</script>

<template>
    <Head title="Manajemen Pengguna" />
    <TataLetakDasbor title="Manajemen Pengguna" subtitle="Kelola akun, role, dan status pengguna">
        <div class="space-y-4">
            <AppTabs :tabs="tabs" :model-value="activeTab" @update:model-value="switchTab" />

            <AppDataTable
                :key="activeTab"
                :table-key="`identity-user-table-${activeTab}`"
                :data="props.users.data"
                :columns="columns"
                title="Daftar Pengguna"
                searchable
                search-placeholder="Cari nama, username, atau email..."
                server-side
                :server-total-rows="props.users.total"
                :reload-only="['users', 'userCounts']"
                :filters="tableFilters"
                :page-size="10"
                :page-size-options="[10, 25, 50, 100]"
            >
                <template #actions>
                    <BaseButton size="sm" @click="openCreate">
                        <IconPlus :size="16" />
                        Tambah Pengguna
                    </BaseButton>
                </template>
            </AppDataTable>
        </div>

        <BaseModal :show="showForm" max-width="2xl" @close="closeForm">
            <BaseModalHeader>
                <div class="flex items-center justify-between gap-3 px-6 py-4">
                    <h2 class="text-title-lg font-semibold text-text-heading">Ubah Pengguna</h2>
                    <BaseButton variant="ghost" size="sm" @click="closeForm">Tutup</BaseButton>
                </div>
            </BaseModalHeader>

            <form class="space-y-4 p-6" @submit.prevent="submitForm">
                <div>
                    <BaseFieldLabel for="user-name" required>Nama</BaseFieldLabel>
                    <BaseInput id="user-name" v-model="form.name" :error="Boolean(form.errors.name)" />
                    <BaseInputError :message="form.errors.name" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <BaseFieldLabel for="user-username" required>Username</BaseFieldLabel>
                        <BaseInput id="user-username" v-model="form.username" :error="Boolean(form.errors.username)" />
                        <BaseInputError :message="form.errors.username" />
                    </div>
                    <div>
                        <BaseFieldLabel for="user-email" required>Email</BaseFieldLabel>
                        <BaseInput id="user-email" v-model="form.email" type="email" :error="Boolean(form.errors.email)" />
                        <BaseInputError :message="form.errors.email" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <BaseFieldLabel for="user-password">Password baru</BaseFieldLabel>
                        <BaseInput id="user-password" v-model="form.password" type="password" :error="Boolean(form.errors.password)" />
                        <BaseInputError :message="form.errors.password" />
                    </div>
                    <div>
                        <BaseFieldLabel for="user-password-confirmation">Konfirmasi password</BaseFieldLabel>
                        <BaseInput id="user-password-confirmation" v-model="form.password_confirmation" type="password" />
                    </div>
                </div>
                <div>
                    <BaseFieldLabel required>Role</BaseFieldLabel>
                    <BaseCheckboxGroup v-model="form.role_ids" :options="roleOptions" inline />
                    <BaseInputError :message="form.errors.role_ids" />
                </div>
                <template v-if="isPesertaRole">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <BaseFieldLabel required>Jenis Peserta</BaseFieldLabel>
                            <DropDown
                                v-model="form.jenis_peserta"
                                :options="jenisPesertaOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Pilih jenis peserta"
                            />
                            <BaseInputError :message="form.errors.jenis_peserta" />
                        </div>
                        <div>
                            <BaseFieldLabel required>{{ labelIdentitas }}</BaseFieldLabel>
                            <BaseInput v-model="form.nomor_identitas" />
                            <BaseInputError :message="form.errors.nomor_identitas" />
                        </div>
                    </div>
                    <div v-if="memerlukanInstitusi">
                        <BaseFieldLabel required>Institusi</BaseFieldLabel>
                        <BaseInput v-model="form.institusi" placeholder="Nama institusi/instansi" />
                        <BaseInputError :message="form.errors.institusi" />
                    </div>
                    <div v-if="memerlukanAkademik">
                        <BaseFieldLabel required>Program Studi</BaseFieldLabel>
                        <DropDown
                            v-model="form.program_studi_id"
                            :options="props.programStudis"
                            option-label="label"
                            option-value="value"
                            placeholder="Pilih program studi"
                            clearable
                        />
                        <BaseInputError :message="form.errors.program_studi_id" />
                    </div>
                    <div>
                        <BaseFieldLabel>Tahun Mulai</BaseFieldLabel>
                        <BaseInput v-model="form.tahun_mulai" type="number" />
                        <BaseInputError :message="form.errors.tahun_mulai" />
                    </div>
                </template>
                <BaseToggle v-model="form.status" label="Status aktif" />
            </form>

            <BaseModalFooter>
                <div class="flex justify-end gap-2 border-t border-outline-variant/40 px-6 py-4">
                    <BaseButton variant="secondary" size="sm" @click="closeForm">Batal</BaseButton>
                    <BaseButton size="sm" :disabled="form.processing" @click="submitForm">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </BaseButton>
                </div>
            </BaseModalFooter>
        </BaseModal>

        <BaseModal :show="showRoles" max-width="md" @close="showRoles = false">
            <BaseModalHeader>
                <div class="px-6 py-4 text-title-lg font-semibold text-text-heading">Daftar Role</div>
            </BaseModalHeader>
            <div class="flex flex-wrap gap-2 p-6">
                <BaseBadge v-for="role in selectedRoles" :key="role.id" variant="secondary">
                    {{ role.name }}
                </BaseBadge>
            </div>
        </BaseModal>
    </TataLetakDasbor>
</template>
