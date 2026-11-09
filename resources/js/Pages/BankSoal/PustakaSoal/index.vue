<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import TataLetakDasbor from "@/Layouts/TataLetakDasbor.vue";
import UploadProgressBar from "@/Components/Shared/UploadProgressBar.vue";
import EmptyState from "@/Components/Shared/EmptyState.vue";
import Pagination from "@/Components/Shared/Pagination.vue";
import UserGuide from "@/Components/Shared/UserGuide.vue";
import KeterampilanFormModal from "@/Components/Ujian/KeterampilanFormModal.vue";
import BagianSoalFormModal from "@/Pages/Admin/DataInduk/BagianSoal/components/BagianSoalFormModal.vue";
import KelompokSoalPustaka from "@/Pages/BankSoal/PustakaSoal/Components/KelompokSoalPustaka.vue";
import EditMateriSoalModal from "@/Pages/BankSoal/PustakaSoal/Components/EditMateriSoalModal.vue";
import TambahSoalMateriModal from "@/Pages/BankSoal/PustakaSoal/Components/TambahSoalMateriModal.vue";
import RingkasanJenisTesPustaka from "@/Pages/BankSoal/PustakaSoal/Components/RingkasanJenisTesPustaka.vue";
import RingkasanBankSoalPustaka from "@/Pages/BankSoal/PustakaSoal/Components/RingkasanBankSoalPustaka.vue";
import { IconBook, IconEdit, IconPlus, IconTrash } from "@tabler/icons-vue";
import { ref, computed } from "vue";
import { useConfirm } from "@/Composables/useConfirm";
import { useUploadProgress } from "@/Composables/useUploadProgress";

const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    soal: { type: Object, default: () => ({ data: [] }) },
    jenisTes: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
    bankSoalTerpilih: { type: Object, default: null },
    opsiKeterampilan: { type: Array, default: () => [] },
    bagianSoal: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
    filter: { type: Object, default: () => ({}) },
    bankSoalTerpilihId: { type: [String, null], default: null },
    jenisTesTerpilihId: { type: [String, null], default: null },
});

const bankSoalTerpilihId = ref(props.bankSoalTerpilihId || props.filter.bank_soal_id || "");
const jenisTesTerpilihId = ref(props.jenisTesTerpilihId || props.filter.jenis_tes_id || "");
const bagianSoalDariFilter = props.bagianSoal.find(
    (part) => String(part.id) === String(props.filter.bagian_soal_id) &&
        String(part.bank_soal_id) === String(bankSoalTerpilihId.value),
);
const keterampilanTerpilih = ref(
    props.filter.keterampilan || bagianSoalDariFilter?.keterampilan_id || props.opsiKeterampilan[0]?.value || "",
);
const bagianSoalPertama = props.bagianSoal.find(
    (part) => String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
        String(part.keterampilan_id) === String(keterampilanTerpilih.value),
);
const bagianSoalTerpilihId = ref(props.filter.bagian_soal_id || bagianSoalPertama?.id || "");

const createParams = computed(() => {
    const p = {};
    if (bankSoalTerpilihId.value) p.bank_soal_id = bankSoalTerpilihId.value;
    if (keterampilanTerpilih.value) p.keterampilan = keterampilanTerpilih.value;
    if (bagianSoalTerpilihId.value) p.bagian_soal_id = bagianSoalTerpilihId.value;
    return p;
});

const ringkasanJenisTes = computed(() => {
    const groups = new Map(props.jenisTes.map((jenisTes) => [String(jenisTes.id), {
        id: jenisTes.id,
        nama: jenisTes.nama,
        jumlahBankSoal: 0,
        jumlahSoal: 0,
    }]));

    props.bankSoal.forEach((bank) => {
        const group = groups.get(String(bank.jenis_tes_id));
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

const bankIsLocked = computed(() => Number(bankSoalTerpilih.value?.bagian_ujian_count || 0) > 0);

function jenisTesUrl(jenisTesId) {
    return `${route('pustaka-soal.daftar')}?jenis_tes_id=${encodeURIComponent(jenisTesId)}`;
}

function bankSoalUrl(bankSoalId) {
    const params = new URLSearchParams({ bank_soal_id: String(bankSoalId) });

    if (jenisTesTerpilihId.value) {
        params.set('jenis_tes_id', String(jenisTesTerpilihId.value));
    }

    return `${route('pustaka-soal.daftar')}?${params.toString()}`;
}

const backToBankSoalUrl = computed(() => jenisTesTerpilihId.value
    ? jenisTesUrl(jenisTesTerpilihId.value)
    : route('pustaka-soal.daftar'));

const pageBackUrl = computed(() => {
    if (bankSoalTerpilihId.value) return backToBankSoalUrl.value;
    if (jenisTesTerpilihId.value) return route('pustaka-soal.daftar');
    return null;
});

const tabsKeterampilan = computed(() => props.opsiKeterampilan.map((skill) => ({
    value: String(skill.value),
    label: skill.label,
})));

const bagianSoalUntukKeterampilan = computed(() => props.bagianSoal
    .filter((part) =>
        String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
        String(part.keterampilan_id) === String(keterampilanTerpilih.value),
    )
    .sort((left, right) => (left.urutan ?? 0) - (right.urutan ?? 0)));

const tabsBagianSoal = computed(() => bagianSoalUntukKeterampilan.value.map((part) => ({
    value: String(part.id),
    label: part.nama,
})));

const canManageKeterampilan = computed(() =>
    (page.props.auth?.permissions || []).includes("master_data.manage"),
);
const canManageBagianSoal = canManageKeterampilan;

const keterampilanTerpilihData = computed(() => props.opsiKeterampilan.find(
    (skill) => String(skill.value) === String(keterampilanTerpilih.value),
) || null);

const keterampilanActions = computed(() => [
    { value: "edit", label: "Ubah keterampilan", icon: IconEdit },
    { value: "delete", label: "Hapus keterampilan", icon: IconTrash, variant: "danger" },
]);

const bagianSoalTerpilihData = computed(() => bagianSoalUntukKeterampilan.value.find(
    (part) => String(part.id) === String(bagianSoalTerpilihId.value),
) || null);

const bagianSoalActions = computed(() => [
    { value: "edit", label: "Ubah bagian soal", icon: IconEdit },
    { value: "delete", label: "Hapus bagian soal", icon: IconTrash, variant: "danger" },
]);

const jenisTesKeterampilan = computed(() => {
    const jenisTesId = bankSoalTerpilih.value?.jenis_tes_id || jenisTesTerpilihId.value;

    return props.jenisTes.filter((jenisTes) => String(jenisTes.id) === String(jenisTesId));
});

const canCreateSoal = computed(() => Boolean(
    !bankIsLocked.value &&
    tabsKeterampilan.value.length &&
    tabsBagianSoal.value.length &&
    keterampilanTerpilih.value &&
    bagianSoalTerpilihId.value,
));

function onKeterampilanChange(value) {
    keterampilanTerpilih.value = String(value);
    bagianSoalTerpilihId.value = String(bagianSoalUntukKeterampilan.value[0]?.id || '');
    applyFilters();
}

function onBagianSoalChange(value) {
    bagianSoalTerpilihId.value = String(value);
    applyFilters();
}

function policyForKeterampilan(keterampilanId) {
    return props.kebijakanMateriSoal.find((policy) =>
        String(policy.keterampilan_id) === String(keterampilanId),
    ) || null;
}

function openCreateKeterampilan() {
    creatingKeterampilan.value = true;
    keterampilanYangDiedit.value = null;
    keterampilanForm.clearErrors();
    keterampilanForm.reset();
    keterampilanForm.jenis_tes_id = bankSoalTerpilih.value?.jenis_tes_id || jenisTesTerpilihId.value;
    showKeterampilanModal.value = true;
}

function openEditKeterampilan() {
    const keterampilan = keterampilanTerpilihData.value;
    if (!keterampilan) return;

    creatingKeterampilan.value = false;
    keterampilanYangDiedit.value = keterampilan;
    editKeterampilanForm.clearErrors();
    editKeterampilanForm.reset();
    editKeterampilanForm.jenis_tes_id = bankSoalTerpilih.value?.jenis_tes_id || jenisTesTerpilihId.value;
    editKeterampilanForm.kode = keterampilan.kode || "";
    editKeterampilanForm.nama = keterampilan.label || "";
    editKeterampilanForm.deskripsi = keterampilan.deskripsi || "";
    editKeterampilanForm.aktif = keterampilan.aktif !== false;

    const policy = policyForKeterampilan(keterampilan.value);
    editKeterampilanForm.ketentuan_materi = policy?.ketentuan_materi || "";
    editKeterampilanForm.aturan_media = {
        text: policy?.aturan_media?.text || "",
        audio: policy?.aturan_media?.audio || "",
        image: policy?.aturan_media?.image || "",
    };
    showKeterampilanModal.value = true;
}

function closeKeterampilanModal() {
    showKeterampilanModal.value = false;
    keterampilanYangDiedit.value = null;
    creatingKeterampilan.value = false;
    keterampilanForm.reset();
    editKeterampilanForm.reset();
}

function syncKeterampilanSelection(pageResponse) {
    const nextSkillId = pageResponse.props.filter?.keterampilan
        || pageResponse.props.opsiKeterampilan?.[0]?.value
        || "";

    keterampilanTerpilih.value = String(nextSkillId);
    bagianSoalTerpilihId.value = String(pageResponse.props.bagianSoal?.find((part) =>
        String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
        String(part.keterampilan_id) === String(nextSkillId),
    )?.id || "");
}

function saveKeterampilan() {
    keterampilanForm.post(route("admin.data-induk.keterampilan.simpan"), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closeKeterampilanModal();
            syncKeterampilanSelection(pageResponse);
        },
    });
}

function updateKeterampilan() {
    if (!keterampilanYangDiedit.value) return;

    editKeterampilanForm.put(route(
        "admin.data-induk.keterampilan.perbarui",
        keterampilanYangDiedit.value.value,
    ), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closeKeterampilanModal();
            syncKeterampilanSelection(pageResponse);
        },
    });
}

async function deleteKeterampilan() {
    const keterampilan = keterampilanTerpilihData.value;
    if (!keterampilan) return;

    if (!(await confirm.confirm(`Hapus keterampilan "${keterampilan.label}"?`))) return;

    router.delete(route("admin.data-induk.keterampilan.hapus", keterampilan.value), {
        preserveScroll: true,
        onSuccess: syncKeterampilanSelection,
    });
}

function handleKeterampilanAction(action) {
    if (action === "edit") {
        openEditKeterampilan();
        return;
    }

    if (action === "delete") {
        deleteKeterampilan();
    }
}

function openCreateBagianSoal() {
    creatingBagianSoal.value = true;
    bagianSoalYangDiedit.value = null;
    bagianSoalForm.clearErrors();
    bagianSoalForm.reset();
    bagianSoalForm.bank_soal_id = bankSoalTerpilihId.value;
    bagianSoalForm.keterampilan_id = keterampilanTerpilih.value;
    showBagianSoalModal.value = true;
}

function openEditBagianSoal() {
    const bagianSoal = bagianSoalTerpilihData.value;
    if (!bagianSoal) return;

    creatingBagianSoal.value = false;
    bagianSoalYangDiedit.value = bagianSoal;
    editBagianSoalForm.clearErrors();
    editBagianSoalForm.reset();
    editBagianSoalForm.bank_soal_id = String(bagianSoal.bank_soal_id);
    editBagianSoalForm.keterampilan_id = String(bagianSoal.keterampilan_id);
    editBagianSoalForm.nama = bagianSoal.nama;
    editBagianSoalForm.petunjuk = bagianSoal.petunjuk || "";
    editBagianSoalForm.aktif = bagianSoal.aktif !== false;
    showBagianSoalModal.value = true;
}

function closeBagianSoalModal() {
    showBagianSoalModal.value = false;
    bagianSoalYangDiedit.value = null;
    creatingBagianSoal.value = false;
    bagianSoalForm.reset();
    editBagianSoalForm.reset();
}

function syncBagianSoalSelection(pageResponse) {
    const nextPartId = pageResponse.props.filter?.bagian_soal_id
        || pageResponse.props.bagianSoal?.find((part) =>
            String(part.bank_soal_id) === String(bankSoalTerpilihId.value) &&
            String(part.keterampilan_id) === String(keterampilanTerpilih.value),
        )?.id
        || "";

    bagianSoalTerpilihId.value = String(nextPartId);
}

function saveBagianSoal() {
    bagianSoalForm.post(route("admin.data-induk.bagian-soal.simpan"), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closeBagianSoalModal();
            syncBagianSoalSelection(pageResponse);
        },
    });
}

function updateBagianSoal() {
    if (!bagianSoalYangDiedit.value) return;

    editBagianSoalForm.put(route(
        "admin.data-induk.bagian-soal.perbarui",
        bagianSoalYangDiedit.value.id,
    ), {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closeBagianSoalModal();
            syncBagianSoalSelection(pageResponse);
        },
    });
}

async function deleteBagianSoal() {
    const bagianSoal = bagianSoalTerpilihData.value;
    if (!bagianSoal) return;

    if (!(await confirm.confirm(`Hapus bagian soal "${bagianSoal.nama}"?`))) return;

    router.delete(route("admin.data-induk.bagian-soal.hapus", bagianSoal.id), {
        preserveScroll: true,
        onSuccess: syncBagianSoalSelection,
    });
}

function handleBagianSoalAction(action) {
    if (action === "edit") {
        openEditBagianSoal();
        return;
    }

    if (action === "delete") {
        deleteBagianSoal();
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
    keterampilan_id: "",
    bagian_soal_id: "",
    teks_soal: "",
    pilihan_a: "",
    pilihan_b: "",
    pilihan_c: "",
    pilihan_d: "",
    kunci_jawaban: "",
});

const keterampilanForm = useForm({
    jenis_tes_id: "",
    kode: "",
    nama: "",
    deskripsi: "",
    aktif: true,
    ketentuan_materi: "",
    aturan_media: { text: "", audio: "", image: "" },
});

const editKeterampilanForm = useForm({
    jenis_tes_id: "",
    kode: "",
    nama: "",
    deskripsi: "",
    aktif: true,
    ketentuan_materi: "",
    aturan_media: { text: "", audio: "", image: "" },
});

const bagianSoalForm = useForm({
    bank_soal_id: "",
    keterampilan_id: "",
    nama: "",
    petunjuk: "",
    aktif: true,
});

const editBagianSoalForm = useForm({
    bank_soal_id: "",
    keterampilan_id: "",
    nama: "",
    petunjuk: "",
    aktif: true,
});

const showKeterampilanModal = ref(false);
const creatingKeterampilan = ref(false);
const keterampilanYangDiedit = ref(null);
const modalKeterampilanForm = computed(() => creatingKeterampilan.value
    ? keterampilanForm
    : editKeterampilanForm);
const showBagianSoalModal = ref(false);
const creatingBagianSoal = ref(false);
const bagianSoalYangDiedit = ref(null);
const modalBagianSoalForm = computed(() => creatingBagianSoal.value
    ? bagianSoalForm
    : editBagianSoalForm);

const optionKeys = ["A", "B", "C", "D"];

function materialForSkill(bankId, keterampilanId) {
    return policyForBankAndSkill(bankId, keterampilanId)?.materi || null;
}

function policyForBankAndSkill(bankId, keterampilanId) {
    const bank = props.bankSoal.find((item) => String(item.id) === String(bankId));

    return props.kebijakanMateriSoal.find(p =>
        String(p.jenis_tes_id) === String(bank?.jenis_tes_id) &&
        String(p.keterampilan_id) === String(keterampilanId),
    ) || null;
}

const editingMateriPolicy = computed(() => policyForBankAndSkill(
    bankSoalTerpilihId.value,
    keterampilanTerpilih.value,
));

function namaKeterampilanDariId(keterampilanId) {
    return props.opsiKeterampilan.find(s => String(s.value) === String(keterampilanId))?.label || '';
}

const quickIsAudio = computed(
    () => materialForSkill(quickQuestionForm.bank_soal_id, quickQuestionForm.keterampilan_id) === "audio",
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
    if (!editingMateriSoal.value) return;

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
    quickQuestionForm.keterampilan_id = keterampilanTerpilih.value;
    quickQuestionForm.bagian_soal_id = bagianSoalTerpilihId.value;
}

function closeQuickAdd() {
    addingPassageId.value = null;
    quickQuestionForm.reset();
}

function saveQuickQuestion() {
    quickQuestionForm
        .transform((data) => ({
            materi_soal_id: data.materi_soal_id,
            bank_soal_id: data.bank_soal_id,
            soal: [
                {
                    keterampilan_id: data.keterampilan_id,
                    bagian_soal_id: data.bagian_soal_id,
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

const kelompokSoalPerKeterampilan = computed(() => {
    const keterampilanMap = new Map();

    for (const q of props.soal.data) {
        const sid = q.keterampilan_id || "none";
        if (!keterampilanMap.has(sid)) {
            keterampilanMap.set(sid, { keterampilan_id: sid, bagianSoal: new Map() });
        }
        const bucket = keterampilanMap.get(sid);

        const pid = q.bagian_soal_id || "none";
        if (!bucket.bagianSoal.has(pid)) {
            bucket.bagianSoal.set(pid, {
                bagianSoal: q.bagian_soal || null,
                materiSoalGroups: new Map(),
                standalone: [],
            });
        }
        const bagianSoalBucket = bucket.bagianSoal.get(pid);

        if (q.materi_soal) {
            if (!bagianSoalBucket.materiSoalGroups.has(q.materi_soal_id)) {
                bagianSoalBucket.materiSoalGroups.set(q.materi_soal_id, {
                    materiSoal: q.materi_soal,
                    soal: [],
                });
            }
            bagianSoalBucket.materiSoalGroups.get(q.materi_soal_id).soal.push(q);
        } else {
            bagianSoalBucket.standalone.push(q);
        }
    }

    return Array.from(keterampilanMap.values())
        .map((s) => ({
            keterampilan_id: s.keterampilan_id,
            label: namaKeterampilanDariId(s.keterampilan_id),
            bagianSoal: Array.from(s.bagianSoal.values())
                .map((p) => ({
                    bagianSoal: p.bagianSoal,
                    groups: Array.from(p.materiSoalGroups.values()).map((group) => ({
                        ...group,
                        soal: [...group.soal].sort(
                            (left, right) => (left.urutan ?? 0) - (right.urutan ?? 0),
                        ),
                    })),
                    standalone: p.standalone,
                }))
                .sort((a, b) => (a.bagianSoal?.urutan ?? 99) - (b.bagianSoal?.urutan ?? 99)),
        }))
        .sort((a, b) => a.label.localeCompare(b.label));
});

function applyFilters() {
    const p = {};
    if (jenisTesTerpilihId.value) p.jenis_tes_id = jenisTesTerpilihId.value;
    if (keterampilanTerpilih.value) p.keterampilan = keterampilanTerpilih.value;
    if (bankSoalTerpilihId.value) p.bank_soal_id = bankSoalTerpilihId.value;
    if (bagianSoalTerpilihId.value) p.bagian_soal_id = bagianSoalTerpilihId.value;
    router.get(route("pustaka-soal.daftar"), p, { preserveState: true });
}

async function deleteQuestion(id, text) {
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
                    { judul: 'Pilih Jenis Tes', desc: 'Buka jenis tes untuk melihat bank soal yang tersedia.' },
                    { judul: 'Pilih Bank Soal', desc: 'Buka bank soal untuk menampilkan daftar soal.' },
                    { judul: 'Kelola & Filter Soal', desc: 'Gunakan filter Keterampilan dan Bagian Soal untuk menemukan soal.' },
                ]"
                :rules="[
                    'Soal dikelompokkan per keterampilan → bagian soal → materi soal.',
                ]"
            />
        </template>

        <template v-if="!bankSoalTerpilihId">
            <RingkasanJenisTesPustaka
                v-if="!jenisTesTerpilihId"
                :ringkasan-jenis-tes="ringkasanJenisTes"
                :detail-url="jenisTesUrl"
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
                    v-if="tabsKeterampilan.length || canManageKeterampilan"
                    class="flex min-w-0 items-end gap-1"
                >
                    <div v-if="tabsKeterampilan.length" class="min-w-0 flex-1">
                        <BaseTabsChrome
                            :model-value="keterampilanTerpilih"
                            :items="tabsKeterampilan"
                            :active-action="canManageKeterampilan && keterampilanTerpilihData !== null"
                            aria-label="Filter keterampilan soal"
                            class="relative z-10 -mb-px px-5"
                            @update:model-value="onKeterampilanChange"
                        >
                            <template #active-action>
                                <BaseActionMenu
                                    :items="keterampilanActions"
                                    icon-only
                                    teleport-menu
                                    compact
                                    class="-ml-1"
                                    aria-label="Aksi keterampilan aktif"
                                    @select="handleKeterampilanAction"
                                />
                            </template>
                            <template #after>
                                <button
                                    v-if="canManageKeterampilan"
                                    type="button"
                                    class="mb-1 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                                    aria-label="Tambah keterampilan"
                                    title="Tambah keterampilan"
                                    @click="openCreateKeterampilan"
                                >
                                    <IconPlus :size="19" />
                                </button>
                            </template>
                        </BaseTabsChrome>
                    </div>

                    <button
                        v-else-if="canManageKeterampilan"
                        type="button"
                        class="mb-1 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                        aria-label="Tambah keterampilan"
                        title="Tambah keterampilan"
                        @click="openCreateKeterampilan"
                    >
                        <IconPlus :size="19" />
                    </button>

                </div>

                <BaseCard :padding="false" class="!border-t-0 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3">
                        <div v-if="tabsBagianSoal.length" class="inline-flex min-w-0 max-w-full items-center gap-1">
                            <BaseTabs
                                :model-value="bagianSoalTerpilihId"
                                :items="tabsBagianSoal"
                                :active-action="canManageBagianSoal && bagianSoalTerpilihData !== null"
                                aria-label="Filter bagian soal"
                                class="min-w-0 max-w-full"
                                @update:model-value="onBagianSoalChange"
                            >
                                <template #active-action>
                                    <BaseActionMenu
                                        :items="bagianSoalActions"
                                        icon-only
                                        teleport-menu
                                        compact
                                        class="-ml-1 [&>button]:!text-on-primary"
                                        aria-label="Aksi bagian soal aktif"
                                        @select="handleBagianSoalAction"
                                    />
                                </template>
                            </BaseTabs>
                            <button
                                v-if="canManageBagianSoal"
                                type="button"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                                aria-label="Tambah bagian soal"
                                title="Tambah bagian soal"
                                @click="openCreateBagianSoal"
                            >
                                <IconPlus :size="19" />
                            </button>
                        </div>
                        <button
                            v-else-if="canManageBagianSoal"
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1"
                            aria-label="Tambah bagian soal"
                            title="Tambah bagian soal"
                            @click="openCreateBagianSoal"
                        >
                            <IconPlus :size="19" />
                        </button>

                        <div class="ml-auto flex shrink-0 items-center gap-2">
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

                    <KelompokSoalPustaka
                        v-else
                        :kelompok-soal="kelompokSoalPerKeterampilan"
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
                v-if="addingPassageId"
                :show="Boolean(addingPassageId)"
                :form="quickQuestionForm"
                :quick-is-audio="quickIsAudio"
                :option-keys="optionKeys"
                @close="closeQuickAdd"
                @submit="saveQuickQuestion"
            />

            <EditMateriSoalModal
                v-if="editingMateriSoal"
                :show="Boolean(editingMateriSoal)"
                :materi-soal="editingMateriSoal"
                :form="materiSoalForm"
                :policy="editingMateriPolicy"
                @close="cancelEditPassage"
                @submit="savePassage"
                @remove-media="(jenisMedia) => removePassageMedia(editingMateriSoal, jenisMedia)"
            />

            <KeterampilanFormModal
                :show="showKeterampilanModal"
                :creating="creatingKeterampilan"
                :form="modalKeterampilanForm"
                :jenis-tes="jenisTesKeterampilan"
                lock-jenis-tes
                @close="closeKeterampilanModal"
                @submit="creatingKeterampilan ? saveKeterampilan() : updateKeterampilan()"
            />

            <BagianSoalFormModal
                :show="showBagianSoalModal"
                :creating="creatingBagianSoal"
                :form="modalBagianSoalForm"
                :bank-soal="bankSoal"
                :opsi-keterampilan="opsiKeterampilan"
                :bank-filter="bankSoalTerpilihId"
                lock-bank-soal
                lock-keterampilan
                @close="closeBagianSoalModal"
                @submit="creatingBagianSoal ? saveBagianSoal() : updateBagianSoal()"
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
