/**
 * Format label program tes menjadi "kode - nama".
 * Dipakai pada kolom tabel agar tampilan kode dan nama konsisten.
 *
 * @param {{ kode?: string|null, nama?: string|null }|null|undefined} programTes
 * @returns {string}
 */
export function formatProgramTes(programTes) {
    if (!programTes) return '-';

    const kode = programTes.kode ?? '';
    const nama = programTes.nama ?? '';

    if (kode && nama) return `${kode} - ${nama}`;

    return nama || kode || '-';
}
