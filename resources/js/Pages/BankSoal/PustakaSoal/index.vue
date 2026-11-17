<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import TataLetakDasbor from "@/Layouts/TataLetakDasbor.vue";
import UploadProgressBar from "@/Components/Shared/UploadProgressBar.vue";
import EmptyState from "@/Components/Shared/EmptyState.vue";
import Pagination from "@/Components/Shared/Pagination.vue";
import UserGuide from "@/Components/Shared/UserGuide.vue";
import SectionFormModal from "@/Components/Ujian/SectionFormModal.vue";
import PartSoalFormModal from "@/Pages/Admin/DataInduk/PartSoal/Components/PartSoalFormModal.vue";
import KelompokSoalPustaka from "@/Pages/BankSoal/PustakaSoal/Components/KelompokSoalPustaka.vue";
import EditMateriSoalModal from "@/Pages/BankSoal/PustakaSoal/Components/EditMateriSoalModal.vue";
import TambahSoalMateriModal from "@/Pages/BankSoal/PustakaSoal/Components/TambahSoalMateriModal.vue";
import RingkasanProgramTesPustaka from "@/Pages/BankSoal/PustakaSoal/Components/RingkasanProgramTesPustaka.vue";
import RingkasanBankSoalPustaka from "@/Pages/BankSoal/PustakaSoal/Components/RingkasanBankSoalPustaka.vue";
import { IconBook, IconEdit, IconPlus, IconSearch, IconTrash, IconX } from "@tabler/icons-vue";
import { ref, computed } from "vue";
import { useConfirm } from "@/Composables/useConfirm";
import { useUploadProgress } from "@/Composables/useUploadProgress";

const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    soal: { type: Object, default: () => ({ data: [] }) },
    programTes: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
    bankSoalTerpilih: { type: Object, default: null },
    bankTerkunci: { type: Boolean, default: false },
    opsiSection: { type: Array, default: () => [] },
    partSoal: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
    filter: { type: Object, default: () => ({}) },
    bankSoalTerpilihId: { type: [String, null], default: null },
    programTesTerpilihId: { type: [String, null], default: null },
});

const bankSoalTerpilihId = ref(props.bankSoalTerpilihId || props.filter.bank_soal_id || "");
const programTesTerpilihId = ref(props.programTesTerpilihId || props.filter.program_tes_id || "");
const partSoalDariFilter = props.partSoal.find(
    (part) => String(part.id) === String(props.filter.part_soal_id) &&
        String(part.bank_soal_id) === String(bankSoalTerpilihId.value),
);
const sectionTerpilih = ref(
    props.filter.section || partSoalDariFilter?.section_id || props.opsiSection[0]?.value || "",
);
const partSoalPertama = props.partSoal.find(
    (part) => String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
        String(part.section_id) === String(sectionTerpilih.value),
);
const partSoalTerpilihId = ref(props.filter.part_soal_id || partSoalPertama?.id || "");
const search = ref("");

const createParams = computed(() => {
    const p = {};
    if (bankSoalTerpilihId.value) p.bank_soal_id = bankSoalTerpilihId.value;
    if (sectionTerpilih.value) p.section = sectionTerpilih.value;
    if (partSoalTerpilihId.value) p.part_soal_id = partSoalTerpilihId.value;
    return p;
});

const ringkasanProgramTes = computed(() => {
    const groups = new Map(props.programTes.map((programTes) => [String(programTes.id), {
        id: programTes.id,
        kode: programTes.kode,
        nama: programTes.nama,
        jumlahBankSoal: 0,
        jumlahSoal: 0,
    }]));

    props.bankSoal.forEach((bank) => {
        const group = groups.get(String(bank.program_tes_id));
        if (!group) return;

        group.jumlahBankSoal += 1;
        group.jumlahSoal += Number(bank.soal_count ?? 0);
    });

    return Array.from(groups.values()).sort((left, right) => left.nama.localeCompare(right.nama, 'id'));
});

const ringkasanBankSoal = computed(() => props.bankSoal.map((bank) => ({
    id: bank.id,
    nama: bank.nama,
    jumlahSoal: Number(bank.soal_count ?? 0),
})));

const bankSoalTerpilih = computed(() => props.bankSoalTerpilih || props.bankSoal.find(
    (bank) => String(bank.id) === String(bankSoalTerpilihId.value),
) || null);

const bankIsLocked = computed(() =>
    props.bankTerkunci || Number(bankSoalTerpilih.value?.part_ujian_count || 0) > 0,
);

function programTesUrl(programTesId) {
    return `${route('pustaka-soal.daftar')}?program_tes_id=${encodeURIComponent(programTesId)}`;
}

function bankSoalUrl(bankSoalId) {
    const params = new URLSearchParams({ bank_soal_id: String(bankSoalId) });

    if (programTesTerpilihId.value) {
        params.set('program_tes_id', String(programTesTerpilihId.value));
    }

    return `${route('pustaka-soal.daftar')}?${params.toString()}`;
}

const backToBankSoalUrl = computed(() => programTesTerpilihId.value
    ? programTesUrl(programTesTerpilihId.value)
    : route('pustaka-soal.daftar'));

const pageBackUrl = computed(() => {
    if (bankSoalTerpilihId.value) return backToBankSoalUrl.value;
    if (programTesTerpilihId.value) return route('pustaka-soal.daftar');
    return null;
});

const tabsSection = computed(() => props.opsiSection.map((section) => ({
    value: String(section.value),
    label: section.label,
})));

const partSoalUntukSection = computed(() => props.partSoal
    .filter((part) =>
        String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
        String(part.section_id) === String(sectionTerpilih.value),
    )
    .sort((left, right) => (left.urutan ?? 0) - (right.urutan ?? 0)));

function partTabLabel(part) {
    const sectionName = props.opsiSection.find(
        (section) => String(section.value) === String(part.section_id),
    )?.label?.trim();

    if (!sectionName || !part.nama.toLowerCase().startsWith(sectionName.toLowerCase())) {
        return part.nama;
    }

    const tanpaSection = part.nama.slice(sectionName.length).trim();

    return /^part\b/i.test(tanpaSection) ? tanpaSection : part.nama;
}

const tabsPartSoal = computed(() => partSoalUntukSection.value.map((part) => ({
    value: String(part.id),
    label: partTabLabel(part),
})));

const canManageSection = computed(() =>
    [
        'admin.data-induk.section.simpan',
        'admin.data-induk.section.perbarui',
        'admin.data-induk.part-soal.simpan',
        'admin.data-induk.part-soal.perbarui',
    ].some(permission => (page.props.auth?.permissions || []).includes(permission))
    && !bankIsLocked.value,
);
const canManagePartSoal = canManageSection;

const sectionTerpilihData = computed(() => props.opsiSection.find(
    (section) => String(section.value) === String(sectionTerpilih.value),
) || null);

const sectionActions = computed(() => [
    { value: "edit", label: "Ubah section", icon: IconEdit },
    { value: "delete", label: "Hapus section", icon: IconTrash, variant: "danger" },
]);

const partSoalTerpilihData = computed(() => partSoalUntukSection.value.find(
    (part) => String(part.id) === String(partSoalTerpilihId.value),
) || null);

const partSoalActions = computed(() => [
    { value: "edit", label: "Ubah part soal", icon: IconEdit },
    { value: "delete", label: "Hapus part soal", icon: IconTrash, variant: "danger" },
]);

const programTesSection = computed(() => {
    const programTesId = bankSoalTerpilih.value?.program_tes_id || programTesTerpilihId.value;

    return props.programTes.filter((programTes) => String(programTes.id) === String(programTesId));
});

const canCreateSoal = computed(() => Boolean(
    !bankIsLocked.value &&
    tabsSection.value.length &&
    tabsPartSoal.value.length &&
    sectionTerpilih.value &&
    partSoalTerpilihId.value,
));

function onSectionChange(value) {
    sectionTerpilih.value = String(value);
    partSoalTerpilihId.value = String(partSoalUntukSection.value[0]?.id || '');
    applyFilters();
}

function onPartSoalChange(value) {
    partSoalTerpilihId.value = String(value);
    applyFilters();
}

function policyForSection(sectionId) {
    return props.kebijakanMateriSoal.find((policy) =>
        String(policy.section_id) === String(sectionId),
    ) || null;
}

function openCreateSection() {
    if (bankIsLocked.value) return;

    creatingSection.value = true;
    sectionYangDiedit.value = null;
    sectionForm.clearErrors();
    sectionForm.reset();
    sectionForm.bank_soal_konteks_id = bankSoalTerpilihId.value;
    sectionForm.program_tes_id = bankSoalTerpilih.value?.program_tes_id || programTesTerpilihId.value;
    showSectionModal.value = true;
}

function openEditSection() {
    if (bankIsLocked.value) return;

    const section = sectionTerpilihData.value;
    if (!section) return;

    creatingSection.value = false;
    sectionYangDiedit.value = section;
    editSectionForm.clearErrors();
    editSectionForm.reset();
    editSectionForm.bank_soal_konteks_id = bankSoalTerpilihId.value;
    editSectionForm.program_tes_id = bankSoalTerpilih.value?.program_tes_id || programTesTerpilihId.value;
    editSectionForm.kode = section.kode || "";
    editSectionForm.nama = section.label || "";
    editSectionForm.deskripsi = section.deskripsi || "";
    editSectionForm.aktif = section.aktif !== false;

    const policy = policyForSection(section.value);
    editSectionForm.ketentuan_materi = policy?.ketentuan_materi || "";
    editSectionForm.aturan_media = {
        text: policy?.aturan_media?.text || "",
        audio: policy?.aturan_media?.audio || "",
        image: policy?.aturan_media?.image || "",
    };
    showSectionModal.value = true;
}

function closeSectionModal() {
    showSectionModal.value = false;
    sectionYangDiedit.value = null;
    creatingSection.value = false;
    sectionForm.reset();
    editSectionForm.reset();
}

function syncSectionSelection(pageResponse) {
    const nextSectionId = pageResponse.props.filter?.section
        || pageResponse.props.opsiSection?.[0]?.value
        || "";

    sectionTerpilih.value = String(nextSectionId);
    partSoalTerpilihId.value = String(pageResponse.props.partSoal?.find((part) =>
        String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
        String(part.section_id) === String(nextSectionId),
    )?.id || "");
}

function saveSection() {
    if (bankIsLocked.value) return;

    sectionForm.post(route("admin.data-induk.section.simpan"), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closeSectionModal();
            syncSectionSelection(pageResponse);
        },
    });
}

function updateSection() {
    if (!sectionYangDiedit.value || bankIsLocked.value) return;

    editSectionForm.put(route(
        "admin.data-induk.section.perbarui",
        sectionYangDiedit.value.value,
    ), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closeSectionModal();
            syncSectionSelection(pageResponse);
        },
    });
}

async function deleteSection() {
    if (bankIsLocked.value) return;

    const section = sectionTerpilihData.value;
    if (!section) return;

    if (!(await confirm.confirm(`Hapus section "${section.label}"?`))) return;

    router.delete(route("admin.data-induk.section.hapus", section.value), {
        data: { bank_soal_konteks_id: bankSoalTerpilihId.value },
        preserveScroll: true,
        onSuccess: syncSectionSelection,
    });
}

function handleSectionAction(action) {
    if (action === "edit") {
        openEditSection();
        return;
    }

    if (action === "delete") {
        deleteSection();
    }
}

function openCreatePartSoal() {
    if (bankIsLocked.value) return;

    creatingPartSoal.value = true;
    partSoalYangDiedit.value = null;
    partSoalForm.clearErrors();
    partSoalForm.reset();
    partSoalForm.bank_soal_konteks_id = bankSoalTerpilihId.value;
    partSoalForm.bank_soal_id = bankSoalTerpilihId.value;
    partSoalForm.section_id = sectionTerpilih.value;
    showPartSoalModal.value = true;
}

function openEditPartSoal() {
    if (bankIsLocked.value) return;

    const partSoal = partSoalTerpilihData.value;
    if (!partSoal) return;

    creatingPartSoal.value = false;
    partSoalYangDiedit.value = partSoal;
    editPartSoalForm.clearErrors();
    editPartSoalForm.reset();
    editPartSoalForm.bank_soal_konteks_id = bankSoalTerpilihId.value;
    editPartSoalForm.bank_soal_id = String(partSoal.bank_soal_id);
    editPartSoalForm.section_id = String(partSoal.section_id);
    editPartSoalForm.nama = partSoal.nama;
    editPartSoalForm.petunjuk = partSoal.petunjuk || "";
    editPartSoalForm.aktif = partSoal.aktif !== false;
    showPartSoalModal.value = true;
}

function closePartSoalModal() {
    showPartSoalModal.value = false;
    partSoalYangDiedit.value = null;
    creatingPartSoal.value = false;
    partSoalForm.reset();
    editPartSoalForm.reset();
}

function syncPartSoalSelection(pageResponse) {
    const nextPartId = pageResponse.props.filter?.part_soal_id
        || pageResponse.props.partSoal?.find((part) =>
            String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
            String(part.section_id) === String(sectionTerpilih.value),
        )?.id
        || "";

    partSoalTerpilihId.value = String(nextPartId);
}

function savePartSoal() {
    if (bankIsLocked.value) return;

    partSoalForm.post(route("admin.data-induk.part-soal.simpan"), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closePartSoalModal();
            syncPartSoalSelection(pageResponse);
        },
    });
}

function updatePartSoal() {
    if (!partSoalYangDiedit.value || bankIsLocked.value) return;

    editPartSoalForm.put(route(
        "admin.data-induk.part-soal.perbarui",
        partSoalYangDiedit.value.id,
    ), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closePartSoalModal();
            syncPartSoalSelection(pageResponse);
        },
    });
}

async function deletePartSoal() {
    if (bankIsLocked.value) return;

    const partSoal = partSoalTerpilihData.value;
    if (!partSoal) return;

    if (!(await confirm.confirm(`Hapus part soal "${partSoal.nama}"?`))) return;

    router.delete(route("admin.data-induk.part-soal.hapus", partSoal.id), {
        data: { bank_soal_konteks_id: bankSoalTerpilihId.value },
        preserveScroll: true,
        onSuccess: syncPartSoalSelection,
    });
}

function handlePartSoalAction(action) {
    if (action === "edit") {
        openEditPartSoal();
        return;
    }

    if (action === "delete") {
        deletePartSoal();
    }
}

const expandedPassages = ref({});
const editingPassageId = ref(null);
const addingPassageId = ref(null);

const editingMateriSoal = computed(() => {
    if (!editingPassageId.value) return null;

    return props.soal.data
        .map((question) => question.materi_soal)
        .find((materiSoal) => materiSoal && String(materiSoal.id) === String(editingPassageId.value)) || null;
});

const materiSoalForm = useForm({
    judul: "",
    isi_teks: "",
    berkas_audio: null,
    berkas_gambar: null,
});

const quickQuestionForm = useForm({
    materi_soal_id: null,
    bank_soal_id: "",
    section_id: "",
    part_soal_id: "",
    teks_soal: "",
    pilihan_a: "",
    pilihan_b: "",
    pilihan_c: "",
    pilihan_d: "",
    kunci_jawaban: "",
});

const sectionForm = useForm({
    bank_soal_konteks_id: "",
    program_tes_id: "",
    kode: "",
    nama: "",
    deskripsi: "",
    aktif: true,
    ketentuan_materi: "",
    aturan_media: { text: "", audio: "", image: "" },
});

const editSectionForm = useForm({
    bank_soal_konteks_id: "",
    program_tes_id: "",
    kode: "",
    nama: "",
    deskripsi: "",
    aktif: true,
    ketentuan_materi: "",
    aturan_media: { text: "", audio: "", image: "" },
});

const partSoalForm = useForm({
    bank_soal_konteks_id: "",
    bank_soal_id: "",
    section_id: "",
    nama: "",
    petunjuk: "",
    aktif: true,
});

const editPartSoalForm = useForm({
    bank_soal_konteks_id: "",
    bank_soal_id: "",
    section_id: "",
    nama: "",
    petunjuk: "",
    aktif: true,
});

const showSectionModal = ref(false);
const creatingSection = ref(false);
const sectionYangDiedit = ref(null);
const modalSectionForm = computed(() => creatingSection.value
    ? sectionForm
    : editSectionForm);
const showPartSoalModal = ref(false);
const creatingPartSoal = ref(false);
const partSoalYangDiedit = ref(null);
const modalPartSoalForm = computed(() => creatingPartSoal.value
    ? partSoalForm
    : editPartSoalForm);

const optionKeys = ["A", "B", "C", "D"];

function materialForSection(bankId, sectionId) {
    return policyForBankAndSection(bankId, sectionId)?.materi || null;
}

function policyForBankAndSection(bankId, sectionId) {
    const bank = props.bankSoal.find((item) => String(item.id) === String(bankId));

    return props.kebijakanMateriSoal.find(p =>
        String(p.program_tes_id) === String(bank?.program_tes_id) &&
        String(p.section_id) === String(sectionId),
    ) || null;
}

const editingMateriPolicy = computed(() => policyForBankAndSection(
    bankSoalTerpilihId.value,
    sectionTerpilih.value,
));

function namaSectionDariId(sectionId) {
    return props.opsiSection.find(s => String(s.value) === String(sectionId))?.label || '';
}

const quickIsAudio = computed(
    () => materialForSection(quickQuestionForm.bank_soal_id, quickQuestionForm.section_id) === "audio",
);

function materiSoalTypeLabel(materiSoal) {
    if (!materiSoal) return "Teks";
    if (materiSoal.memiliki_audio) return "Audio";
    if (materiSoal.memiliki_gambar) return "Gambar";
    return "Teks";
}

function isPassageExpanded(id) {
    return !!expandedPassages.value[id];
}

function togglePassage(id) {
    expandedPassages.value[id] = !expandedPassages.value[id];
}

function startEditPassage(materiSoal) {
    if (bankIsLocked.value) return;

    editingPassageId.value = materiSoal.id;
    materiSoalForm.clearErrors();
    materiSoalForm.reset();
    materiSoalForm.judul = materiSoal.judul;
    materiSoalForm.isi_teks = materiSoal.isi_teks || "";
}

function cancelEditPassage() {
    editingPassageId.value = null;
    materiSoalForm.reset();
}

function savePassage() {
    if (!editingMateriSoal.value || bankIsLocked.value) return;

    materiSoalForm.put(route("pustaka-soal.materi-soal.perbarui", editingMateriSoal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingPassageId.value = null;
            materiSoalForm.reset();
            router.reload({
                only: ["soal"],
                preserveState: true,
                preserveScroll: true,
            });
        },
    });
}

async function removePassageMedia(materiSoal, jenisMedia) {
    if (bankIsLocked.value) return;

    const label = jenisMedia === 'audio' ? 'audio' : 'gambar';
    if (!(await confirm.confirm(`Hapus ${label} dari materi soal "${materiSoal.judul}"?`))) return;

    router.delete(route('pustaka-soal.materi-soal.media.hapus', [materiSoal.id, jenisMedia]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => router.reload({ only: ['soal'], preserveState: true, preserveScroll: true }),
    });
}

const { showUploadProgress, uploadLabel, mediaType } = useUploadProgress(materiSoalForm);

function openQuickAdd(materiSoal) {
    if (bankIsLocked.value) return;

    addingPassageId.value = materiSoal.id;
    quickQuestionForm.clearErrors();
    quickQuestionForm.reset();
    quickQuestionForm.materi_soal_id = materiSoal.id;
    quickQuestionForm.bank_soal_id = bankSoalTerpilihId.value;
    quickQuestionForm.section_id = sectionTerpilih.value;
    quickQuestionForm.part_soal_id = partSoalTerpilihId.value;
}

function closeQuickAdd() {
    addingPassageId.value = null;
    quickQuestionForm.reset();
}

function saveQuickQuestion() {
    if (bankIsLocked.value) return;

    quickQuestionForm
        .transform((data) => ({
            materi_soal_id: data.materi_soal_id,
            bank_soal_id: data.bank_soal_id,
            soal: [
                {
                    section_id: data.section_id,
                    part_soal_id: data.part_soal_id,
                    teks_soal: data.teks_soal,
                    pilihan_a: data.pilihan_a,
                    pilihan_b: data.pilihan_b,
                    pilihan_c: data.pilihan_c,
                    pilihan_d: data.pilihan_d,
                    kunci_jawaban: data.kunci_jawaban,
                },
            ],
        }))
        .post(route("pustaka-soal.simpan"), {
            preserveScroll: true,
            onSuccess: () => {
                addingPassageId.value = null;
                quickQuestionForm.reset();
                router.reload({
                    only: ["soal"],
                    preserveState: true,
                    preserveScroll: true,
                });
            },
        });
}

async function deletePassage(materiSoal) {
    if (bankIsLocked.value) return;

    if (
        !(await confirm.confirm(
            `Hapus materi soal "${materiSoal.judul}"?`,
        ))
    )
        return;
    router.delete(route("pustaka-soal.materi-soal.hapus", materiSoal.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

const kataKunciPencarian = computed(() => search.value.trim().toLowerCase());

const soalTampil = computed(() => {
    const daftarSoal = props.soal.data ?? [];

    if (!kataKunciPencarian.value) return daftarSoal;

    return daftarSoal.filter((question) =>
        [question.teks_soal, question.materi_soal?.judul]
            .filter(Boolean)
            .some((teks) => String(teks).toLowerCase().includes(kataKunciPencarian.value)),
    );
});

const sedangMencari = computed(() => kataKunciPencarian.value !== "");

const kelompokSoalPerSection = computed(() => {
    const sectionMap = new Map();

    for (const q of soalTampil.value) {
        const sid = q.section_id || "none";
        if (!sectionMap.has(sid)) {
            sectionMap.set(sid, { section_id: sid, partSoal: new Map() });
        }
        const bucket = sectionMap.get(sid);

        const pid = q.part_soal_id || "none";
        if (!bucket.partSoal.has(pid)) {
            bucket.partSoal.set(pid, {
                partSoal: q.part_soal || null,
                materiSoalGroups: new Map(),
                standalone: [],
            });
        }
        const partSoalBucket = bucket.partSoal.get(pid);

        if (q.materi_soal) {
            if (!partSoalBucket.materiSoalGroups.has(q.materi_soal_id)) {
                partSoalBucket.materiSoalGroups.set(q.materi_soal_id, {
                    materiSoal: q.materi_soal,
                    soal: [],
                });
            }
            partSoalBucket.materiSoalGroups.get(q.materi_soal_id).soal.push(q);
        } else {
            partSoalBucket.standalone.push(q);
        }
    }

    return Array.from(sectionMap.values())
        .map((s) => ({
            section_id: s.section_id,
            label: namaSectionDariId(s.section_id),
            partSoal: Array.from(s.partSoal.values())
                .map((p) => ({
                    partSoal: p.partSoal,
                    groups: Array.from(p.materiSoalGroups.values()).map((group) => ({
                        ...group,
                        soal: [...group.soal].sort(
                            (left, right) => (left.urutan ?? 0) - (right.urutan ?? 0),
                        ),
                    })),
                    standalone: p.standalone,
                }))
                .sort((a, b) => (a.partSoal?.urutan ?? 99) - (b.partSoal?.urutan ?? 99)),
        }))
        .sort((a, b) => a.label.localeCompare(b.label));
});

function applyFilters() {
    const p = {};
    if (programTesTerpilihId.value) p.program_tes_id = programTesTerpilihId.value;
    if (sectionTerpilih.value) p.section = sectionTerpilih.value;
    if (bankSoalTerpilihId.value) p.bank_soal_id = bankSoalTerpilihId.value;
    if (partSoalTerpilihId.value) p.part_soal_id = partSoalTerpilihId.value;
    router.get(route("pustaka-soal.daftar"), p, { preserveState: true });
}

async function deleteQuestion(id, text) {
    if (bankIsLocked.value) return;

    if (!(await confirm.confirm(`Hapus soal: "${text.substring(0, 50)}..."?`)))
        return;
    router.delete(route("pustaka-soal.hapus", id), {
        preserveScroll: true,
    });
}

</script>

<template>
    <Head title="Bank Soal" />
    <TataLetakDasbor
        :title="bankSoalTerpilih?.nama || 'Bank Soal'"
        subtitle="Kelola soal dan materi per bank soal"
        :back-href="pageBackUrl"
    >
        <template #breadcrumb-actions>
            <UserGuide
                title="Panduan Bank Soal"
                :steps="[
                    { judul: 'Pilih Program Tes', desc: 'Buka program tes untuk melihat bank soal yang tersedia.' },
                    { judul: 'Pilih Bank Soal', desc: 'Buka bank soal untuk menampilkan daftar soal.' },
                    { judul: 'Kelola & Filter Soal', desc: 'Gunakan filter Section dan Part Soal untuk menemukan soal.' },
                ]"
                :rules="[
                    'Soal dikelompokkan per section → part soal → materi soal.',
                ]"
            />
        </template>

        <template v-if="!bankSoalTerpilihId">
            <RingkasanProgramTesPustaka
                v-if="!programTesTerpilihId"
                :ringkasan-program-tes="ringkasanProgramTes"
                :detail-url="programTesUrl"
            />
            <RingkasanBankSoalPustaka
                v-else
                :ringkasan-bank-soal="ringkasanBankSoal"
                :detail-url="bankSoalUrl"
            />
        </template>

        <template v-else>
            <div class="relative mb-6">
                <div
                    v-if="tabsSection.length || canManageSection"
                    class="flex min-w-0 items-end gap-1"
                >
                    <div v-if="tabsSection.length" class="min-w-0 flex-1">
                        <BaseTabsChrome
                            :model-value="sectionTerpilih"
                            :items="tabsSection"
                            :active-action="canManageSection && sectionTerpilihData !== null"
                            aria-label="Filter section soal"
                            class="relative z-10 -mb-px px-5"
                            @update:model-value="onSectionChange"
                        >
                            <template #active-action>
                                <BaseActionMenu
                                    :items="sectionActions"
                                    icon-only
                                    teleport-menu
                                    compact
                                    class="-ml-1"
                                    aria-label="Aksi section aktif"
                                    @select="handleSectionAction"
                                />
                            </template>
                            <template #after>
                                <button
                                    v-if="canManageSection"
                                    type="button"
                                    class="mb-1 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                                    aria-label="Tambah section"
                                    title="Tambah section"
                                    @click="openCreateSection"
                                >
                                    <IconPlus :size="19" />
                                </button>
                            </template>
                        </BaseTabsChrome>
                    </div>

                    <button
                        v-else-if="canManageSection"
                        type="button"
                        class="mb-1 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                        aria-label="Tambah section"
                        title="Tambah section"
                        @click="openCreateSection"
                    >
                        <IconPlus :size="19" />
                    </button>

                </div>

                <BaseCard :padding="false" class="!border-t-0 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3">
                        <div v-if="tabsPartSoal.length" class="inline-flex min-w-0 max-w-full items-center gap-1">
                            <BaseTabs
                                :model-value="partSoalTerpilihId"
                                :items="tabsPartSoal"
                                :active-action="canManagePartSoal && partSoalTerpilihData !== null"
                                aria-label="Filter part soal"
                                class="min-w-0 max-w-full"
                                @update:model-value="onPartSoalChange"
                            >
                                <template #active-action>
                                    <BaseActionMenu
                                        :items="partSoalActions"
                                        icon-only
                                        teleport-menu
                                        compact
                                        class="-ml-1 [&>button]:!text-on-primary"
                                        aria-label="Aksi part soal aktif"
                                        @select="handlePartSoalAction"
                                    />
                                </template>
                            </BaseTabs>
                            <button
                                v-if="canManagePartSoal"
                                type="button"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                                aria-label="Tambah part soal"
                                title="Tambah part soal"
                                @click="openCreatePartSoal"
                            >
                                <IconPlus :size="19" />
                            </button>
                        </div>
                        <button
                            v-else-if="canManagePartSoal"
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                            aria-label="Tambah part soal"
                            title="Tambah part soal"
                            @click="openCreatePartSoal"
                        >
                            <IconPlus :size="19" />
                        </button>

                        <div class="ml-auto flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <IconSearch
                                    :size="16"
                                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-text-muted"
                                />
                                <input
                                    v-model="search"
                                    type="text"
                                    class="h-9 w-full rounded-full border border-surface-container-high bg-surface-container-lowest pl-9 pr-9 text-body-md text-text-body placeholder:text-text-muted focus:border-secondary focus:outline-none"
                                    placeholder="Cari soal atau materi..."
                                    aria-label="Cari soal pada part aktif"
                                />
                                <button
                                    v-if="search"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-1 text-text-muted transition-colors hover:text-text-heading"
                                    aria-label="Hapus pencarian"
                                    @click="search = ''"
                                >
                                    <IconX :size="14" />
                                </button>
                            </div>
                            <BaseTambahData
                                :href="canCreateSoal ? route('pustaka-soal.tambah', createParams) : null"
                                :disabled="!canCreateSoal"
                                label="Tambah Soal Baru"
                            />
                        </div>
                    </div>

                    <EmptyState
                        v-if="soal.data?.length === 0"
                        :icon="IconBook"
                        title="Tidak ada soal ditemukan."
                        class="!rounded-none !border-0 !shadow-none"
                    />

                    <EmptyState
                        v-else-if="sedangMencari && soalTampil.length === 0"
                        :icon="IconSearch"
                        title="Tidak ada soal yang cocok dengan pencarian."
                        description="Coba kata kunci lain atau hapus pencarian."
                        class="!rounded-none !border-0 !shadow-none"
                    />

                    <KelompokSoalPustaka
                        v-else
                        :kelompok-soal="kelompokSoalPerSection"
                        :toggle-materi-soal-fn="togglePassage"
                        :materi-soal-type-label-fn="materiSoalTypeLabel"
                        :is-materi-soal-expanded-fn="isPassageExpanded"
                        :bank-locked="bankIsLocked"
                        attached
                        @start-edit="startEditPassage"
                        @add-soal="openQuickAdd"
                        @soal-delete="deleteQuestion"
                        @delete-materi-soal="deletePassage"
                    />
                </BaseCard>
            </div>

            <TambahSoalMateriModal
                v-if="addingPassageId && !bankIsLocked"
                :show="Boolean(addingPassageId) && !bankIsLocked"
                :form="quickQuestionForm"
                :quick-is-audio="quickIsAudio"
                :option-keys="optionKeys"
                @close="closeQuickAdd"
                @submit="saveQuickQuestion"
            />

            <EditMateriSoalModal
                v-if="editingMateriSoal && !bankIsLocked"
                :show="Boolean(editingMateriSoal) && !bankIsLocked"
                :materi-soal="editingMateriSoal"
                :form="materiSoalForm"
                :policy="editingMateriPolicy"
                @close="cancelEditPassage"
                @submit="savePassage"
                @remove-media="(jenisMedia) => removePassageMedia(editingMateriSoal, jenisMedia)"
            />

            <SectionFormModal
                :show="showSectionModal && !bankIsLocked"
                :creating="creatingSection"
                :form="modalSectionForm"
                :program-tes="programTesSection"
                lock-program-tes
                @close="closeSectionModal"
                @submit="creatingSection ? saveSection() : updateSection()"
            />

            <PartSoalFormModal
                :show="showPartSoalModal && !bankIsLocked"
                :creating="creatingPartSoal"
                :form="modalPartSoalForm"
                :bank-soal="bankSoal"
                :opsi-section="opsiSection"
                :bank-filter="bankSoalTerpilihId"
                lock-bank-soal
                lock-section
                @close="closePartSoalModal"
                @submit="creatingPartSoal ? savePartSoal() : updatePartSoal()"
            />

            <Pagination
                :links="soal.links"
                :total="soal.total"
                :per-page="soal.per_page"
            />
            <UploadProgressBar
                :show="showUploadProgress"
                :label="uploadLabel"
                :media-type="mediaType"
            />

         </template>
    </TataLetakDasbor>
</template>
