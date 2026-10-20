/**
 * Helper keterampilan berbasis ID — perilaku material ditentukan policy database
 * (keterampilan_materi-soal_policies) yang dikirim backend, bukan pencocokan kode keterampilan.
 */

/**
 * Cari keterampilan berdasarkan ID dari array opsiKeterampilan.
 * @param {Array} opsiKeterampilan - Array [{value: id, label: name, kode: kode}]
 * @param {number|string} id - ID keterampilan
 * @returns {Object|null}
 */
export function keterampilanById(opsiKeterampilan, id) {
    return opsiKeterampilan.find((s) => String(s.value) === String(id)) || null;
}

/**
 * Dapatkan label keterampilan berdasarkan ID.
 * @param {Array} opsiKeterampilan
 * @param {number|string} id
 * @returns {string}
 */
export function keterampilanLabelById(opsiKeterampilan, id) {
    return keterampilanById(opsiKeterampilan, id)?.label || '';
}
