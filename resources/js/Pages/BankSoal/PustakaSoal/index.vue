<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import TataLetakDasbor from "@/Layouts/TataLetakDasbor.vue";
import UploadProgressBar from "@/Components/Shared/UploadProgressBar.vue";
import EmptyState from "@/Components/Shared/EmptyState.vue";
import Pagination from "@/Components/Shared/Pagination.vue";
import UserGuide from "@/Components/Shared/UserGuide.vue";
import KelompokSoalPustaka from "@/Pages/BankSoal/PustakaSoal/components/KelompokSoalPustaka.vue";
import RingkasanJenisTesPustaka from "@/Pages/BankSoal/PustakaSoal/components/RingkasanJenisTesPustaka.vue";
import RingkasanBankSoalPustaka from "@/Pages/BankSoal/PustakaSoal/components/RingkasanBankSoalPustaka.vue";
import { IconArrowLeft, IconBook, IconPlus } from "@tabler/icons-vue";
import { ref, computed, watch } from "vue";
import { useConfirm } from "@/Composables/useConfirm";
import { useUploadProgress } from "@/Composables/useUploadProgress";

const confirm = useConfirm();

const props = defineProps({
    soal: { type: Object, default: () => ({ data: [] }) },
    jenisTes: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
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

const bankSoalTerpilih = computed(() => props.bankSoal.find(
    (bank) => String(bank.id) === String(bankSoalTerpilihId.value),
) || null);

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

function onKeterampilanChange(value) {
    keterampilanTerpilih.value = String(value);
    bagianSoalTerpilihId.value = String(bagianSoalUntukKeterampilan.value[0]?.id || '');
    applyFilters();
}

function onBagianSoalChange(value) {
    bagianSoalTerpilihId.value = String(value);
    applyFilters();
}

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
    <TataLetakDasbor title="Bank Soal" subtitle="Kelola soal dan materi per bank soal">
        <div class="flex justify-end mb-6">
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
        </div>

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
                :back-url="route('pustaka-soal.daftar')"
            />
        </template>

        <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div class="flex items-center gap-4">
                    <div>
                        <p class="text-title-md font-semibold text-text-heading">
                            {{ bankSoalTerpilih?.nama || 'Bank Soal' }}
                        </p>
                        <p class="text-text-body text-body-md">
                            {{ soal.total || 0 }} soal ditemukan
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <BaseButton variant="secondary" size="sm" :href="backToBankSoalUrl">
                        <IconArrowLeft :size="16" /> Kembali
                    </BaseButton>
                    <BaseButton :href="route('pustaka-soal.tambah', createParams)">
                        <IconPlus :size="18" /> Tambah Soal Baru
                    </BaseButton>
                </div>
            </div>

            <BaseTabsChrome
                v-if="tabsKeterampilan.length"
                :model-value="keterampilanTerpilih"
                :items="tabsKeterampilan"
                aria-label="Filter keterampilan soal"
                class="relative z-10 -mb-px px-5"
                @update:model-value="onKeterampilanChange"
            />

            <div v-if="tabsBagianSoal.length" class="mb-6 overflow-x-auto">
                <BaseTabs
                    :model-value="bagianSoalTerpilihId"
                    :items="tabsBagianSoal"
                    :grouped="false"
                    @update:model-value="onBagianSoalChange"
                />
            </div>

            <EmptyState
                v-if="soal.data?.length === 0"
                :icon="IconBook"
                title="Tidak ada soal ditemukan."
            />

            <BaseCard v-else :padding="false" class="overflow-hidden">
                <KelompokSoalPustaka
                    :kelompok-soal="kelompokSoalPerKeterampilan"
                   :materi-soal-form="materiSoalForm"
                   :editing-materi-soal-id="editingPassageId"
                   :keterampilan-name-fn="namaKeterampilanDariSoal"
                 :bank-name-fn="bankName"
                 :option-keys="optionKeys"
                 :quick-form="quickQuestionForm"
                 :bank-soal="bankSoal"
                 :adding-materi-soal-id="addingPassageId"
                 :quick-bagian-soal-fn="quickParts"
                   :quick-is-audio="quickIsAudio"
                   :label-keterampilan-fn="namaKeterampilanDariId"
                   :toggle-materi-soal-fn="togglePassage"
                    :materi-soal-type-label-fn="materiSoalTypeLabel"
                    :is-materi-soal-expanded-fn="isPassageExpanded"
                    attached
                    @start-edit="startEditPassage"
                    @cancel-edit="cancelEditPassage"
                    @save-materi-soal="savePassage"
                    @add-soal="openQuickAdd"
                    @close-add="closeQuickAdd"
                    @save-quick="saveQuickQuestion"
                    @soal-delete="deleteQuestion"
                    @delete-materi-soal="deletePassage"
                />
            </BaseCard>

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
