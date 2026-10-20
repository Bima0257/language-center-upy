<script setup>
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import TataLetakDasbor from "@/Layouts/TataLetakDasbor.vue";
import UploadProgressBar from "@/Components/Shared/UploadProgressBar.vue";
import EmptyState from "@/Components/Shared/EmptyState.vue";
import Pagination from "@/Components/Shared/Pagination.vue";
import PemilihPustakaSoal from "@/Pages/BankSoal/PustakaSoal/Components/PemilihPustakaSoal.vue";
import FilterPustakaSoal from "@/Pages/BankSoal/PustakaSoal/Components/FilterPustakaSoal.vue";
import KelompokSoalPustaka from "@/Pages/BankSoal/PustakaSoal/Components/KelompokSoalPustaka.vue";
import AksiTinjauanMassal from "@/Pages/BankSoal/PustakaSoal/Components/AksiTinjauanMassal.vue";
import { IconBook, IconPlus, IconBooks } from "@tabler/icons-vue";
import { ref, computed, watch } from "vue";
import { useConfirm } from "@/Composables/useConfirm";
import { useToast } from "@/Composables/useToast";
import { useUploadProgress } from "@/Composables/useUploadProgress";

const page = usePage();
const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    soal: { type: Object, default: () => ({ data: [] }) },
    jenisTes: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
    opsiKeterampilan: { type: Array, default: () => [] },
    bagianSoal: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
    status: { type: Array, default: () => ["draft", "approved", "rejected"] },
    filter: { type: Object, default: () => ({}) },
});

const canReview = computed(() => {
    const roles = page.props.auth?.roles || [];
    return roles.some((r) => r === "admin" || r === "superadmin");
});

const searchQuery = ref(props.filter.cari || "");
const keterampilanTerpilih = ref(props.filter.keterampilan || "");
const bankSoalTerpilihId = ref(props.filter.bank_soal_id || "");
const selectedStatus = ref(props.filter.status || "");
const bagianSoalTerpilihId = ref(props.filter.bagian_soal_id || "");
const jenisTesTerpilihId = ref("");

const createParams = computed(() => {
    const p = {};
    if (bankSoalTerpilihId.value) p.bank_soal_id = bankSoalTerpilihId.value;
    if (keterampilanTerpilih.value) p.keterampilan = keterampilanTerpilih.value;
    if (bagianSoalTerpilihId.value) p.bagian_soal_id = bagianSoalTerpilihId.value;
    if (selectedStatus.value) p.status = selectedStatus.value;
    if (searchQuery.value) p.cari = searchQuery.value;
    return p;
});

if (bankSoalTerpilihId.value) {
    const bank = props.bankSoal.find(
        (b) => String(b.id) === String(bankSoalTerpilihId.value),
    );
    if (bank?.jenis_tes_id) {
        jenisTesTerpilihId.value = String(bank.jenis_tes_id);
    }
}

const selectedType = computed(
    () =>
        props.jenisTes.find(
            (t) => String(t.id) === String(jenisTesTerpilihId.value),
        ) || null,
);

const bankSoalTersaring = computed(() => {
    if (!selectedType.value) return props.bankSoal;
    return props.bankSoal.filter(
        (b) => String(b.jenis_tes_id) === String(selectedType.value.id),
    );
});

const filteredPartOptions = computed(() =>
    props.bagianSoal.filter((p) =>
        (!bankSoalTerpilihId.value || String(p.bank_soal_id) === String(bankSoalTerpilihId.value)) &&
        (!keterampilanTerpilih.value || String(p.keterampilan_id) === String(keterampilanTerpilih.value)),
    ),
);

function onTypeChange(value) {
    jenisTesTerpilihId.value = value;
    bankSoalTerpilihId.value = "";
    keterampilanTerpilih.value = "";
    bagianSoalTerpilihId.value = "";
    selectedStatus.value = "";
    searchQuery.value = "";
    applyFilters();
}

function onBankChange(value) {
    bankSoalTerpilihId.value = value;
    keterampilanTerpilih.value = "";
    bagianSoalTerpilihId.value = "";
    applyFilters();
}

const selectedIds = ref([]);
const expandedPassages = ref({});
const editingPassageId = ref(null);
const addingPassageId = ref(null);

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

const optionKeys = ["A", "B", "C", "D"];

function materialForSkill(bankId, keterampilanId) {
    return props.kebijakanMateriSoal.find(p =>
         String(p.bank_soal_id) === String(bankId) &&
        String(p.keterampilan_id) === String(keterampilanId),
    )?.material || 'text';
}

function namaKeterampilanDariId(keterampilanId) {
    return props.opsiKeterampilan.find(s => String(s.value) === String(keterampilanId))?.label || '';
}

function namaKeterampilanDariSoal(keterampilan) {
    return keterampilan?.nama || '';
}

const quickIsAudio = computed(
    () => materialForSkill(quickQuestionForm.bank_soal_id, quickQuestionForm.keterampilan_id) === "audio",
);

function quickParts() {
    return props.bagianSoal.filter(
        (p) =>
            String(p.keterampilan_id) === String(quickQuestionForm.keterampilan_id) &&
            String(p.bank_soal_id) === String(quickQuestionForm.bank_soal_id),
    );
}

const statusLabels = {
    draft: "Draf",
    approved: "Disetujui",
    rejected: "Ditolak",
};

function materiSoalTypeLabel(materiSoal) {
    if (!materiSoal) return "Teks";
    if (materiSoal.memiliki_audio) return "Audio";
    if (materiSoal.memiliki_gambar) return "Gambar";
    return "Teks";
}

function bankName(id) {
    return props.bankSoal.find((b) => b.id === id)?.nama || "";
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

function savePassage(materiSoal) {
    materiSoalForm.put(route("pustaka-soal.materi-soal.perbarui", materiSoal.id), {
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

const { showUploadProgress, uploadLabel, mediaType } = useUploadProgress(materiSoalForm);

function preselectQuickSkill() {
    if (!addingPassageId.value) return;

    const materiSoal = props.soal.data
        .map((q) => q.materi_soal)
        .find((p) => p && String(p.id) === String(addingPassageId.value));

    if (!materiSoal) return;

    const materiSoalMaterial = materiSoal.memiliki_audio ? "audio" : "text";
    const bankId = quickQuestionForm.bank_soal_id;

    if (!bankId) return;

    const policy = props.kebijakanMateriSoal.find(
        (p) =>
            String(p.bank_soal_id) === String(bankId) &&
            p.material === materiSoalMaterial,
    );

    quickQuestionForm.keterampilan_id = policy ? policy.keterampilan_id : "";
}

watch(
    () => quickQuestionForm.bank_soal_id,
    () => preselectQuickSkill(),
);

function openQuickAdd(materiSoal) {
    addingPassageId.value = materiSoal.id;
    quickQuestionForm.clearErrors();
    quickQuestionForm.reset();
    quickQuestionForm.materi_soal_id = materiSoal.id;
    if (props.bankSoal.length === 1) {
        quickQuestionForm.bank_soal_id = props.bankSoal[0].id;
    }
    preselectQuickSkill();
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
                    groups: Array.from(p.materiSoalGroups.values()),
                    standalone: p.standalone,
                }))
                .sort((a, b) => (a.bagianSoal?.urutan ?? 99) - (b.bagianSoal?.urutan ?? 99)),
        }))
        .sort((a, b) => a.label.localeCompare(b.label));
});

let debounceTimer = null;

function applyFilters() {
    selectedIds.value = [];
    const p = {};
    if (keterampilanTerpilih.value) p.keterampilan = keterampilanTerpilih.value;
    if (bankSoalTerpilihId.value) p.bank_soal_id = bankSoalTerpilihId.value;
    if (selectedStatus.value) p.status = selectedStatus.value;
    if (bagianSoalTerpilihId.value) p.bagian_soal_id = bagianSoalTerpilihId.value;
    if (searchQuery.value) p.cari = searchQuery.value;
    router.get(route("pustaka-soal.daftar"), p, { preserveState: true });
}

function onSearchInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
}
function resetFilters() {
    searchQuery.value = "";
    keterampilanTerpilih.value = "";
    bankSoalTerpilihId.value = "";
    selectedStatus.value = "";
    bagianSoalTerpilihId.value = "";
    applyFilters();
}

async function deleteQuestion(id, text) {
    if (!(await confirm.confirm(`Hapus soal: "${text.substring(0, 50)}..."?`)))
        return;
    router.delete(route("pustaka-soal.hapus", id), {
        preserveScroll: true,
    });
}

async function reviewQuestion(id, status) {
    const note =
        status === "rejected"
            ? await confirm.prompt("Catatan penolakan (opsional):")
            : null;
    router.patch(
        route("pustaka-soal.tinjau", id),
        { status, catatan_tinjauan: note },
        { preserveScroll: true },
    );
}

function isPassageSelected(group) {
    return (
        group.soal.length > 0 &&
        group.soal.every((q) => selectedIds.value.includes(q.id))
    );
}

function togglePassageSelection(group) {
    const ids = group.soal.map((q) => q.id);
    if (isPassageSelected(group)) {
        selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
    } else {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...ids]));
    }
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) selectedIds.value.splice(idx, 1);
    else selectedIds.value.push(id);
}

async function bulkReview(status) {
    if (selectedIds.value.length === 0) {
        toast.warning("Pilih soal terlebih dahulu.");
        return;
    }
    const note =
        status === "rejected"
            ? await confirm.prompt("Catatan penolakan (opsional):")
            : null;
    router.patch(
        route("pustaka-soal.tinjauan-massal"),
        { ids: selectedIds.value, status, catatan_tinjauan: note },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
        },
    );
}
</script>

<template>
    <Head title="Bank Soal" />
    <TataLetakDasbor title="Bank Soal" subtitle="Kelola soal, materi, dan status review per bank soal">
        <PemilihPustakaSoal
            :jenis-tes="jenisTes"
            :bank-soal-tersaring="bankSoalTersaring"
            :jenis-tes-terpilih-id="jenisTesTerpilihId"
            :bank-soal-terpilih-id="bankSoalTerpilihId"
            :bank-soal-dinonaktifkan="!jenisTesTerpilihId"
            :reset-dinonaktifkan="!bankSoalTerpilihId"
            @type-change="onTypeChange"
            @bank-change="onBankChange"
            @reset="resetFilters"
        />

        <template v-if="bankSoalTerpilihId">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <p class="text-text-body text-text-body text-body-md">
                        {{ soal.total || 0 }} soal ditemukan
                    </p>
                    <Link
                        v-if="canReview"
                        :href="route('pustaka-soal.bank-soal.daftar')"
                        class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline"
                    >
                        <IconBooks :size="16" /> Kelola Bank Soal
                    </Link>
                </div>
                <BaseButton
                    :href="route('pustaka-soal.tambah', createParams)"
                >
                    <IconPlus :size="18" /> Tambah Soal Baru
                </BaseButton>
            </div>

            <FilterPustakaSoal
                :search-query="searchQuery"
                 :keterampilan-terpilih="keterampilanTerpilih"
                 :bagian-soal-terpilih-id="bagianSoalTerpilihId"
                 :selected-status="selectedStatus"
                 :opsi-keterampilan="opsiKeterampilan"
                 :bagian-soal="filteredPartOptions"
                 :statuses="status"
                :status-labels="statusLabels"
                 @update:search-query="searchQuery = $event"
                 @search="onSearchInput"
                 @update:keterampilan-terpilih="keterampilanTerpilih = $event"
                  @update:bagian-soal-terpilih-id="bagianSoalTerpilihId = $event"
                 @update:selected-status="selectedStatus = $event"
                @filter-change="applyFilters"
            />

            <EmptyState
                v-if="soal.data?.length === 0"
                :icon="IconBook"
                title="Tidak ada soal ditemukan."
            />

             <KelompokSoalPustaka
                 v-else
                 :kelompok-soal="kelompokSoalPerKeterampilan"
                 :can-review="canReview"
                 :materi-soal-form="materiSoalForm"
                 :editing-materi-soal-id="editingPassageId"
                 :selected-ids="selectedIds"
                 :keterampilan-name-fn="namaKeterampilanDariSoal"
                 :bank-name-fn="bankName"
                 :option-keys="optionKeys"
                 :quick-form="quickQuestionForm"
                 :bank-soal="bankSoal"
                 :adding-materi-soal-id="addingPassageId"
                 :quick-bagian-soal-fn="quickParts"
                 :quick-is-audio="quickIsAudio"
                 :label-keterampilan-fn="namaKeterampilanDariId"
                 :is-materi-soal-selected-fn="isPassageSelected"
                 :toggle-materi-soal-selection-fn="togglePassageSelection"
                 :toggle-materi-soal-fn="togglePassage"
                 :materi-soal-type-label-fn="materiSoalTypeLabel"
                 :is-materi-soal-expanded-fn="isPassageExpanded"
                 @start-edit="startEditPassage"
                 @cancel-edit="cancelEditPassage"
                 @save-materi-soal="savePassage"
                 @add-soal="openQuickAdd"
                 @close-add="closeQuickAdd"
                 @save-quick="saveQuickQuestion"
                 @soal-toggle="toggleSelect"
                 @soal-review="reviewQuestion"
                 @soal-delete="deleteQuestion"
                 @delete-materi-soal="deletePassage"
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

             <AksiTinjauanMassal
                 :can-review="canReview"
                 :selected-count="selectedIds.length"
                 @review="bulkReview"
                 @clear="selectedIds = []"
             />
        </template>

        <!-- EMPTY STATE: BELUM PILIH BANK -->
        <EmptyState
            v-else
            :icon="IconBook"
            title="Pilih Jenis Tes dan Bank Soal"
            deskripsi="Daftar soal akan muncul setelah kamu memilih bank soal."
        />
    </TataLetakDasbor>
</template>
