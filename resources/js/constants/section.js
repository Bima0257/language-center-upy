/**
 * Helper section berbasis ID — perilaku material ditentukan policy database
 * (kebijakan_materi_soal) yang dikirim backend, bukan pencocokan kode section.
 */

/**
 * Cari section berdasarkan ID dari array opsiSection.
 * @param {Array} opsiSection - Array [{value: id, label: name, kode: kode}]
 * @param {number|string} id - ID section
 * @returns {Object|null}
 */
export function sectionById(opsiSection, id) {
    return opsiSection.find((s) => String(s.value) === String(id)) || null;
}

/**
 * Dapatkan label section berdasarkan ID.
 * @param {Array} opsiSection
 * @param {number|string} id
 * @returns {string}
 */
export function sectionLabelById(opsiSection, id) {
    return sectionById(opsiSection, id)?.label || '';
}
